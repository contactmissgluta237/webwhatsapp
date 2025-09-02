<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AiModel;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CustomerTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Création des données de test pour customer1@example.com...');

        // 1. Trouver ou créer l'utilisateur customer1@example.com
        $customer = User::firstOrCreate(
            ['email' => 'customer1@example.com'],
            [
                'first_name' => 'Customer',
                'last_name' => 'Test',
                'phone_number' => '+1234567890',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'is_active' => true,
                'affiliation_code' => Str::random(4),
                'locale' => 'fr',
            ]
        );
        $this->command->info("✓ Utilisateur créé/trouvé: {$customer->email}");

        // 2. Créer un modèle IA s'il n'existe pas
        $aiModel = AiModel::firstOrCreate(
            ['name' => 'gpt-3.5-turbo'],
            [
                'provider' => 'openai',
                'is_active' => true,
                'is_default' => true,
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'cost_per_token' => 0.0001,
            ]
        );
        $this->command->info("✓ Modèle IA créé/trouvé: {$aiModel->name}");

        // 3. Créer un compte WhatsApp avec agent activé
        $whatsappAccount = WhatsAppAccount::firstOrCreate(
            [
                'user_id' => $customer->id,
                'session_id' => 'test_session_' . $customer->id,
            ],
            [
                'session_name' => 'Test WhatsApp Business',
                'agent_name' => 'Assistant IA Test',
                'agent_enabled' => true,
                'agent_prompt' => 'Tu es un assistant commercial intelligent. Tu aides les clients avec leurs questions sur nos produits et services. Sois poli, professionnel et utile.',
                'ai_model_id' => $aiModel->id,
                'trigger_words' => 'bonjour,salut,aide,question,produit,service',
                'contextual_information' => 'Nous vendons des produits numériques de qualité avec un excellent service client.',
                'ignore_words' => 'spam,pub,publicité',
                'response_time' => 'fast',
                'stop_on_human_reply' => false,
                'status' => 'ready',
            ]
        );
        $this->command->info("✓ Compte WhatsApp créé/trouvé: {$whatsappAccount->session_name}");

        // 4. Créer plusieurs conversations avec des messages
        $conversations = [];
        $phoneNumbers = [
            '+33612345678',
            '+33687654321', 
            '+33623456789',
            '+33698765432',
            '+33634567890'
        ];

        $customerNames = [
            'Alice Martin',
            'Bob Dupont', 
            'Claire Moreau',
            'David Bernard',
            'Emma Petit'
        ];

        $conversationTopics = [
            [
                'topic' => 'Demande d\'informations produit',
                'messages' => [
                    ['from_customer' => true, 'body' => 'Bonjour, j\'aimerais avoir des informations sur vos produits'],
                    ['from_customer' => false, 'body' => 'Bonjour ! Je serais ravi de vous aider. Quels types de produits vous intéressent ?'],
                    ['from_customer' => true, 'body' => 'Je cherche quelque chose pour mon entreprise'],
                    ['from_customer' => false, 'body' => 'Parfait ! Nous avons plusieurs solutions adaptées aux entreprises. Pourriez-vous me dire quel est votre secteur d\'activité ?'],
                ]
            ],
            [
                'topic' => 'Support technique',
                'messages' => [
                    ['from_customer' => true, 'body' => 'Salut, j\'ai un problème avec ma commande'],
                    ['from_customer' => false, 'body' => 'Bonjour ! Je suis là pour vous aider. Pouvez-vous me donner votre numéro de commande ?'],
                    ['from_customer' => true, 'body' => 'C\'est la commande #CMD-12345'],
                    ['from_customer' => false, 'body' => 'Merci ! Je vérifie votre commande immédiatement. Un instant...'],
                    ['from_customer' => false, 'body' => 'J\'ai trouvé votre commande. Elle a été expédiée hier. Vous devriez la recevoir d\'ici 2-3 jours.'],
                ]
            ],
            [
                'topic' => 'Demande de devis',
                'messages' => [
                    ['from_customer' => true, 'body' => 'Bonjour, je voudrais un devis'],
                    ['from_customer' => false, 'body' => 'Bonjour ! Avec plaisir. Pour établir un devis personnalisé, j\'aurais besoin de quelques informations.'],
                    ['from_customer' => true, 'body' => 'Pas de souci, que voulez-vous savoir ?'],
                    ['from_customer' => false, 'body' => 'Combien d\'employés dans votre entreprise et quel est votre budget approximatif ?'],
                ]
            ],
            [
                'topic' => 'Question sur les prix',
                'messages' => [
                    ['from_customer' => true, 'body' => 'Quels sont vos tarifs ?'],
                    ['from_customer' => false, 'body' => 'Nos tarifs varient selon vos besoins. Nous avons plusieurs formules : Starter à partir de 29€/mois, Pro à 79€/mois et Business à 149€/mois.'],
                    ['from_customer' => true, 'body' => 'Quelle est la différence entre Pro et Business ?'],
                    ['from_customer' => false, 'body' => 'La formule Business inclut des fonctionnalités avancées comme l\'IA, plus d\'agents simultanés et un support prioritaire.'],
                ]
            ],
            [
                'topic' => 'Réclamation',
                'messages' => [
                    ['from_customer' => true, 'body' => 'Je ne suis pas satisfait de mon achat'],
                    ['from_customer' => false, 'body' => 'Je suis désolé d\'apprendre cela. Pouvez-vous me dire ce qui ne va pas pour que je puisse vous aider ?'],
                    ['from_customer' => true, 'body' => 'Le produit ne correspond pas à la description'],
                    ['from_customer' => false, 'body' => 'Je comprends votre frustration. Nous allons résoudre cela rapidement. Voulez-vous un remboursement ou un échange ?'],
                ]
            ]
        ];

        foreach ($phoneNumbers as $index => $phoneNumber) {
            $conversation = WhatsAppConversation::create([
                'whatsapp_account_id' => $whatsappAccount->id,
                'phone_number' => $phoneNumber,
                'customer_name' => $customerNames[$index],
                'last_message_at' => now()->subMinutes(rand(10, 1440)), // Messages entre 10 min et 24h
                'message_count' => 0, // Sera mis à jour après
                'is_archived' => rand(0, 10) < 2, // 20% de chance d'être archivé
            ]);

            $conversations[] = $conversation;
            
            // Créer les messages pour cette conversation
            $topicData = $conversationTopics[$index % count($conversationTopics)];
            $messageCount = 0;
            $lastMessageTime = $conversation->last_message_at;

            foreach ($topicData['messages'] as $msgIndex => $msgData) {
                WhatsAppMessage::create([
                    'whatsapp_conversation_id' => $conversation->id,
                    'whatsapp_account_id' => $whatsappAccount->id,
                    'message_id' => 'msg_' . Str::random(10),
                    'from_phone' => $msgData['from_customer'] ? $phoneNumber : $whatsappAccount->session_id,
                    'to_phone' => $msgData['from_customer'] ? $whatsappAccount->session_id : $phoneNumber,
                    'body' => $msgData['body'],
                    'type' => 'text',
                    'status' => 'delivered',
                    'is_from_customer' => $msgData['from_customer'],
                    'timestamp' => $lastMessageTime->addMinutes($msgIndex * 2), // Espacement de 2 min entre messages
                    'created_at' => $lastMessageTime->addMinutes($msgIndex * 2),
                ]);
                $messageCount++;
            }

            // Mettre à jour le nombre de messages dans la conversation
            $conversation->update([
                'message_count' => $messageCount,
                'last_message_at' => $lastMessageTime->addMinutes(count($topicData['messages']) * 2),
            ]);
        }

        $this->command->info("✓ Créé {$whatsappAccount->conversations->count()} conversations avec " . 
                           $whatsappAccount->messages->count() . " messages");

        // 5. Statistiques finales
        $this->command->line('');
        $this->command->info('🎉 Données de test créées avec succès !');
        $this->command->line('');
        $this->command->table(
            ['Élément', 'Quantité', 'Détails'],
            [
                ['Utilisateur', '1', $customer->email],
                ['Compte WhatsApp', '1', $whatsappAccount->session_name],
                ['Agent IA', '1', 'Activé avec prompt personnalisé'],
                ['Conversations', count($conversations), 'Avec différents clients'],
                ['Messages', $whatsappAccount->messages->count(), 'Messages bidirectionnels'],
            ]
        );
        
        $this->command->line('');
        $this->command->info('📱 Tu peux maintenant te connecter avec:');
        $this->command->info('   Email: customer1@example.com');
        $this->command->info('   Mot de passe: password');
        $this->command->line('');
        $this->command->info('🔗 Pour voir les conversations: /dashboard/whatsapp/conversations/{account_id}');
    }
}