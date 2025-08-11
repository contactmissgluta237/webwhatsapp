#!/bin/bash

echo "🚀 Test du système d'affiliation..."
echo "=================================="

echo "📋 Tests du flow d'affiliation..."
php artisan test tests/Feature/Referral/CompleteReferralFlowTest.php

if [ $? -eq 0 ]; then
    echo "✅ Tests d'affiliation: SUCCÈS"
else
    echo "❌ Tests d'affiliation: ÉCHECS"
fi

echo ""
echo "🎉 Tests terminés!"
echo "=================================="