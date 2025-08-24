<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProduct;
use App\Models\WhatsAppAccount;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

final class UserProductsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure customer role exists
        if (! Role::where('name', 'customer')->exists()) {
            Role::create(['name' => 'customer']);
        }

        // Create a demo customer
        $customer = User::factory()->create([
            'first_name' => 'Demo',
            'last_name' => 'Customer',
            'email' => 'demo@customer.com',
        ]);
        $customer->assignRole('customer');

        // Create WhatsApp account for the customer
        $whatsappAccount = WhatsAppAccount::factory()->create([
            'user_id' => $customer->id,
            'session_name' => 'Demo Agent',
            'agent_enabled' => true,
        ]);

        // Create demo products
        $products = [
            [
                'title' => 'iPhone 15 Pro Max',
                'description' => 'Le dernier iPhone avec écran Super Retina XDR de 6,7 pouces, processeur A17 Pro et système photo avancé.',
                'price' => 850000,
                'is_active' => true,
            ],
            [
                'title' => 'MacBook Air M3',
                'description' => 'MacBook Air ultra-portable avec puce M3, écran Liquid Retina 13,6 pouces et autonomie exceptionnelle.',
                'price' => 1200000,
                'is_active' => true,
            ],
            [
                'title' => 'AirPods Pro 3',
                'description' => 'Écouteurs sans fil avec réduction de bruit active, audio spatial et boîtier de charge MagSafe.',
                'price' => 180000,
                'is_active' => true,
            ],
            [
                'title' => 'iPad Pro 12.9"',
                'description' => 'iPad Pro avec écran Liquid Retina XDR, puce M2 et compatibilité Apple Pencil 2.',
                'price' => 750000,
                'is_active' => true,
            ],
            [
                'title' => 'Apple Watch Series 9',
                'description' => 'Montre connectée avec suivi de santé avancé, GPS et écran Retina Always-On.',
                'price' => 280000,
                'is_active' => true,
            ],
            [
                'title' => 'Samsung Galaxy S24 Ultra',
                'description' => 'Smartphone premium avec écran Dynamic AMOLED 6,8", S Pen intégré et appareil photo 200MP.',
                'price' => 800000,
                'is_active' => true,
            ],
            [
                'title' => 'Dell XPS 13',
                'description' => 'Ultrabook avec processeur Intel Core i7, écran InfinityEdge 13,4" et design ultra-compact.',
                'price' => 950000,
                'is_active' => false, // Produit inactif pour démonstration
            ],
        ];

        $createdProducts = [];
        foreach ($products as $productData) {
            $product = UserProduct::factory()
                ->withUser($customer)
                ->create($productData);

            $createdProducts[] = $product;
        }

        // Link first 5 products to the WhatsApp agent (demonstrating the 10 limit)
        $productsToLink = array_slice($createdProducts, 0, 5);
        foreach ($productsToLink as $product) {
            if ($product->is_active) {
                $whatsappAccount->userProducts()->attach($product->id);
            }
        }

        $this->command->info('Created demo customer with '.count($createdProducts).' products');
        $this->command->info('Linked '.count($productsToLink).' active products to WhatsApp agent');
        $this->command->info('Demo customer credentials: demo@customer.com');
    }
}
