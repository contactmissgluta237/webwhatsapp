<?php

require_once __DIR__.'/vendor/autoload.php';

use Minishlink\WebPush\VAPID;

echo "🔑 Génération des clés VAPID pour les notifications push\n";
echo '='.str_repeat('=', 50)."\n\n";

try {
    $keys = VAPID::createVapidKeys();

    echo "✅ Clés générées avec succès !\n\n";
    echo "📋 Ajoutez ces lignes à votre fichier .env :\n";
    echo "----------------------------------------\n";
    echo 'VAPID_PUBLIC_KEY='.$keys['publicKey']."\n";
    echo 'VAPID_PRIVATE_KEY='.$keys['privateKey']."\n";
    echo 'VAPID_SUBJECT=mailto:admin@'.parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)."\n";
    echo "----------------------------------------\n\n";

    echo "🔧 Après avoir ajouté ces variables :\n";
    echo "1. Rechargez votre serveur web\n";
    echo "2. Visitez /push/diagnostic pour vérifier\n";
    echo "3. Testez les notifications sur /test/notification\n\n";

} catch (Exception $e) {
    echo '❌ Erreur lors de la génération des clés : '.$e->getMessage()."\n";
}
