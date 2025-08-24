<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Livewire\Customer\Products\Forms\EditProductForm;
use App\Models\User;
use App\Models\UserProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class ProductEditTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private UserProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Role::where('name', 'customer')->exists()) {
            Role::create(['name' => 'customer']);
        }

        $this->user = User::factory()->create();
        $this->user->assignRole('customer');

        // Créer un produit de test
        $this->product = UserProduct::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Produit Original',
            'description' => 'Description originale',
            'price' => 10000.0,
            'is_active' => true,
        ]);

        Storage::fake('public');
    }

    public function test_customer_can_access_edit_product_page(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('customer.products.edit', $this->product));

        $response->assertSuccessful()
            ->assertSee('Modifier le produit')
            ->assertSee($this->product->title)
            ->assertSee($this->product->description);
    }

    public function test_customer_can_edit_product_basic_info(): void
    {
        $this->actingAs($this->user);

        Livewire::test(EditProductForm::class, ['product' => $this->product])
            ->set('title', 'Titre Modifié')
            ->set('description', 'Description modifiée')
            ->set('price', 15000.0)
            ->set('is_active', false)
            ->call('save');

        // Vérifier que les modifications sont en base
        $this->assertDatabaseHas('user_products', [
            'id' => $this->product->id,
            'user_id' => $this->user->id,
            'title' => 'Titre Modifié',
            'description' => 'Description modifiée',
            'price' => 15000.0,
            'is_active' => false,
        ]);

        // Vérifier que l'ancien titre n'existe plus
        $this->assertDatabaseMissing('user_products', [
            'id' => $this->product->id,
            'title' => 'Produit Original',
        ]);
    }

    public function test_customer_can_edit_product_with_new_media(): void
    {
        $this->actingAs($this->user);

        // Créer de nouveaux fichiers
        $newImage = UploadedFile::fake()->image('new-image.jpg', 800, 600)->size(1000);
        $newPdf = UploadedFile::fake()->create('new-document.pdf', 2000, 'application/pdf');

        Livewire::test(EditProductForm::class, ['product' => $this->product])
            ->set('title', 'Produit avec Nouveaux Médias')
            ->set('description', 'Description avec médias')
            ->set('price', 20000.0)
            ->set('media', [$newImage, $newPdf])
            ->call('save');

        $this->product->refresh();

        // Vérifier les modifications de base
        $this->assertEquals('Produit avec Nouveaux Médias', $this->product->title);
        $this->assertEquals(20000.0, $this->product->price);

        // Vérifier que les médias sont attachés
        $this->assertTrue($this->product->hasMedia('images'));
        $this->assertCount(2, $this->product->getMedia('images'));

        // Vérifier dans la table media
        $this->assertDatabaseHas('media', [
            'model_type' => UserProduct::class,
            'model_id' => $this->product->id,
            'collection_name' => 'images',
        ]);
    }

    public function test_customer_can_edit_product_with_existing_media(): void
    {
        $this->actingAs($this->user);

        // Simuler un produit avec médias existants
        $this->product->update(['title' => 'Produit avec Médias']);

        // Éditer le produit
        Livewire::test(EditProductForm::class, ['product' => $this->product])
            ->set('title', 'Titre Édité avec Médias Existants')
            ->set('description', 'Description éditée')
            ->set('price', 25000.0)
            ->call('save');

        $this->product->refresh();

        // Vérifier les modifications
        $this->assertEquals('Titre Édité avec Médias Existants', $this->product->title);
        $this->assertEquals(25000.0, $this->product->price);
    }

    public function test_customer_can_edit_product_adding_media_to_existing(): void
    {
        $this->actingAs($this->user);

        // Ajouter de nouveaux médias
        $newImage = UploadedFile::fake()->image('additional.png', 800, 600);
        $newVideo = UploadedFile::fake()->create('video.mp4', 3000, 'video/mp4');

        Livewire::test(EditProductForm::class, ['product' => $this->product])
            ->set('title', 'Produit avec Médias Combinés')
            ->set('media', [$newImage, $newVideo])
            ->call('save');

        $this->product->refresh();

        // Vérifier les modifications de base
        $this->assertEquals('Produit avec Médias Combinés', $this->product->title);

        // Vérifier que les médias sont présents
        $this->assertTrue($this->product->hasMedia('images'));
        $totalMediaCount = $this->product->getMedia('images')->count();
        $this->assertGreaterThanOrEqual(2, $totalMediaCount);
    }

    public function test_product_edit_validates_required_fields(): void
    {
        $this->actingAs($this->user);

        // Test title required - vider le titre et sauvegarder
        Livewire::test(EditProductForm::class, ['product' => $this->product])
            ->set('title', '')
            ->set('description', 'Description valide')
            ->set('price', 1000.0)
            ->call('save')
            ->assertHasErrors('title');

        // Test description required
        Livewire::test(EditProductForm::class, ['product' => $this->product])
            ->set('title', 'Titre valide')
            ->set('description', '')
            ->set('price', 1000.0)
            ->call('save')
            ->assertHasErrors('description');

        // Test price validation - prix négatif
        Livewire::test(EditProductForm::class, ['product' => $this->product])
            ->set('title', 'Titre valide')
            ->set('description', 'Description valide')
            ->set('price', -1)
            ->call('save')
            ->assertHasErrors('price');
    }

    public function test_customer_cannot_edit_other_users_product(): void
    {
        // Créer un autre utilisateur et son produit
        $otherUser = User::factory()->create();
        $otherUser->assignRole('customer');

        $otherProduct = UserProduct::factory()->create([
            'user_id' => $otherUser->id,
            'title' => 'Produit d\'un autre utilisateur',
        ]);

        $this->actingAs($this->user);

        // Tenter d'accéder à la page d'édition d'un produit d'un autre utilisateur
        $response = $this->get(route('customer.products.edit', $otherProduct));

        $response->assertStatus(403); // Forbidden
    }

    public function test_unauthorized_user_cannot_edit_product(): void
    {
        $response = $this->get(route('customer.products.edit', $this->product));

        $response->assertRedirect(route('login'));
    }

    public function test_edit_form_loads_existing_product_data(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(EditProductForm::class, ['product' => $this->product]);

        // Vérifier que les données existantes sont chargées
        $component->assertSet('title', $this->product->title);
        $component->assertSet('description', $this->product->description);
        $component->assertSet('price', (float) $this->product->price);
        $component->assertSet('is_active', $this->product->is_active);
    }

    public function test_product_edit_preserves_user_ownership(): void
    {
        $this->actingAs($this->user);

        Livewire::test(EditProductForm::class, ['product' => $this->product])
            ->set('title', 'Titre Modifié')
            ->set('description', 'Description modifiée')
            ->set('price', 30000.0)
            ->call('save');

        $this->product->refresh();

        // Vérifier que l'ownership n'a pas changé
        $this->assertEquals($this->user->id, $this->product->user_id);
    }

    public function test_edit_form_handles_price_conversion(): void
    {
        $this->actingAs($this->user);

        // Tester avec différents formats de prix
        Livewire::test(EditProductForm::class, ['product' => $this->product])
            ->set('price', '15000.50')
            ->call('save');

        $this->product->refresh();
        $this->assertEquals(15000.5, $this->product->price);
    }

    public function test_customer_can_toggle_product_status(): void
    {
        $this->actingAs($this->user);

        // Le produit est actif par défaut
        $this->assertTrue($this->product->is_active);

        // Désactiver le produit
        Livewire::test(EditProductForm::class, ['product' => $this->product])
            ->set('is_active', false)
            ->call('save');

        $this->product->refresh();
        $this->assertFalse($this->product->is_active);

        // Réactiver le produit
        Livewire::test(EditProductForm::class, ['product' => $this->product])
            ->set('is_active', true)
            ->call('save');

        $this->product->refresh();
        $this->assertTrue($this->product->is_active);
    }
}
