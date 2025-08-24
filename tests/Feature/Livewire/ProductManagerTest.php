<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Customer\ProductManager;
use App\Models\User;
use App\Models\UserProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class ProductManagerTest extends TestCase
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

    public function test_customer_can_see_product_manager(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ProductManager::class)
            ->assertSuccessful()
            ->assertSee('Mes Produits');
    }

    public function test_customer_can_open_create_form(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ProductManager::class)
            ->call('openCreateForm')
            ->assertSet('showForm', true)
            ->assertSee('Nouveau produit');
    }

    public function test_customer_can_create_product(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ProductManager::class)
            ->call('openCreateForm')
            ->set('title', 'Test Product')
            ->set('description', 'Test Description')
            ->set('price', 1500)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('product-saved');

        $this->assertDatabaseHas('user_products', [
            'user_id' => $this->user->id,
            'title' => 'Test Product',
            'description' => 'Test Description',
            'price' => 1500,
        ]);
    }

    public function test_product_creation_validation(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ProductManager::class)
            ->call('openCreateForm')
            ->set('title', '')
            ->set('description', '')
            ->set('price', -1)
            ->call('save')
            ->assertHasErrors(['title', 'description', 'price']);
    }

    public function test_customer_can_edit_own_product(): void
    {
        $this->actingAs($this->user);

        $product = UserProduct::factory()->withUser($this->user)->create([
            'title' => 'Original Title',
            'description' => 'Original Description',
            'price' => 1000,
        ]);

        Livewire::test(ProductManager::class)
            ->call('edit', $product->id)
            ->assertSet('editingProduct.id', $product->id)
            ->assertSet('title', 'Original Title')
            ->set('title', 'Updated Title')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('product-saved');

        $this->assertDatabaseHas('user_products', [
            'id' => $product->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_customer_can_toggle_product_status(): void
    {
        $this->actingAs($this->user);

        $product = UserProduct::factory()
            ->withUser($this->user)
            ->active()
            ->create();

        Livewire::test(ProductManager::class)
            ->call('toggleStatus', $product->id)
            ->assertDispatched('product-status-updated');

        $this->assertFalse($product->fresh()->is_active);
    }

    public function test_customer_can_delete_own_product(): void
    {
        $this->actingAs($this->user);

        $product = UserProduct::factory()->withUser($this->user)->create();

        Livewire::test(ProductManager::class)
            ->call('delete', $product->id)
            ->assertDispatched('product-deleted');

        $this->assertSoftDeleted('user_products', [
            'id' => $product->id,
        ]);
    }

    public function test_displays_user_products(): void
    {
        $this->actingAs($this->user);

        $products = UserProduct::factory()
            ->withUser($this->user)
            ->count(3)
            ->create();

        $component = Livewire::test(ProductManager::class);

        foreach ($products as $product) {
            $component->assertSee($product->title);
        }
    }
}
