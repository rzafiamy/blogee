# Blogee API Documentation 🕹️

Welcome to the **Blogee API**. This document explains how external tools can interact with the Blogee platform to read content or trigger synchronization (publishing).

## 📡 Base URLs
- **Read API**: `https://your-domain.com/api.php`
- **Publish/Sync API**: `https://your-domain.com/webhook.php`
- **RSS Feed**: `https://your-domain.com/rss.php`

---

## 📖 Reading Content

### [GET] Fetch All Posts
Retrieve a complete list of all blog posts in JSON format.

**Endpoint:** `/api.php`

**Response Format:**
```json
[
  {
    "id": "md5_hash_of_filename",
    "slug": "post-slug",
    "path": "category/2026/04/22/post-slug",
    "title": "Post Title",
    "description": "A compelling hook to attract readers...",
    "date": "2026-04-22",
    "category": "Category Name",
    "tags": ["tag1", "tag2"],
    "rawMarkdown": "Post content in markdown format..."
  }
]
```

**Fields:**
| Field | Type | Description |
| :--- | :--- | :--- |
| `id` | String | Unique MD5 hash based on the filename. |
| `slug` | String | URL-friendly identifier. |
| `path` | String | Hierarchical path used for frontend routing. |
| `title` | String | Title extracted from YAML frontmatter. |
| `description` | String | A compelling short hook/accroche for the post. |
| `date` | String | ISO date (YYYY-MM-DD). |
| `category`| String | Directory name where the post is stored. |
| `tags` | Array | List of tags from frontmatter. |
| `rawMarkdown`| String | The body of the post (excluding frontmatter). |

> [!NOTE]
> The API returns all posts sorted by date (descending). Clients should filter the results locally to find specific posts or categories.

---

## 🚀 Publishing & Synchronization

Publishing in Blogee works by triggering the server to sync (pull) content from its primary source (GitHub or a Zip Archive).

### [POST] Trigger Sync
Triggers the `webhook.php` script to refresh the `content/` directory.

**Endpoint:** `/webhook.php`

**Headers:**
- `Content-Type: application/json`
- `X-Hub-Signature-256: sha256={hmac_hash}` (Required)

**Payload:**
To trigger a manual sync, send:
```json
{
  "action": "manual_trigger",
  "ref": "refs/heads/main"
}
```

### 🔐 Authentication
Requests to `/webhook.php` MUST be signed using a HMAC-SHA256 hash of the JSON payload, using the `GITHUB_WEBHOOK_SECRET` found in your `.env` file as the key.

#### Implementation Example (Bash)
```bash
SECRET="your_secret_here"
PAYLOAD='{"action": "manual_trigger"}'
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | sed 's/^.* //')

curl -X POST \
     -H "Content-Type: application/json" \
     -H "X-Hub-Signature-256: sha256=$SIGNATURE" \
     -d "$PAYLOAD" \
     https://your-domain.com/webhook.php
```

#### Implementation Example (Python)
```python
import hmac
import hashlib
import requests
import json

secret = b"your_secret_here"
payload = {"action": "manual_trigger"}
payload_bytes = json.dumps(payload).encode('utf-8')

signature = hmac.new(secret, payload_bytes, hashlib.sha256).hexdigest()

headers = {
    "Content-Type": "application/json",
    "X-Hub-Signature-256": f"sha256={signature}"
}

response = requests.post("https://your-domain.com/webhook.php", data=payload_bytes, headers=headers)
print(response.status_code, response.text)
```

---

## 📝 Content Structure

External tools should prepare content as Markdown files in the following format:

**File Location:** `content/posts/{category}/{slug}.md`

**Format:**
```markdown
---
title: "Your Post Title"
date: "YYYY-MM-DD"
tags: ["example", "api"]
---
Your markdown content starts here.

Images should be stored in `content/assets/` and referenced as:
![Description](./assets/image-name.png)
```

---

## 📡 RSS Feed

The `/rss.php` endpoint provides a standard RSS 2.0 feed compatible with readers like Feedly or NetNewsWire.

- **URL**: `https://your-domain.com/rss.php`
- **Method**: `GET`
- **Returns**: XML document
