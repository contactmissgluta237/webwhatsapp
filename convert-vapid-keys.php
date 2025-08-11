<?php

require_once __DIR__.'/vendor/autoload.php';

use Minishlink\WebPush\VAPID;

echo "🔧 Conversion des clés VAPID au format Laravel...\n\n";

try {
    $privateKeyPem = file_get_contents(__DIR__.'/vapid_private.pem');
    $publicKeyPem = file_get_contents(__DIR__.'/vapid_public.pem');

    if (! $privateKeyPem || ! $publicKeyPem) {
        throw new Exception('Impossible de lire les fichiers de clés');
    }

    $privateKeyResource = openssl_pkey_get_private($privateKeyPem);
    $publicKeyResource = openssl_pkey_get_public($publicKeyPem);

    if (! $privateKeyResource || ! $publicKeyResource) {
        throw new Exception('Clés invalides');
    }

    $privateKeyDetails = openssl_pkey_get_details($privateKeyResource);
    $publicKeyDetails = openssl_pkey_get_details($publicKeyResource);

    $privateKeyBase64 = base64_encode($privateKeyDetails['ec']['d']);
    $publicKeyBase64 = base64_encode("\x04".$publicKeyDetails['ec']['x'].$publicKeyDetails['ec']['y']);

    echo "✅ Clés converties avec succès !\n\n";
    echo "📋 Copiez ces lignes dans votre fichier .env :\n";
    echo "----------------------------------------\n";
    echo 'VAPID_PUBLIC_KEY='.$publicKeyBase64."\n";
    echo 'VAPID_PRIVATE_KEY='.$privateKeyBase64."\n";
    echo "VAPID_SUBJECT=mailto:admin@localhost\n";
    echo "----------------------------------------\n\n";

    echo "🔧 Instructions :\n";
    echo "1. Copiez les 3 lignes ci-dessus dans votre .env\n";
    echo "2. Redémarrez le serveur Laravel\n";
    echo "3. Testez à nouveau les notifications\n\n";

} catch (Exception $e) {
    echo '❌ Erreur : '.$e->getMessage()."\n";

    echo "\n🔄 Alternative - Génération avec une méthode différente...\n";

    try {
        $keys = VAPID::createVapidKeys();

        echo "✅ Nouvelles clés VAPID générées avec succès !\n\n";
        echo "📋 Copiez ces lignes dans votre fichier .env :\n";
        echo "----------------------------------------\n";
        echo 'VAPID_PUBLIC_KEY='.$keys['publicKey']."\n";
        echo 'VAPID_PRIVATE_KEY='.$keys['privateKey']."\n";
        echo "VAPID_SUBJECT=mailto:admin@localhost\n";
        echo "----------------------------------------\n\n";

    } catch (Exception $e2) {
        echo '❌ Erreur alternative : '.$e2->getMessage()."\n";
        echo "\n💡 Solution manuelle :\n";
        echo "Utilisez un générateur en ligne comme https://vapidkeys.com/\n";
    }
}
