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
Clone your *Content Repo* into the `content` subdirectory:
```bash
git clone https://github.com/rzafiamy/your-content-repo.git content
sudo chown -R www-data:www-data content
```
> [!IMPORTANT]
> If you don't have a content repo yet, you must at least create the directory structure: `mkdir -p content/posts content/assets` and ensure the web server has write permissions.

### 4. Configure Security & Secrets
... [existing content] ...

---

## 🛠️ Troubleshooting

### "Failed to open directory" / 404 on Posts
If you see a "NO ACCESS LOGS FOUND" message or encounter PHP errors in your logs:
1. **Check Directory Existence**: Verify that `content/posts` exists and contains at least one `.md` file.
2. **Permissions**: Ensure Nginx/PHP-FPM (`www-data`) has read/write access to the `content` folder.
3. **Webhook Logs**: Check `webhook-pull.log` in the root directory for any `git pull` or permission errors.

---

## 🛡️ Security Architecture
... [existing content] ...


- **Hidden File Protection**: Nginx is configured to block access to all dotfiles (like `.env` and `.git`).
- **HMAC-SHA256 Validation**: Every webhook request is cryptographically signed by GitHub and verified by `webhook.php`.
- **Timing Attack Prevention**: Uses `hash_equals()` for constant-time signature comparison.
- **PHP Lockdown**: PHP execution is disabled inside the `/content` directory to prevent malicious uploads from running code.

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
