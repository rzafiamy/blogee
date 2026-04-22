---
description: How to create and publish a new blog post
---

1. Define the topic and target audience.
2. Generate a hero image using the `visual_design` skill.
   - Use `generate_image` with retro pixel art prompts.
   - Move the image to `content/assets/`.
3. Create a Markdown file in `content/posts/` following the `blog_management` naming convention.
4. Add the required frontmatter (title, date, tags).
5. Write the content, ensuring relative paths for images are correct (`./assets/filename.png`).
6. Perform a commit using the `deployment` skill rules (prefix `blog:`).
// turbo
7. Run `./publish.sh --push` to deploy via Git, or simply `./publish.sh` to trigger a remote sync if `BLOG_WEBHOOK_URL` is set in `.env`.
