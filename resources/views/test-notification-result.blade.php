<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultat du test de notification</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
    <meta name="user-id" content="{{ auth()->id() }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full mx-4">
        @if($success ?? false)
            <div class="text-center">
                <div class="text-6xl mb-4">🧪</div>
                <h1 class="text-2xl font-bold text-green-600 mb-4">{{ $message }}</h1>
                
                <div class="text-left bg-gray-50 rounded-lg p-4 mb-6">
                    <h3 class="font-semibold text-gray-800 mb-2">Détails :</h3>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li><strong>Utilisateur :</strong> {{ $details['user'] }}</li>
                        <li><strong>Base de données :</strong> {{ $details['database_notification'] }}</li>
                        <li><strong>Push :</strong> {{ $details['push_notifications'] }}</li>
                        <li><strong>Heure :</strong> {{ $details['timestamp'] }}</li>
                    </ul>
                </div>

                <div class="mb-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Gestion des notifications push :</h3>
                    <x-push-notification-button />
                </div>

                <div class="space-y-2">
                    <button onclick="window.location.reload()" 
                            class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                        🔄 Tester à nouveau
                    </button>
                    
                    <button onclick="window.history.back()" 
                            class="w-full px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors duration-200">
                        ← Retour
                    </button>
                </div>
            </div>
        @else
            <div class="text-center">
                <div class="text-6xl mb-4">❌</div>
                <h1 class="text-2xl font-bold text-red-600 mb-4">Erreur</h1>
                <p class="text-gray-600 mb-4">{{ $message }}</p>
                
                @if(isset($error))
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-left">
                        <strong class="text-red-800">Détail de l'erreur :</strong>
                        <p class="text-red-600 text-sm mt-1 font-mono">{{ $error }}</p>
                    </div>
                @endif

                <button onclick="window.history.back()" 
                        class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors duration-200">
                    ← Retour
                </button>
            </div>
        @endif
    </div>

    <script src="{{ asset('js/push-notifications.js') }}"></script>
</body>
</html>
