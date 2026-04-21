# 📜 Blog Content Rules

This document outlines the mandatory rules for creating and organizing content for the **HUMANITY LAST BLOG**. Following these rules ensures your posts are correctly parsed by the system and displayed with the "8-Bit Retro" aesthetic.

---

## 📂 Directory Structure

All content must reside in the content repository (cloned into the `content/` directory of the engine):

- `/posts/`: Place all your Markdown (`.md`) files here.
- `/assets/`: Place all images, videos, and downloadable files here.

---

## 🏷️ Categories & Tags

The system distinguishes between **Categories** (structural) and **Tags** (descriptive):

1. **Category**: Automatically determined by the **folder path** inside `/posts/`.
   - `posts/dev/my-post.md` → Category: **dev**
   - `posts/tech/moe/my-post.md` → Category: **tech / moe**
   - Files in the root of `/posts/` are assigned to **Uncategorized**.
2. **Tags**: Defined in the frontmatter `tags` array. These are used for internal metadata and system-wide indexing.

---

## 📝 Creating a Post

### 1. Filename & Location
Files must be saved in the `posts/` directory. You can create deeply nested folders to generate subcategories/recursive categories.

**Path:** `posts/[category]/[subcategory]/YYYY-MM-DD-filename.md`  
**Example:** `posts/tech/ai/2026-04-21-agent-logic.md`

### 2. Mandatory Frontmatter
Every post **must** start with a YAML frontmatter block.

```markdown
---
title: "Your Post Title"
date: "YYYY-MM-DD"
tags: ["internal-tag1", "internal-tag2"]
---
```

| Field | Requirement | Description |
| :--- | :--- | :--- |
| `title` | **Mandatory** | The heading displayed for your post. |
| `date` | **Mandatory** | Format: `YYYY-MM-DD`. Used for sorting. |
| `tags` | **Mandatory** | An array of strings for descriptive metadata. |

---

## 🖼️ Including Media & Files

To keep paths portable, always use the relative path `./assets/` when referencing media.

### 1. Images
`![Alt Text](./assets/your-image.png)`

### 2. Files (PDFs, Downloads)
`[Download Technical Specs](./assets/specs.pdf)`

### 3. Videos
Use standard HTML5 within your Markdown file:

```html
<video width="100%" controls>
  <source src="./assets/demo.mp4" type="video/mp4">
  SYSTEM ERROR: Video playback not supported.
</video>
```

---

## 🛠️ Style Guidelines

- **Headings**: Use `##` and `###` for subheadings.
- **Code Blocks**: Use triple backticks (```) followed by the language name.
- **Excerpts**: The first 180 characters are used for the home feed.
