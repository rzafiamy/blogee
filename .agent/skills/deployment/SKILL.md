---
name: deployment
description: Standardized rules for commits, publishing, and system updates.
---

# Deployment Skill

## 🚢 Commit Rules
All commits must follow the conventional prefix format:
- `blog:` - For new posts or content updates.
- `feat:` - For new frontend/backend features.
- `fix:` - For bug fixes.
- `refactor:` - For code improvements without changing functionality.
- `doc:` - For documentation changes (README, SKILLS).
- `test:` - For adding or updating tests.
- `style:` - For CSS or aesthetic changes.

**Example**: `blog: add post about llama.cpp`

## 🚀 Publishing
- **Script**: Use `./publish.sh` to synchronize changes.
- **Environment**: Ensure `BLOG_WEBHOOK_URL` and `GITHUB_WEBHOOK_SECRET` are set in `.env`.
- **Commands**:
  - `./publish.sh`: Sync to the remote server defined in `.env`.
  - `./publish.sh --push`: Standard workflow (Add, Commit, Push).
  - `./publish.sh --local`: Trigger local webhook test (`http://localhost:8080/webhook.php`).

## 🛠️ System Integrity
- Always check `.env` for the `GITHUB_WEBHOOK_SECRET` before publishing.
- Ensure `index.css` variables are respected when adding new UI elements.
