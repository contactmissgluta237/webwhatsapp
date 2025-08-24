<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\WhatsApp\Components\AiProductsConfiguration;
use App\Models\User;
use App\Models\UserProduct;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class AiProductsConfigurationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private WhatsAppAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        // Create customer role if it doesn't exist
        if (! Role::where('name', 'customer')->exists()) {
            Role::create(['name' => 'customer']);
        }

        $this->user = User::factory()->create();
        $this->user->assignRole('customer');

        $this->account = WhatsAppAccount::factory()->create([
            'user_id' => $this->user->id,
        ]);
    }

    public function test_component_loads_successfully(): void
    {
        $this->actingAs($this->user);

        Livewire::test(AiProductsConfiguration::class, ['account' => $this->account])
            ->assertSuccessful()
            ->assertSee('Produits liés à l\'agent')
            ->assertSee('Ajouter des produits');
    }

    public function test_can_add_product_to_ai_agent(): void
    {
        $this->actingAs($this->user);

        $product = UserProduct::factory()
            ->withUser($this->user)
            ->active()
            ->create();

        Livewire::test(AiProductsConfiguration::class, ['account' => $this->account])
            ->call('addProduct', $product->id)
            ->assertDispatched('show-toast')
            ->assertDispatched('products-updated');

        $this->assertTrue($this->account->userProducts->contains($product));
    }

    public function test_cannot_add_inactive_product(): void
    {
        $this->actingAs($this->user);

        $product = UserProduct::factory()
            ->withUser($this->user)
            ->inactive()
            ->create();

        Livewire::test(AiProductsConfiguration::class, ['account' => $this->account])
            ->call('addProduct', $product->id)
            ->assertDispatched('show-toast');

        $this->assertFalse($this->account->userProducts->contains($product));
    }

    public function test_cannot_add_duplicate_product(): void
    {
        $this->actingAs($this->user);

        $product = UserProduct::factory()
            ->withUser($this->user)
            ->active()
            ->create();

        // First, link the product
        $this->account->userProducts()->attach($product->id);

        // Try to add it again
        Livewire::test(AiProductsConfiguration::class, ['account' => $this->account])
            ->call('addProduct', $product->id)
            ->assertDispatched('show-toast');

        // Should still have only one link
        $this->assertEquals(1, $this->account->userProducts()->count());
    }

    public function test_cannot_add_more_than_10_products(): void
    {
        $this->actingAs($this->user);

        // Create and link 10 products
        $products = UserProduct::factory()
            ->withUser($this->user)
            ->active()
            ->count(10)
            ->create();

        foreach ($products as $product) {
            $this->account->userProducts()->attach($product->id);
        }

        // Try to add an 11th product
        $eleventhProduct = UserProduct::factory()
            ->withUser($this->user)
            ->active()
            ->create();

        Livewire::test(AiProductsConfiguration::class, ['account' => $this->account])
            ->call('addProduct', $eleventhProduct->id)
            ->assertDispatched('show-toast');

        $this->assertEquals(10, $this->account->userProducts()->count());
    }

    public function test_can_remove_product(): void
    {
        $this->actingAs($this->user);

        $product = UserProduct::factory()
            ->withUser($this->user)
            ->active()
            ->create();

        $this->account->userProducts()->attach($product->id);

        Livewire::test(AiProductsConfiguration::class, ['account' => $this->account])
            ->call('removeProduct', $product->id)
            ->assertDispatched('show-toast')
            ->assertDispatched('products-updated');

        $this->assertFalse($this->account->userProducts->contains($product));
    }

    public function test_search_filters_products(): void
    {
        $this->actingAs($this->user);

        $product1 = UserProduct::factory()
            ->withUser($this->user)
            ->active()
            ->create(['title' => 'iPhone 15']);

        $product2 = UserProduct::factory()
            ->withUser($this->user)
            ->active()
            ->create(['title' => 'Samsung Galaxy']);

        $component = Livewire::test(AiProductsConfiguration::class, ['account' => $this->account])
            ->set('searchTerm', 'iPhone')
            ->assertSee('iPhone 15')
            ->assertDontSee('Samsung Galaxy');
    }

    public function test_displays_correct_remaining_slots(): void
    {
        $this->actingAs($this->user);

        // Link 3 products
        $products = UserProduct::factory()
            ->withUser($this->user)
            ->active()
            ->count(3)
            ->create();

        foreach ($products as $product) {
            $this->account->userProducts()->attach($product->id);
        }

        $component = Livewire::test(AiProductsConfiguration::class, ['account' => $this->account]);

        $this->assertEquals(7, $component->get('remainingSlots'));
        $this->assertTrue($component->get('canAddMoreProducts'));
    }

    public function test_shows_linked_products_with_badge(): void
    {
        $this->actingAs($this->user);

        $products = UserProduct::factory()
            ->withUser($this->user)
            ->active()
            ->count(2)
            ->create();

        foreach ($products as $product) {
            $this->account->userProducts()->attach($product->id);
        }

        Livewire::test(AiProductsConfiguration::class, ['account' => $this->account])
            ->assertSee('2/10'); // Badge should show 2 out of 10
    }
}
