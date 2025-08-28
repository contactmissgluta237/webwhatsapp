<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\WhatsApp;

use App\Repositories\WhatsApp\Contracts\WhatsAppAccountRepositoryInterface;
use App\Repositories\WhatsApp\Contracts\WhatsAppMessageRepositoryInterface;
use App\Repositories\WhatsApp\EloquentWhatsAppAccountRepository;
use App\Repositories\WhatsApp\EloquentWhatsAppMessageRepository;
use Tests\TestCase;

final class RepositoryStructureTest extends TestCase
{
    /**
     * Test that WhatsApp repositories are properly bound in service container
     */
    public function test_whatsapp_repositories_are_bound(): void
    {
        // Test WhatsApp Account Repository binding
        $accountRepository = $this->app->make(WhatsAppAccountRepositoryInterface::class);
        $this->assertInstanceOf(EloquentWhatsAppAccountRepository::class, $accountRepository);

        // Test WhatsApp Message Repository binding
        $messageRepository = $this->app->make(WhatsAppMessageRepositoryInterface::class);
        $this->assertInstanceOf(EloquentWhatsAppMessageRepository::class, $messageRepository);
    }

    /**
     * Test that repository interfaces exist
     */
    public function test_repository_interfaces_exist(): void
    {
        $this->assertTrue(interface_exists(WhatsAppAccountRepositoryInterface::class));
        $this->assertTrue(interface_exists(WhatsAppMessageRepositoryInterface::class));
    }

    /**
     * Test that repository implementations exist
     */
    public function test_repository_implementations_exist(): void
    {
        $this->assertTrue(class_exists(EloquentWhatsAppAccountRepository::class));
        $this->assertTrue(class_exists(EloquentWhatsAppMessageRepository::class));
    }

    /**
     * Test that implementations implement the correct interfaces
     */
    public function test_implementations_implement_interfaces(): void
    {
        $this->assertInstanceOf(
            WhatsAppAccountRepositoryInterface::class,
            new EloquentWhatsAppAccountRepository
        );

        $this->assertInstanceOf(
            WhatsAppMessageRepositoryInterface::class,
            new EloquentWhatsAppMessageRepository
        );
    }
}
