---
name: blog_management
description: Guidelines for creating, formatting, and organizing blog posts for the Blogee platform.
---

# Blog Management Skill

## 📂 Content Organization
- **Base Directory**: `content/posts/`
- **Naming Convention**: `YYYY-MM-DD-filename-slug.md`
- **Categories**: Organize posts into subdirectories within `content/posts/`. The directory name will be automatically used as the category in the UI.

## 📝 Markdown Formatting
- **Frontmatter**: Every post MUST start with YAML frontmatter:
  ```yaml
  ---
  title: "A Compelling Title"
  date: "YYYY-MM-DD"
  tags: ["tag1", "tag2"]
  ---
  ```
- **Images**: Use the following path for images: `./assets/filename.png`. 
  - Note: The frontend automatically maps `./assets/` to `/content/assets/`.
- **Assets**: Store all binary files in `content/assets/`.

## 🏷️ Tagging Rules
- Use lowercase, kebab-case for tags (e.g., `llama-cpp`).
- Aim for 2-4 tags per post.
- Include at least one broad topic and one specific tool/technology.

## 📖 README Maintenance
- Keep the README updated with the latest project features.
- Ensure the project roadmap or recent posts section (if any) reflects the current state.
