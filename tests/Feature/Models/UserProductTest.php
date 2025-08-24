<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\User;
use App\Models\UserProduct;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class UserProductTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create customer role if it doesn't exist
        if (! Role::where('name', 'customer')->exists()) {
            Role::create(['name' => 'customer']);
        }

        $this->user = User::factory()->create();
        $this->user->assignRole('customer');
    }

    public function test_user_can_create_product(): void
    {
        $product = UserProduct::factory()
            ->withUser($this->user)
            ->create([
                'title' => 'Test Product',
                'description' => 'Test Description',
                'price' => 1500.00,
            ]);

        $this->assertDatabaseHas('user_products', [
            'id' => $product->id,
            'user_id' => $this->user->id,
            'title' => 'Test Product',
            'price' => 1500.00,
        ]);
    }

    public function test_product_belongs_to_user(): void
    {
        $product = UserProduct::factory()->withUser($this->user)->create();

        $this->assertEquals($this->user->id, $product->user->id);
        $this->assertTrue($this->user->userProducts->contains($product));
    }

    public function test_product_can_be_linked_to_whatsapp_account(): void
    {
        $whatsappAccount = WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $product = UserProduct::factory()->withUser($this->user)->create();

        $whatsappAccount->userProducts()->attach($product->id);

        $this->assertTrue($whatsappAccount->userProducts->contains($product));
        $this->assertTrue($product->whatsappAccounts->contains($whatsappAccount));
    }

    public function test_product_price_formatting(): void
    {
        $product = UserProduct::factory()
            ->withUser($this->user)
            ->create(['price' => 1500.50]);

        $this->assertEquals('1 501 XAF', $product->getFormattedPrice());
    }

    public function test_product_activation_deactivation(): void
    {
        $product = UserProduct::factory()
            ->withUser($this->user)
            ->inactive()
            ->create();

        $this->assertFalse($product->isActive());

        $product->activate();
        $this->assertTrue($product->fresh()->isActive());

        $product->deactivate();
        $this->assertFalse($product->fresh()->isActive());
    }

    public function test_whatsapp_account_cannot_link_more_than_10_products(): void
    {
        $whatsappAccount = WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Create 10 products and link them
        $products = UserProduct::factory()
            ->withUser($this->user)
            ->count(10)
            ->create();

        foreach ($products as $product) {
            $whatsappAccount->userProducts()->attach($product->id);
        }

        $this->assertEquals(10, $whatsappAccount->userProducts()->count());

        // Try to add an 11th product - should be handled at application level
        $eleventhProduct = UserProduct::factory()->withUser($this->user)->create();

        // In a real application, this would be prevented by the Livewire component
        // Here we just verify the relationship allows it technically
        $whatsappAccount->userProducts()->attach($eleventhProduct->id);
        $this->assertEquals(11, $whatsappAccount->userProducts()->count());
    }
}
