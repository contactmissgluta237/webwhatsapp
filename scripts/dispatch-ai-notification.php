<?php

declare(strict_types=1);

/**
 * Script pour dispatcher une notification AI Unknown Information de test
 */

require_once __DIR__.'/../vendor/autoload.php';

use App\DTOs\WhatsApp\WhatsAppMessageRequestDTO;
use App\Models\WhatsAppAccount;
use App\Notifications\WhatsApp\AIUnknownInformationNotification;
use Illuminate\Support\Facades\Notification;

// Initialiser Laravel
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🚀 Dispatch de notification AI Unknown Information\n";
echo "================================================\n\n";

try {
    // Récupérer le compte WhatsApp disponible
    $account = WhatsAppAccount::with(['user', 'settings'])->find(9);

    if (! $account) {
        echo "❌ Aucun compte WhatsApp trouvé avec l'ID 9\n";
        exit(1);
    }

    echo "✅ Compte WhatsApp trouvé :\n";
    echo "   - ID: {$account->id}\n";
    echo "   - Session: {$account->session_name}\n";
    echo '   - Agent: '.($account->agent_name ?? 'Non défini')."\n";
    echo "   - Utilisateur: {$account->user->first_name} {$account->user->last_name}\n";
    echo '   - Locale utilisateur: '.($account->user->locale ?? 'Non définie')."\n\n";

    if ($account->settings) {
        echo "✅ Paramètres de notification :\n";
        echo '   - Email activé: '.($account->settings->enable_email_notifications ? 'Oui' : 'Non')."\n";
        echo "   - Email destination: {$account->settings->notification_email}\n";
        echo '   - WhatsApp activé: '.($account->settings->enable_whatsapp_notifications ? 'Oui' : 'Non')."\n";
        echo "   - WhatsApp destination: {$account->settings->notification_whatsapp_number}\n\n";
    } else {
        echo "⚠️  Aucun paramètre de notification configuré\n\n";
    }

    // Créer un message de test
    $messageRequest = new WhatsAppMessageRequestDTO(
        id: 'test_dispatch_'.time(),
        from: '237693456789@c.us',
        body: 'Bonjour, j\'aimerais savoir si vous livrez les commandes le dimanche et quels sont les frais de livraison pour ma zone ?',
        timestamp: now()->timestamp,
        type: 'text',
        isGroup: false
    );

    echo "✅ Message client créé :\n";
    echo "   - De: {$messageRequest->getContactPhone()}\n";
    echo '   - Message: '.substr($messageRequest->body, 0, 80)."...\n\n";

    // Simuler la réponse IA incomplète
    $aiResponse = "Je peux vous aider avec les informations de livraison, mais je n'ai pas accès aux détails spécifiques concernant les livraisons du dimanche et les frais par zone. Notre équipe pourra vous donner des informations plus précises.";

    echo "✅ Réponse IA préparée :\n";
    echo '   - Réponse: '.substr($aiResponse, 0, 80)."...\n\n";

    // Créer la notification
    $conversationId = (int) (time() % 10000); // ID de conversation simulé basé sur le timestamp
    $notification = new AIUnknownInformationNotification(
        $account,
        $messageRequest,
        $aiResponse,
        $conversationId
    );

    echo "📤 Dispatch de la notification...\n";

    // Dispatcher la notification
    Notification::send($account, $notification);

    echo "✅ Notification dispatchée avec succès !\n\n";

    echo "📋 Détails de la notification :\n";
    echo "===============================\n";

    // Afficher les détails de ce qui a été envoyé
    $channels = $notification->via($account);
    echo 'Canaux utilisés : '.implode(', ', $channels)."\n\n";

    if (in_array('mail', $channels)) {
        $mailData = $notification->toMail($account);
        $envelope = $mailData->envelope();
        echo "📧 Email :\n";
        echo "   - Sujet: {$envelope->subject}\n";
        echo "   - Destinataire: {$account->settings->notification_email}\n\n";
    }

    if (in_array(\App\Channels\WhatsAppChannel::class, $channels)) {
        $whatsappData = $notification->toWhatsApp($account);
        echo "📱 WhatsApp :\n";
        echo "   - Destinataire: {$whatsappData['to']}\n";
        echo "   - Session ID: {$whatsappData['session_id']}\n";
        echo '   - Message (extrait): '.substr($whatsappData['message'], 0, 100)."...\n\n";
    }

    if (in_array('database', $channels)) {
        $arrayData = $notification->toArray($account);
        echo "🔔 Base de données :\n";
        echo "   - Type: {$arrayData['type']}\n";
        echo "   - Titre: {$arrayData['title']}\n";
        echo "   - URL conversation: {$arrayData['conversation_url']}\n\n";
    }

    echo "🎉 Notification dispatchée avec succès !\n";
    echo "\n💡 Vérifiez :\n";
    echo "   - Votre boîte email ({$account->settings->notification_email})\n";
    echo "   - Votre WhatsApp ({$account->settings->notification_whatsapp_number})\n";
    echo "   - Les logs Laravel pour les détails techniques\n";

} catch (Exception $e) {
    echo "❌ Erreur lors du dispatch de la notification :\n";
    echo "   {$e->getMessage()}\n";
    echo "   Fichier: {$e->getFile()}:{$e->getLine()}\n";
    exit(1);
}

echo "\n✅ Script terminé avec succès !\n";
