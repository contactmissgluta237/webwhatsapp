/**
 * Configuration centralisée pour la messagerie de produits WhatsApp
 * Tous les délais, limites et paramètres sont configurables ici
 */

const ProductMessagingConfig = {
    // Délais en millisecondes
    delays: {
        // Délai entre l'envoi de différents médias d'un même produit
        betweenMediaOfSameProduct: 1500, // 1.5 seconde
        
        // Délai entre l'envoi de différents produits
        betweenProducts: 3000, // 3 secondes
        
        // Délai entre le message texte du produit et ses médias
        betweenProductTextAndMedia: 300, // 0.3 seconde
        
        // Délai avant de commencer l'envoi des produits (après la réponse IA)
        beforeStartingProducts: 500, // 0.5 seconde
    },

    // Limites de sécurité
    limits: {
        // Nombre maximum de médias par produit à envoyer
        maxMediaPerProduct: 10,
        
        // Nombre maximum de produits par réponse
        maxProductsPerResponse: 5,
        
        // Taille maximum de l'aperçu des URLs dans les logs (caractères)
        urlPreviewLength: 50,
        
        // Taille maximum de l'aperçu des messages dans les logs (caractères)
        messagePreviewLength: 100,
    },

    // Configuration des logs
    logging: {
        // Activer les logs détaillés pour les produits
        enableDetailedProductLogs: true,
        
        // Activer les logs détaillés pour les médias
        enableDetailedMediaLogs: true,
        
        // Activer les logs de timing/performance
        enableTimingLogs: true,
        
        // Préfixes pour les différents types de logs
        prefixes: {
            product: "🛍️",
            media: "📎",
            timing: "⏱️",
            error: "❌",
            success: "✅",
            warning: "⚠️",
            info: "ℹ️"
        }
    },

    // Gestion des médias
    media: {
        // Télécharger et envoyer les médias comme fichiers (au lieu d'URLs texte)
        downloadAndSendAsFiles: true,
        
        // Timeout pour le téléchargement des médias (millisecondes)
        downloadTimeout: 30000, // 30 secondes
        
        // Taille maximum des fichiers médias (bytes)
        maxFileSizeBytes: 16 * 1024 * 1024, // 16MB (limite WhatsApp)
        
        // Types MIME acceptés pour les médias
        acceptedMimeTypes: [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
            'video/mp4', 'video/webm', 'video/avi',
            'audio/mpeg', 'audio/ogg', 'audio/wav',
            'application/pdf'
        ],
        
        // Envoyer l'URL en texte si le téléchargement échoue (fallback)
        fallbackToUrlOnError: true
    },

    // Gestion des erreurs
    errorHandling: {
        // Continuer l'envoi des autres médias si un média échoue
        continueOnMediaError: true,
        
        // Continuer l'envoi des autres produits si un produit échoue
        continueOnProductError: true,
        
        // Nombre maximum de tentatives pour un média qui échoue
        maxRetryAttempts: 2,
        
        // Délai entre les tentatives en cas d'échec (millisecondes)
        retryDelay: 1000
    },

    // Détection anti-spam
    antiSpam: {
        // Activer la détection anti-spam automatique
        enabled: true,
        
        // Augmenter automatiquement les délais si trop de messages rapides
        autoIncrementDelays: true,
        
        // Facteur de multiplication des délais en cas de détection de spam
        delayMultiplierOnSpamDetection: 2,
        
        // Délai maximum autorisé (sécurité)
        maxAllowedDelay: 10000 // 10 secondes
    }
};

module.exports = ProductMessagingConfig;