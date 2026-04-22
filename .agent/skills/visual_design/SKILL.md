---
name: visual_design
description: Rules for generating assets compatible with the Blogee retro 8-bit design.
---

# Visual Design Skill

## 🎨 Aesthetic Guidelines
- **Theme**: "Modern Retro" 8-bit.
- **Palette**: Muted terracotta, cream, and deep charcoal. 
- **Style**: Pixel art, chunky UI elements, high-tech/cybernetic combined with retro computing vibes.

## 🖼️ Image Generation
When using `generate_image`, always include keywords to ensure compatibility:
- **Style Keywords**: `8-bit pixel art`, `retro aesthetic`, `muted vintage colors`, `isometric pixel art`, `vibrant neon accents`.
- **Composition**: Keep subjects centered and clean. Avoid complex realistic textures.
- **Resolution**: Use `1024x1024` but prompt for "pixelated" or "low-fidelity" look to match the site's CSS.

## 📁 Storage
- Always move generated images from the brain directory to `content/assets/`.
- Use descriptive, kebab-case filenames (e.g., `llama-cpp-banner.png`).
