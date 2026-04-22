# Webhook Blog // Modern Retro CMS 🕹️

![Modern Retro Blog UI Mockup](/home/cook/.gemini/antigravity/brain/2918e5f7-2ada-4ffd-bd63-18210dbd37a9/modern_retro_blog_ui_1776779979905.png)

A high-performance, headless CMS blog powered by GitHub webhooks and PHP 8.3. This project implements a "Webhook-Triggered HTTPS Git Pull" architecture, allowing for a zero-database, ultra-secure, and automated publishing workflow.

## 🚀 Quick Start (How-To)

### 1. Create the Repositories
You need two repositories on GitHub:
- **Engine Repo** (this one): `https://github.com/rzafiamy/blogee.git`
- **Content Repo**: A separate repo (e.g., `blogee-content`) to store your `.md` posts and assets.

### 2. Server Installation
Clone the engine to your Nginx web root:
```bash
git clone https://github.com/rzafiamy/blogee.git /var/www/blogee
cd /var/www/blogee
```

### 3. Initialize Content
#### Option A: If you have Git (Recommended)
Clone your *Content Repo* into the `content` subdirectory:
```bash
git clone https://github.com/rzafiamy/your-content-repo.git content
sudo chown -R www-data:www-data content
```

#### Option B: If you DON'T have Git (Archive Sync)
If your server lacks `git` or you prefer a lightweight deployment, use the **Archive Sync** method:
1. Create the content directory: `mkdir content`
2. Ensure permissions: `sudo chown -R www-data:www-data content`
3. Configure your `.env` (see Step 4) to use `SYNC_METHOD=archive`.
> [!IMPORTANT]
> This method uses pure PHP (`ZipArchive`). No `curl`, `tar`, or `shell_exec` required! It is designed for maximum compatibility across shared hosting and restricted environments.

---

### 4. Configure Security & Secrets
1. Create a `.env` file (copied from `.env.example`):
   ```bash
   cp .env.example .env
   ```
2. Generate a secure secret: `openssl rand -hex 32`
3. Edit `.env` to include your settings:
   ```env
   GITHUB_WEBHOOK_SECRET=your_generated_secret
   
   # Remote Webhook URL (for the publish.sh script)
   BLOG_WEBHOOK_URL=https://your-domain.com/webhook.php

   # For No-Git Servers:
   SYNC_METHOD=archive
   # Use the GitHub API URL for maximum reliability
   REPO_ARCHIVE_URL=https://api.github.com/repos/USER/REPO/zipball/main
   
   # Optional: Required if your repo is private
   GITHUB_TOKEN=your_personal_access_token
   ```
4. **Permissions**: `chmod 600 .env`

---

### 5. Setup GitHub Webhook
1. Go to your **Content Repo** (or the engine repo if you are using a single-repo structure) on GitHub.
2. **Settings > Webhooks > Add webhook**.
3. **Payload URL**: `https://yourdomain.com/webhook.php`
4. **Content type**: `application/json`
5. **Secret**: The same secret from your `.env` file.
6. **Events**: "Just the push event".

### 6. Nginx Hardening
Apply the configuration from `nginx.conf.example` to your server block to protect your `.env` and `.git` files.

---

## 🛠️ Troubleshooting

### "Sync failed: Error: internal corruption of phar"
This occurs when PHP tries to open an archive with the wrong extension. 
- **Fix**: Ensure your `REPO_ARCHIVE_URL` in `.env` contains the word `zipball`. The engine will automatically handle the file extension.

### "Sync failed: Error: Downloaded an HTML page"
This means GitHub is redirecting the request to a login page (private repo) or an error page.
- **Fix**: 
  1. Use the API URL: `api.github.com/repos/USER/REPO/zipball/main`.
  2. If the repo is private, ensure `GITHUB_TOKEN` is set in `.env`.
  3. Ensure your server allows outbound connections to `api.github.com`.

### "403 Unauthorized"
The HMAC signature verification failed.
- **Fix**: Check that `GITHUB_WEBHOOK_SECRET` is identical in both your `.env` and your GitHub Webhook settings.

---

## 🛡️ Security Architecture

- **Hidden File Protection**: Nginx is configured to block access to all dotfiles (like `.env` and `.git`).
- **HMAC-SHA256 Validation**: Every webhook request is cryptographically signed by GitHub and verified by `webhook.php`.
- **Timing Attack Prevention**: Uses `hash_equals()` for constant-time signature comparison.
- **PHP Lockdown**: PHP execution is disabled inside the `/content` directory.
- **User-Agent Shielding**: The engine specifically identifies as `Blogee-CMS-Sync` to satisfy GitHub's API requirements.

## ✍️ Content Specification

Your blog posts must be in the `posts/` folder of your content repo with YAML frontmatter:

```markdown
---
title: "The Future of 8-Bit Design"
date: "2026-04-21"
tags: ["design", "retro", "2026"]
---
Your markdown content here...

![Sample Image](./assets/image.png)
```

## 🛠️ Technology Stack
- **Backend**: PHP 8.3 (Headless API + Webhook Engine)
- **Frontend**: Vanilla HTML/JS with **Marked.js** for client-side rendering.
- **Theme**: custom CSS "Modern Retro" with glassmorphism and CRT effects.
- **Deployment**: Automated via GitHub Webhooks.

---

## 🏗️ Deployment Checklist
- [ ] Create `blogee` repository on GitHub.
- [ ] Push engine code: `git push -u origin main`.
- [ ] Create Content repository.
- [ ] Setup Nginx with `nginx.conf.example`.
- [ ] Verify `webhook-pull.log` for successful syncs.

---

## ⚖️ Licensing

This project uses a dual-licensing model to protect both the software and the creative content:

- **Source Code**: Licensed under the [Apache License 2.0](LICENSE-CODE). This applies to all PHP, HTML, CSS, and JS files in the engine.
- **Blog Content**: Licensed under the [GNU General Public License v3.0](LICENSE-CONTENT). This applies to all Markdown files and assets within the `content/` directory.
