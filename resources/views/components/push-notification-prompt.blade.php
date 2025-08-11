@auth
    <!-- Composant de proposition d'activation des notifications push -->
    <div id="push-notification-prompt"
        class="fixed bottom-4 right-4 bg-white shadow-lg rounded-lg border border-gray-200 p-4 w-80 z-50 transform transition-all duration-300 ease-in-out"
        style="display: none;">

        <div class="flex items-start space-x-3">
            <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-5 5v-5zM4 19h6v-2H4v2zM4 15h8v-2H4v2zM4 11h10V9H4v2z" />
                    </svg>
                </div>
            </div>

            <div class="flex-1">
                <h4 class="text-sm font-semibold text-gray-900 mb-1">
                    Activer les notifications
                </h4>
                <p class="text-xs text-gray-600 mb-3">
                    Recevez des notifications importantes directement sur votre appareil.
                </p>

                <div class="flex space-x-2">
                    <button id="enable-notifications"
                        class="bg-blue-600 text-white text-xs px-3 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        Activer
                    </button>
                    <button id="maybe-later-notifications"
                        class="bg-gray-100 text-gray-700 text-xs px-3 py-2 rounded-md hover:bg-gray-200 transition-colors">
                        Plus tard
                    </button>
                    <button id="never-ask-notifications"
                        class="text-gray-500 text-xs px-2 py-2 hover:text-gray-700 transition-colors">
                        Jamais
                    </button>
                </div>
            </div>

            <button id="close-notification-prompt"
                class="flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Message d'erreur pour navigateurs non supportés -->
        <div id="unsupported-browser-message"
            class="mt-3 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800" style="display: none;">
            <strong>Navigateur non compatible :</strong>
            <span id="unsupported-reason"></span>
        </div>
    </div>

    <style>
        #push-notification-prompt.slide-in {
            transform: translateX(0);
        }

        #push-notification-prompt.slide-out {
            transform: translateX(100%);
        }

        @media (max-width: 640px) {
            #push-notification-prompt {
                bottom: 1rem;
                right: 1rem;
                left: 1rem;
                width: auto;
            }
        }
    </style>
@endauth
