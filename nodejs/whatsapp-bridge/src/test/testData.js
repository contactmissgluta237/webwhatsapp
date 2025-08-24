/**
 * Données de test pour la messagerie de produits WhatsApp
 * Utilisé uniquement pour les tests et le développement
 */

const TestData = {
    // URLs de médias gratuits pour les tests
    freeMediaUrls: {
        smartphones: [
            "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=500",
            "https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=500",
            "https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=500"
        ],
        
        headphones: [
            "https://images.unsplash.com/photo-1583394838336-acd977736f90?w=500",
            "https://images.unsplash.com/photo-1618366712010-f4ae9c647dcb?w=500",
            "https://images.unsplash.com/photo-1484704849700-f032a568e944?w=500"
        ],
        
        laptops: [
            "https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500",
            "https://images.unsplash.com/photo-1525547719571-a2d4ac8945e2?w=500",
            "https://images.unsplash.com/photo-1541807084-5c52b6b3adef?w=500"
        ],
        
        shoes: [
            "https://images.unsplash.com/photo-1549298916-b41d501d3772?w=500",
            "https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?w=500",
            "https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=500"
        ]
    },

    // Produits de test simulant la réponse Laravel
    sampleProducts: [
        {
            formattedProductMessage: "🛍️ *Smartphone Galaxy Ultra*\n\n💰 **285 000 XAF**\n\n📝 Smartphone dernière génération avec caméra 108MP, écran AMOLED 6.8\", 256GB de stockage. Parfait état, garantie constructeur 2 ans.\n\n📞 Interested? Contact us for more information!",
            mediaUrls: [
                "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=500",
                "https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=500",
                "https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=500"
            ]
        },
        {
            formattedProductMessage: "🛍️ *Casque Audio Premium*\n\n💰 **65 000 XAF**\n\n📝 Casque sans fil avec réduction de bruit active, son Hi-Fi, autonomie 40h. Parfait pour audiophiles et professionnels.\n\n📞 Interested? Contact us for more information!",
            mediaUrls: [
                "https://images.unsplash.com/photo-1583394838336-acd977736f90?w=500",
                "https://images.unsplash.com/photo-1618366712010-f4ae9c647dcb?w=500"
            ]
        },
        {
            formattedProductMessage: "🛍️ *MacBook Pro M3*\n\n💰 **1 200 000 XAF**\n\n📝 Ordinateur portable professionnel, processeur M3, 16GB RAM, 512GB SSD. Idéal pour développement, design, montage vidéo.\n\n📞 Interested? Contact us for more information!",
            mediaUrls: [
                "https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500",
                "https://images.unsplash.com/photo-1525547719571-a2d4ac8945e2?w=500"
            ]
        }
    ],

    // Réponse Laravel simulée pour les tests
    mockLaravelResponse: {
        success: true,
        processed: true,
        session_id: "test_session",
        phone_number: "+237000000000",
        response_message: "Voici quelques produits qui pourraient vous intéresser :",
        wait_time_seconds: 2,
        typing_duration_seconds: 3,
        products: [] // Sera rempli avec sampleProducts lors des tests
    }
};

module.exports = TestData;