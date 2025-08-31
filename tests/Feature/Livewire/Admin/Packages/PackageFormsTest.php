<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Admin\Packages;

use App\Livewire\Admin\Packages\Forms\CreatePackageForm;
use App\Livewire\Admin\Packages\Forms\EditPackageForm;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PackageFormsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary roles
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);

        $this->admin = User::factory()->admin()->create();
    }

    #[Test]
    public function create_package_form_has_correct_default_values(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreatePackageForm::class)
            ->assertSet('duration_days', '30')
            ->assertSet('availableFeatures', [])
            ->assertSet('currency', 'USD')
            ->assertSet('is_active', true)
            ->assertSet('is_recurring', true)
            ->assertSet('one_time_only', false);
    }

    #[Test]
    public function duration_adjusts_automatically_for_trial_package(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreatePackageForm::class)
            ->set('name', 'trial')
            ->set('display_name', 'Trial Package')
            ->set('price', '0')
            ->set('messages_limit', '50')
            ->assertSet('duration_days', '7');
    }

    #[Test]
    public function duration_adjusts_automatically_for_regular_package(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreatePackageForm::class)
            ->set('name', 'starter')
            ->set('display_name', 'Starter Package')
            ->set('price', '1000')
            ->set('messages_limit', '200')
            ->assertSet('duration_days', '30');
    }

    #[Test]
    public function available_features_array_is_empty(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(CreatePackageForm::class);

        $this->assertEmpty($component->get('availableFeatures'));
        $this->assertNotContains('weekly_reports', array_keys($component->get('availableFeatures')));
        $this->assertNotContains('priority_support', array_keys($component->get('availableFeatures')));
        $this->assertNotContains('api_access', array_keys($component->get('availableFeatures')));
    }

    #[Test]
    public function can_create_package_successfully(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreatePackageForm::class)
            ->set('name', 'test-package')
            ->set('display_name', 'Test Package')
            ->set('description', 'A test package')
            ->set('price', '1000')
            ->set('currency', 'USD')
            ->set('messages_limit', '500')
            ->set('context_limit', '1000')
            ->set('accounts_limit', '1')
            ->set('products_limit', '0')
            ->set('duration_days', '30')
            ->set('sort_order', '1')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.packages.index'));

        $this->assertDatabaseHas('packages', [
            'name' => 'test-package',
            'display_name' => 'Test Package',
            'price' => 1000,
            'duration_days' => 30,
        ]);
    }

    #[Test]
    public function edit_package_form_loads_existing_data(): void
    {
        $this->actingAs($this->admin);

        $package = Package::factory()->create([
            'name' => 'existing-package',
            'display_name' => 'Existing Package',
            'duration_days' => 7,
        ]);

        Livewire::test(EditPackageForm::class, ['package' => $package])
            ->assertSet('name', 'existing-package')
            ->assertSet('display_name', 'Existing Package')
            ->assertSet('duration_days', '7');
    }

    #[Test]
    public function can_update_package_successfully(): void
    {
        $this->actingAs($this->admin);

        $package = Package::factory()->create([
            'name' => 'old-package',
            'display_name' => 'Old Package',
        ]);

        Livewire::test(EditPackageForm::class, ['package' => $package])
            ->set('name', 'updated-package')
            ->set('display_name', 'Updated Package')
            ->set('duration_days', '15')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.packages.index'));

        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'name' => 'updated-package',
            'display_name' => 'Updated Package',
            'duration_days' => 15,
        ]);
    }

    #[Test]
    public function form_validation_works_correctly(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreatePackageForm::class)
            ->set('name', '') // Required field
            ->set('display_name', '') // Required field
            ->set('price', '') // Required field
            ->call('save')
            ->assertHasErrors(['name', 'display_name', 'price']);
    }

    #[Test]
    public function removed_features_do_not_appear_in_form(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.packages.create'));

        $response->assertStatus(200);
        $response->assertDontSee('Rapports hebdomadaires');
        $response->assertDontSee('Support prioritaire');
        $response->assertDontSee('Accès API');
        $response->assertDontSee('weekly_reports');
        $response->assertDontSee('priority_support');
        $response->assertDontSee('api_access');
    }
}
