---
description: Project maintenance and metadata updates
---

1. Review `content/posts/` for any missing tags or incorrect dates.
2. Update `README.md` if new features were added to the engine or UI.
3. Check `.agent/skills/` to ensure instructions remain accurate to the current codebase.
4. Perform a cleanup of unused assets in `content/assets/`.
5. Use the `deployment` skill rules (prefix `doc:` or `refactor:`) for maintenance commits.
// turbo
6. Use `git push` or `./publish.sh --push` to sync maintenance changes.
