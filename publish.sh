#!/bin/bash

# Webhook Blog // Publish & Manual Sync Script 🕹️
# This script triggers the webhook.php logic on the server or pushes local changes.

# Configuration
DEFAULT_URL="http://localhost:8080/webhook.php"
DEFAULT_SECRET="PLEASE_CHANGE_ME_TO_A_SECURE_TOKEN"

# 1. Load from .env if available
if [ -f .env ]; then
    # Extract values, remove potential quotes
    SECRET=$(grep '^GITHUB_WEBHOOK_SECRET=' .env | sed 's/^GITHUB_WEBHOOK_SECRET=//' | sed "s/^['\"]//; s/['\"]$//")
    URL_ENV=$(grep '^BLOG_WEBHOOK_URL=' .env | sed 's/^BLOG_WEBHOOK_URL=//' | sed "s/^['\"]//; s/['\"]$//")
fi

SECRET=${SECRET:-$DEFAULT_SECRET}
URL=${URL_ENV:-$DEFAULT_URL}

# Override URL if passed as first argument, unless it's a flag
if [[ -n "$1" && ! "$1" =~ ^-- ]]; then
    URL="$1"
fi

echo "----------------------------------------------------"
echo "🕹️  BLOGEE // PUBLISH COMMAND CENTER"
echo "----------------------------------------------------"

# Mode: Push to GitHub (Standard Workflow)
push_to_git() {
    local msg=${2:-"blog: update content and assets ($(date +'%Y-%m-%d %H:%M'))"}
    echo "📤 STEP 1: Pushing changes to GitHub with message: $msg"
    git add .
    git commit -m "$msg"
    git push origin main
    echo "✅ Changes pushed. GitHub will now trigger the remote webhook."
}

# Mode: Manual Trigger (Force Update on Server)
trigger_webhook() {
    local target_url=$1
    echo "📡 STEP 1: Generating signed payload..."
    
    # Simulate a GitHub push payload
    PAYLOAD='{"ref": "refs/heads/main", "action": "manual_trigger", "timestamp": "'$(date +%s)'"}'
    
    # Generate HMAC-SHA256 signature
    SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | sed 's/^.* //')
    
    echo "🚀 STEP 2: Sending POST request to $target_url..."
    
    RESPONSE=$(curl -s -w "\n%{http_code}" -X POST \
        -H "Content-Type: application/json" \
        -H "X-Hub-Signature-256: sha256=$SIGNATURE" \
        -d "$PAYLOAD" \
        "$target_url")

    STATUS=$(echo "$RESPONSE" | tail -n1)
    BODY=$(echo "$RESPONSE" | head -n -1)

    if [ "$STATUS" -eq 200 ]; then
        echo "✨ SUCCESS ($STATUS): $BODY"
        echo "Check /webhook-pull.log on your server for the git output."
    else
        echo "❌ ERROR ($STATUS): $BODY"
        echo "Verification failed. Check your GITHUB_WEBHOOK_SECRET in .env."
    fi
}

# Display Usage if no arguments provided (or if requested)
case "$1" in
    "--push")
        push_to_git "$@"
        ;;
    "--help"|"-h")
        echo "Usage:"
        echo "  ./publish.sh          : Trigger remote sync using BLOG_WEBHOOK_URL in .env"
        echo "  ./publish.sh --push   : Commit and push changes to GitHub (triggers webhook automatically)"
        echo "  ./publish.sh --local  : Manually trigger local test server (http://localhost:8080/webhook.php)"
        echo "  ./publish.sh <URL>    : Manually trigger a specific remote server"
        ;;
    "--local")
        trigger_webhook "http://localhost:8080/webhook.php"
        ;;
    *)
        # Default behavior: use the loaded URL (from env or argument)
        trigger_webhook "$URL"
        ;;
esac

echo "----------------------------------------------------"
