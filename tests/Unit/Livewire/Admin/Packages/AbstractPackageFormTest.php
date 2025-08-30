<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire\Admin\Packages;

use App\Livewire\Admin\Packages\Forms\CreatePackageForm;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AbstractPackageFormTest extends TestCase
{
    #[Test]
    public function available_features_array_is_empty(): void
    {
        $form = new CreatePackageForm;

        $this->assertIsArray($form->availableFeatures);
        $this->assertEmpty($form->availableFeatures);
    }

    #[Test]
    public function default_values_are_correct(): void
    {
        $form = new CreatePackageForm;

        $this->assertEquals('30', $form->duration_days);
        $this->assertEquals('XAF', $form->currency);
        $this->assertEquals('1000', $form->context_limit);
        $this->assertEquals('1', $form->accounts_limit);
        $this->assertEquals('0', $form->products_limit);
        $this->assertTrue($form->is_recurring);
        $this->assertFalse($form->one_time_only);
        $this->assertTrue($form->is_active);
        $this->assertEquals('0', $form->sort_order);
    }

    #[Test]
    public function removed_features_are_not_available(): void
    {
        $form = new CreatePackageForm;

        $this->assertArrayNotHasKey('weekly_reports', $form->availableFeatures);
        $this->assertArrayNotHasKey('priority_support', $form->availableFeatures);
        $this->assertArrayNotHasKey('api_access', $form->availableFeatures);
    }
}
