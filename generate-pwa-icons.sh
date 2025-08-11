#!/bin/bash

# Script de génération automatique des icônes PWA
# Génère toutes les tailles nécessaires à partir d'une image source

echo "🎨 Génération des icônes PWA..."

# Créer le dossier des icônes s'il n'existe pas
mkdir -p public/images/icons
mkdir -p public/images/screenshots

# Tailles d'icônes nécessaires pour PWA
sizes=(72 96 128 144 152 192 384 512)

# Couleur de fond pour les icônes maskables
background_color="#0d6efd"

echo "📱 Génération des icônes avec ImageMagick..."

# Vérifier si ImageMagick est installé
if ! command -v convert &> /dev/null; then
    echo "❌ ImageMagick n'est pas installé. Installation..."
    sudo apt-get update && sudo apt-get install -y imagemagick
fi

# Créer une icône simple avec le logo de l'app
for size in "${sizes[@]}"; do
    echo "🔧 Génération de l'icône ${size}x${size}..."
    
    # Créer une icône simple avec un carré bleu et du texte
    convert -size ${size}x${size} xc:"$background_color" \
        -font Arial-Bold \
        -pointsize $((size/6)) \
        -fill white \
        -gravity center \
        -annotate 0 "SAAS" \
        public/images/icons/icon-${size}x${size}.png
done

echo "📸 Génération des captures d'écran de démonstration..."

# Créer des captures d'écran de démonstration
convert -size 1280x720 xc:"#f8f9fa" \
    -font Arial-Bold \
    -pointsize 48 \
    -fill "#0d6efd" \
    -gravity center \
    -annotate 0 "Tableau de bord\nGeneric SaaS" \
    public/images/screenshots/desktop-dashboard.png

convert -size 375x812 xc:"#f8f9fa" \
    -font Arial-Bold \
    -pointsize 24 \
    -fill "#0d6efd" \
    -gravity center \
    -annotate 0 "Transactions\nMobiles" \
    public/images/screenshots/mobile-transactions.png

echo "🎯 Génération des icônes de raccourcis..."

# Icône de recharge (vert)
convert -size 96x96 xc:"#28a745" \
    -font Arial-Bold \
    -pointsize 48 \
    -fill white \
    -gravity center \
    -annotate 0 "+" \
    public/images/icons/shortcut-recharge.png

# Icône de retrait (orange)
convert -size 96x96 xc:"#fd7e14" \
    -font Arial-Bold \
    -pointsize 48 \
    -fill white \
    -gravity center \
    -annotate 0 "−" \
    public/images/icons/shortcut-withdrawal.png

# Icône de transactions (bleu)
convert -size 96x96 xc:"#0d6efd" \
    -font Arial-Bold \
    -pointsize 24 \
    -fill white \
    -gravity center \
    -annotate 0 "₿" \
    public/images/icons/shortcut-transactions.png

echo "✅ Icônes PWA générées avec succès !"
echo "📁 Fichiers créés dans :"
echo "   - public/images/icons/"
echo "   - public/images/screenshots/"

# Afficher la liste des fichiers créés
echo ""
echo "📋 Liste des fichiers générés :"
ls -la public/images/icons/
ls -la public/images/screenshots/

echo ""
echo "🚀 Votre PWA est maintenant prête avec toutes les icônes nécessaires !"