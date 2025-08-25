#!/bin/bash

# MyCoolPay Webhook Simulation Script
# Usage: ./scripts/simulate-mycoolpay-webhook.sh <app_transaction_ref> <status>

set -e

# Configuration
WEBHOOK_URL="${APP_URL:-http://localhost:8000}/api/payment/mycoolpay/webhook"
PUBLIC_KEY="${MYCOOLPAY_PUBLIC_KEY:-2dd729e2-b68c-4769-a32b-459041e3e6b8}"
PRIVATE_KEY="${MYCOOLPAY_PRIVATE_KEY:-Pp9x0HWRD6hx0EcB5rWuLLErYMADSKhdStNNCf5EBiW0pfG2EilhILcmSn3Jxd7h}"

# Parse command line arguments
APP_TRANSACTION_REF="${1}"
STATUS="${2:-SUCCESS}"

if [ -z "$APP_TRANSACTION_REF" ]; then
    echo "Usage: $0 <app_transaction_ref> <status>"
    echo ""
    echo "Examples:"
    echo "  $0 txn_1234567890_1 SUCCESS"
    echo "  $0 txn_1234567890_1 FAILED"
    exit 1
fi

# Convertir le status en majuscules pour MyCoolPay
STATUS=$(echo "$STATUS" | tr '[:lower:]' '[:upper:]')

# Get transaction details from app_transaction_ref (on suppose 100 XAF pour le test)
# Dans un vrai cas, tu pourrais query la DB pour récupérer ces infos
AMOUNT=100
CURRENCY="XAF"

# Create webhook payload selon la structure MyCoolPay réelle
PAYLOAD=$(cat <<EOF
{
    "application": "$PUBLIC_KEY",
    "app_transaction_ref": "$APP_TRANSACTION_REF",
    "operator_transaction_ref": "MP$(date +%Y%m%d).$(date +%H%M).A$(shuf -i 10000-99999 -n 1)",
    "transaction_ref": "7602986f-d8ad-43f2-95de-a0ae0034fc7d",
    "transaction_type": "PAYIN",
    "transaction_amount": $AMOUNT,
    "transaction_fees": 2,
    "transaction_currency": "$CURRENCY",
    "transaction_operator": "CM_OM",
    "transaction_status": "$STATUS",
    "transaction_reason": "Account recharge",
    "transaction_message": "Your transaction has been successfully completed",
    "customer_phone_number": "655332183",
    "signature": "will_be_calculated"
}
EOF
)

# Calculate MD5 signature as per MyCoolPay docs
SIGNATURE=$(echo -n "${PRIVATE_KEY}" | md5sum | cut -d' ' -f1)

# Update payload with calculated signature
PAYLOAD=$(echo "$PAYLOAD" | sed "s/\"signature\": \"will_be_calculated\"/\"signature\": \"$SIGNATURE\"/")

echo "=== MyCoolPay Webhook Simulation ==="
echo "Webhook URL: $WEBHOOK_URL"
echo "App Transaction Ref: $APP_TRANSACTION_REF"
echo "Status: $STATUS"
echo "Amount: $AMOUNT $CURRENCY"
echo ""

# Add failure message for failed transactions
if [ "$STATUS" = "FAILED" ]; then
    PAYLOAD=$(echo "$PAYLOAD" | sed 's/"Your transaction has been successfully completed"/"Transaction failed: Insufficient balance"/')
fi

echo "Payload:"
echo "$PAYLOAD" | jq .
echo ""
echo "Signature: $SIGNATURE"
echo ""

# Send webhook request (sans header de signature, elle est dans le payload)
echo "Sending webhook..."
RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$WEBHOOK_URL" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "$PAYLOAD")

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | head -n -1)

echo "HTTP Code: $HTTP_CODE"
echo "Response: $BODY"

if [ "$HTTP_CODE" -eq 200 ]; then
    echo "✅ Webhook simulation successful!"
else
    echo "❌ Webhook simulation failed!"
    exit 1
fi