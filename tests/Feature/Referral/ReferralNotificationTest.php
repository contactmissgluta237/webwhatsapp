<?php

namespace Tests\Feature\Referral;

use App\DTOs\Customer\CreateCustomerDTO;
use App\Events\CustomerCreatedEvent;
use App\Mail\ReferralNotificationMail;
use App\Models\Customer;
use App\Models\User;
use App\Services\Customer\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReferralNotificationTest extends TestCase
{
    use RefreshDatabase;

    private CustomerService $customerService;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les rôles nécessaires pour les tests
        \Spatie\Permission\Models\Role::create(['name' => 'customer']);
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);

        // Créer un pays par défaut pour les tests
        \Illuminate\Support\Facades\DB::table('countries')->insert([
            'id' => 1,
            'name' => 'Cameroon',
            'code' => 'CM',
            'phone_code' => '+237',
            'flag_emoji' => '🇨🇲',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->customerService = app(CustomerService::class);
    }

    /** @test */
    public function customer_created_event_is_dispatched_when_new_customer_is_created()
    {
        // Arrange: Fake events to capture dispatched events
        Event::fake();

        // Create a referrer
        $referrer = User::factory()->customer()->create([
            'affiliation_code' => 'EVENT123',
        ]);

        // Act: Create new customer with referral code
        $dto = new CreateCustomerDTO(
            first_name: 'Test',
            last_name: 'Event',
            email: 'event@test.com',
            password: 'password123',
            phone_number: '+237111222333',
            country_id: 1,
            referral_code: 'EVENT123',
            terms: true
        );

        $customer = $this->customerService->create($dto);

        // Assert: CustomerCreatedEvent was dispatched with correct customer
        Event::assertDispatched(CustomerCreatedEvent::class, function ($event) use ($customer) {
            return $event->customer->id === $customer->id &&
                   $event->customer->referrer_id === $customer->referrer_id;
        });
    }

    /** @test */
    public function referral_relationship_is_established_correctly()
    {
        // Create a referrer user with customer profile
        $referrer = User::factory()->customer()->create([
            'first_name' => 'John',
            'last_name' => 'Referrer',
            'email' => 'referrer@test.com',
            'affiliation_code' => 'REF123',
        ]);

        // Act: Create new customer with referral code
        $dto = new CreateCustomerDTO(
            first_name: 'Jane',
            last_name: 'Referred',
            email: 'referred@test.com',
            password: 'password123',
            phone_number: '+237123456789',
            country_id: 1,
            referral_code: 'REF123',
            terms: true
        );

        $customer = $this->customerService->create($dto);

        // Assert: Verify customer is properly linked to referrer
        $this->assertEquals($referrer->customer->id, $customer->referrer_id);
        $this->assertInstanceOf(Customer::class, $customer->referrer);
        $this->assertEquals($referrer->id, $customer->referrer->user_id);

        // Assert: Referrer has the new customer in referrals
        $this->assertEquals(1, $referrer->customer->referrals()->count());
        $referrals = $referrer->customer->referrals()->get();
        $this->assertTrue($referrals->contains($customer));
    }

    /** @test */
    public function notification_listener_sends_email_when_customer_has_referrer()
    {
        // Create a referrer with customer profile
        $referrerUser = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Referrer',
            'email' => 'referrer@test.com',
        ]);
        $referrerUser->assignRole('customer');

        /** @var Customer */
        $referrerCustomer = Customer::factory()->create([
            'user_id' => $referrerUser->id,
        ]);

        // Create a new customer user
        $newUser = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Referred',
            'email' => 'referred@test.com',
        ]);

        /** @var Customer */
        $customer = Customer::factory()->create([
            'user_id' => $newUser->id,
            'referrer_id' => $referrerCustomer->id,
        ]);

        // Load relationships explicitly to ensure they're available
        $customer->load(['user', 'referrer.user']);

        // Verify relationships are loaded correctly (this is the core test)
        $this->assertNotNull($customer->referrer);
        $this->assertInstanceOf(Customer::class, $customer->referrer);

        /** @var Customer $customerReferrer */
        $customerReferrer = $customer->referrer;
        $this->assertNotNull($customerReferrer->user);
        $this->assertEquals('referrer@test.com', $customerReferrer->user->email);

        // Test that the listener logic would work by checking the conditions
        $listener = new \App\Listeners\NotifyReferrerListener;

        // Mock the Mail facade to verify it would be called
        Mail::shouldReceive('to')
            ->once()
            ->with($referrerUser->email)
            ->andReturnSelf();

        Mail::shouldReceive('send')
            ->once()
            ->with(\Mockery::type(ReferralNotificationMail::class));

        // Act: Trigger the listener
        $event = new CustomerCreatedEvent($customer);
        $listener->handle($event);
    }

    /** @test */
    public function notification_listener_does_not_send_email_when_customer_has_no_referrer()
    {
        // Arrange: Fake mail to capture sent emails
        Mail::fake();

        // Create a customer without referrer
        $customer = Customer::factory()->create([
            'user_id' => User::factory()->create()->id,
            'referrer_id' => null,
        ]);

        // Load relationships
        $customer->load('user');

        // Act: Manually trigger the listener
        $listener = new \App\Listeners\NotifyReferrerListener;
        $event = new CustomerCreatedEvent($customer);
        $listener->handle($event);

        // Assert: No email should be sent
        Mail::assertNotSent(ReferralNotificationMail::class);
    }

    /** @test */
    public function customer_is_created_without_referrer_when_no_referral_code_provided()
    {
        // Act: Create customer without referral code
        $dto = new CreateCustomerDTO(
            first_name: 'Bob',
            last_name: 'Independent',
            email: 'independent@test.com',
            password: 'password123',
            phone_number: '+237987654321',
            country_id: 1,
            referral_code: null,
            terms: true
        );

        $customer = $this->customerService->create($dto);

        // Assert: Customer has no referrer
        $this->assertNull($customer->referrer_id);
        $this->assertNull($customer->referrer);
    }

    /** @test */
    public function customer_is_created_without_referrer_when_invalid_referral_code_provided()
    {
        // Act: Create customer with invalid referral code
        $dto = new CreateCustomerDTO(
            first_name: 'Alice',
            last_name: 'Lost',
            email: 'lost@test.com',
            password: 'password123',
            phone_number: '+237555666777',
            country_id: 1,
            referral_code: 'INVALID123',
            terms: true
        );

        $customer = $this->customerService->create($dto);

        // Assert: Customer has no referrer
        $this->assertNull($customer->referrer_id);
        $this->assertNull($customer->referrer);
    }

    /** @test */
    public function multiple_customers_can_be_referred_by_same_referrer()
    {
        // Create a referrer
        $referrer = User::factory()->customer()->create([
            'affiliation_code' => 'MULTI123',
        ]);

        // Act: Create two customers with same referral code
        $dto1 = new CreateCustomerDTO(
            first_name: 'First',
            last_name: 'Customer',
            email: 'first@test.com',
            password: 'password123',
            phone_number: '+237111111111',
            country_id: 1,
            referral_code: 'MULTI123',
            terms: true
        );

        $dto2 = new CreateCustomerDTO(
            first_name: 'Second',
            last_name: 'Customer',
            email: 'second@test.com',
            password: 'password123',
            phone_number: '+237222222222',
            country_id: 1,
            referral_code: 'MULTI123',
            terms: true
        );

        $customer1 = $this->customerService->create($dto1);
        $customer2 = $this->customerService->create($dto2);

        // Assert: Both customers are linked to the same referrer
        $this->assertEquals($referrer->customer->id, $customer1->referrer_id);
        $this->assertEquals($referrer->customer->id, $customer2->referrer_id);

        // Assert: Referrer has 2 referrals
        $this->assertEquals(2, $referrer->customer->referrals()->count());
    }
}
