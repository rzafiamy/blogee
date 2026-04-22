# Antigravity Global Rules

## 🕵️ Dynamic Skill Discovery
You MUST always discover and apply the most relevant skills for every user request. Do not rely solely on general knowledge when project-specific skills exist.

### Discovery Protocol
1.  **Intercept**: Before responding to a request, scan `.agent/skills/` for relevant domain knowledge.
2.  **Read**: If a relevant skill is found, you MUST use `view_file` on its `SKILL.md` file first.
3.  **Workflow Check**: Check `.agent/workflows/` for any slash commands or documented workflows that automate the requested task.
4.  **Execute**: Implement the solution following the exact formatting, naming, and technical rules found in the skills.

### Key Knowledge Domains
- **Content**: Refer to `blog_management` for paths, frontmatter, and categorization.
- **Design**: Refer to `visual_design` for 8-bit aesthetic and image generation prompts.
- **Operations**: Refer to `deployment` for commit prefixes and publishing scripts.

## 🤖 Adaptive Behavior
- If the user's intent is ambiguous, check the skills to see if a standard procedure exists.
- Always use the `blog:`, `feat:`, `fix:`, etc., commit prefixes as defined in the `deployment` skill.
- Always organize images in `content/assets/` and posts in `content/posts/` as per the `blog_management` skill.
