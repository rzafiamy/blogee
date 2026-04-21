# Webhook Blog // Modern Retro CMS

A high-performance, headless CMS blog powered by GitHub webhooks and PHP 8.3. This implementation follows the technical specification for a "Webhook-Triggered HTTPS Git Pull" architecture.

## Features
- **Zero Database**: Content is stored as flat Markdown files in GitHub.
- **Auto-Sync**: Production server automatically pulls changes on Every `git push`.
- **Modern Retro Theme**: 8-bit aesthetic with glassmorphism, CRT effects, and premium animations.
- **Secure**: HMAC-SHA256 signature validation for all webhook requests.
- **Fast**: PHP 8.3 optimized and Nginx-ready.

## Project Structure
- `index.html`: The frontend portal.
- `index.css`: Modern Retro 8-bit styles.
- `api.php`: Serves content as JSON.
- `webhook.php`: Handles GitHub sync triggers.
- `content/`: Cloned repository folder (git ignored in development).
    - `posts/`: Your .md blog posts.
    - `assets/`: Images and other media.

## Setup Instructions

### 1. Server Deployment
Clone this project to your Nginx web root.

### 2. Initial Content Setup
On your server, clone your content repository:
```bash
git clone https://github.com/your-username/your-blog-content.git content
chown -R www-data:www-data content
```

### 3. Webhook Configuration
1. Go to your GitHub Repository > Settings > Webhooks.
2. Add a new webhook:
   - **Payload URL**: `https://yourdomain.com/webhook.php`
   - **Content type**: `application/json`
   - **Secret**: Generate a secure token (e.g., `openssl rand -hex 32`)
3. Set the secret in your server environment:
   ```bash
   export GITHUB_WEBHOOK_SECRET='your_secret_token'
   ```
   (Or add it to your PHP-FPM pool config).

### 4. Nginx Configuration
Use the provided `nginx.conf.example` to secure your installation.

## Authoring Posts
Create Markdown files in `posts/` with the following frontmatter:
```markdown
---
title: "My Awesome Post"
date: "2026-04-21"
tags: ["tech", "retro"]
---
Your content here...
```

## Security Analysis
- **Webhook validation**: Uses constant-time comparison to prevent timing attacks.
- **PHP Lockdown**: Nginx rules prevent PHP execution inside the `/content` directory.
- **Git protection**: Public access to `.git` is blocked.
