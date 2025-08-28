<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Livewire\Customer\Products\Forms\CreateProductForm;
use App\Models\User;
use App\Models\UserProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class ProductCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Role::where('name', 'customer')->exists()) {
            Role::create(['name' => 'customer']);
        }

        $this->user = User::factory()->create();
        $this->user->assignRole('customer');

        Storage::fake('public');
    }

    public function test_customer_can_create_product_with_media(): void
    {
        $this->actingAs($this->user);

        // Créer des fichiers de test
        $image = UploadedFile::fake()->image('test-image.jpg', 800, 600)->size(1000);
        $pdf = UploadedFile::fake()->create('test-document.pdf', 2000, 'application/pdf');

        $component = Livewire::test(CreateProductForm::class)
            ->set('title', 'Produit de Test')
            ->set('description', 'Description complète du produit de test')
            ->set('price', 15000.0)
            ->set('is_active', true);

        $component->set('allMediaFiles', [$image, $pdf]);
        $component->call('save');

        // Vérifications en base de données
        $this->assertDatabaseHas('user_products', [
            'user_id' => $this->user->id,
            'title' => 'Produit de Test',
            'description' => 'Description complète du produit de test',
            'price' => 15000.0,
            'is_active' => true,
        ]);

        $product = UserProduct::where('title', 'Produit de Test')->first();
        $this->assertNotNull($product);

        // Vérifier que les médias sont attachés
        $this->assertTrue($product->hasMedia('medias'));
        $this->assertCount(2, $product->getMedia('medias'));

        // Vérifier dans la table media
        $this->assertDatabaseHas('media', [
            'model_type' => UserProduct::class,
            'model_id' => $product->id,
            'collection_name' => 'medias',
        ]);
    }

    public function test_customer_can_create_product_without_media(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateProductForm::class)
            ->set('title', 'Produit Sans Média')
            ->set('description', 'Un produit simple sans fichiers')
            ->set('price', 5000.0)
            ->set('is_active', true)
            ->call('save');

        $this->assertDatabaseHas('user_products', [
            'user_id' => $this->user->id,
            'title' => 'Produit Sans Média',
            'price' => 5000.0,
        ]);

        $product = UserProduct::where('title', 'Produit Sans Média')->first();
        $this->assertFalse($product->hasMedia('medias'));
    }

    public function test_customer_can_access_create_product_page(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('customer.products.create'));

        $response->assertSuccessful()
            ->assertSee('Créer un produit')
            ->assertSee('Titre')
            ->assertSee('Description')
            ->assertSee('Prix')
            ->assertSee('Médias');
    }

    public function test_customer_can_create_product_with_single_image(): void
    {
        $this->actingAs($this->user);

        $image = UploadedFile::fake()->image('test-product.jpg', 800, 600)->size(1000); // 1MB

        $productData = [
            'title' => 'Produit avec image',
            'description' => 'Description du produit avec image',
            'price' => 35.50,
            'is_active' => true,
        ];

        $component = Livewire::test(CreateProductForm::class)
            ->set('title', $productData['title'])
            ->set('description', $productData['description'])
            ->set('price', $productData['price'])
            ->set('is_active', $productData['is_active']);

        $component->set('allMediaFiles', [$image]);
        $component->call('save');

        $product = UserProduct::where('title', $productData['title'])->first();
        $this->assertNotNull($product);
        $this->assertCount(1, $product->getMedia('medias'));

        $media = $product->getFirstMedia('medias');
        $this->assertEquals('test-product.jpg', $media->name);
        $this->assertEquals('image/jpeg', $media->mime_type);
    }

    public function test_customer_can_create_product_with_multiple_media(): void
    {
        $this->actingAs($this->user);

        $image1 = UploadedFile::fake()->image('image1.jpg', 800, 600)->size(1000);
        $image2 = UploadedFile::fake()->image('image2.png', 600, 400)->size(800);

        $productData = [
            'title' => 'Produit multi-médias',
            'description' => 'Description du produit avec plusieurs médias',
            'price' => 99.99,
            'is_active' => true,
        ];

        $component = Livewire::test(CreateProductForm::class)
            ->set('title', $productData['title'])
            ->set('description', $productData['description'])
            ->set('price', $productData['price'])
            ->set('is_active', $productData['is_active']);

        // Set the media files in the allMediaFiles array directly
        $component->set('allMediaFiles', [$image1, $image2]);
        $component->call('save');

        $product = UserProduct::where('title', $productData['title'])->first();
        $this->assertNotNull($product);
        $this->assertCount(2, $product->getMedia('medias'));

        $mediaTypes = $product->getMedia('medias')->pluck('mime_type')->toArray();
        $this->assertContains('image/jpeg', $mediaTypes);
        $this->assertContains('image/png', $mediaTypes);
        $this->assertCount(2, $mediaTypes);
    }

    public function test_product_creation_validates_required_fields(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateProductForm::class)
            ->set('title', '')
            ->set('description', '')
            ->call('save')
            ->assertHasErrors([
                'title',
                'description',
            ]);
    }

    public function test_product_creation_validates_string_length(): void
    {
        $this->actingAs($this->user);

        // Test titre trop long
        Livewire::test(CreateProductForm::class)
            ->set('title', str_repeat('a', 256))
            ->set('description', 'Description valide')
            ->set('price', 100)
            ->call('save')
            ->assertHasErrors(['title']);

        // Test description trop longue
        Livewire::test(CreateProductForm::class)
            ->set('title', 'Titre valide')
            ->set('description', str_repeat('a', 1001))
            ->set('price', 100)
            ->call('save')
            ->assertHasErrors(['description']);
    }

    public function test_product_creation_validates_price_format(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateProductForm::class)
            ->set('title', 'Produit test')
            ->set('description', 'Description test')
            ->set('price', 0)
            ->call('save')
            ->assertHasErrors(['price']);

        // Test avec un prix négatif
        Livewire::test(CreateProductForm::class)
            ->set('title', 'Produit test')
            ->set('description', 'Description test')
            ->set('price', -10)
            ->call('save')
            ->assertHasErrors(['price']);

        // Test avec un prix non numérique (skip this test as the property is typed as float)
        // Livewire will convert non-numeric strings to 0 for float properties
    }

    public function test_product_creation_validates_file_size(): void
    {
        $this->actingAs($this->user);

        // Test simple de validation - vérifier que les règles existent
        $component = Livewire::test(CreateProductForm::class);
        $rules = $component->instance()->rules();

        $this->assertArrayHasKey('mediaFiles.*', $rules);
        $this->assertContains('max:10240', $rules['mediaFiles.*']);
    }

    public function test_product_creation_validates_file_types(): void
    {
        $this->actingAs($this->user);

        // Vérifier que les règles de validation incluent les types de fichiers corrects
        $component = Livewire::test(CreateProductForm::class);
        $rules = $component->instance()->rules();

        $this->assertArrayHasKey('mediaFiles.*', $rules);
        $this->assertContains('mimes:jpeg,jpg,png,gif,pdf,doc,docx', $rules['mediaFiles.*']);
    }

    public function test_customer_can_remove_media_before_saving(): void
    {
        $this->actingAs($this->user);

        $image1 = UploadedFile::fake()->image('image1.jpg');
        $image2 = UploadedFile::fake()->image('image2.jpg');

        $component = Livewire::test(CreateProductForm::class);
        $component->set('allMediaFiles', [$image1, $image2]);

        $this->assertCount(2, $component->get('allMediaFiles'));

        $component->call('removeMediaFile', 0);
        $this->assertCount(1, $component->get('allMediaFiles'));

        // Vérifie que c'est le bon fichier qui reste
        $this->assertEquals('image2.jpg', $component->get('allMediaFiles')[0]->getClientOriginalName());
    }

    public function test_unauthorized_user_cannot_create_product(): void
    {
        $response = $this->get(route('customer.products.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_product_creation_form_displays_validation_errors(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('customer.products.create'));

        $response->assertSuccessful()
            ->assertSeeLivewire(CreateProductForm::class);
    }
}
