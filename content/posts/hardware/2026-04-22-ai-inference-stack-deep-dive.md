---
title: "The AI Inference Stack: From Silicon to Intelligence"
description: "A deep dive into the hardware and software layers that power AI inference, from NVIDIA's CUDA ecosystem to the physics of VRAM and quantization techniques."
date: "2026-04-22"
slug: "ai-inference-stack-deep-dive"
tags: ["gpu", "cuda", "llm", "hardware"]
---

![AI Inference Stack](./assets/ai-inference-stack-banner.png)

Hosting Large Language Models (LLMs) locally isn't just about having a fast CPU. It's a complex dance between specialized hardware, drivers, and optimized numerical libraries. In this deep dive, we'll explore exactly what goes into the modern AI inference stack.

## The Hardware Layer: NVIDIA is King

While competitors like AMD and Apple (Metal) are catching up, NVIDIA remains the gold standard for AI inference due to its mature **CUDA** ecosystem.

### Consumer vs. Enterprise
- **RTX Series (3060, 3090, 4090):** These are the workhorses for local AI enthusiasts. The **RTX 3090/4090** are particularly prized for their **24GB of VRAM**, which is the minimum "sweet spot" for running high-quality 70B models with quantization.
- **Enterprise (A100, H100):** These are designed for data centers. With 40GB to 80GB of HBM (High Bandwidth Memory), they handle massive batch sizes and training tasks that consumer cards simply can't touch.

### Why VRAM size is the Bottleneck
VRAM (Video RAM) is where the model lives. Unlike a video game that loads textures as needed, an LLM must have its entire "brain" (weights) loaded into VRAM to respond quickly. If the model doesn't fit in VRAM, it spills over to system RAM, and speeds drop from 50+ tokens/second to a painful 1-2 tokens/second.

## The Software Stack: Connecting Code to Silicon

To talk to the GPU, you need a specific hierarchy of software:

1.  **NVIDIA Drivers:** The base layer allowed the OS to see the hardware.
2.  **CUDA (Compute Unified Device Architecture):** A parallel computing platform.
3.  **nvcc:** The CUDA C++ Compiler. If you are building tools like `llama.cpp` from source, `nvcc` is what translates the code into instructions the GPU understands.
4.  **cuDNN:** A library of primitives for deep neural networks (convolutions, pooling, etc.).

## Inside the LLM: Weights and Attention

What actually takes up all that space?

### Model Weights
An LLM is essentially a massive file of numbers called **parameters**. A 7B model has 7 billion parameters. If stored in 16-bit precision (FP16), each parameter takes 2 bytes. 
*   **Formula:** $7 \text{ billion} \times 2 \text{ bytes} = 14\text{GB of VRAM}$.

### The Attention Mechanism & KV Cache
Beyond the weights, the model needs space to "think." As you type, the model stores a history of the conversation in the **KV Cache** (Key-Value Cache). This consumes additional VRAM proportional to the **Context Window** (the number of tokens the model can "remember").

## Quantization: Squeezing the Brain
Since few people have 140GB of VRAM for a 70B model, we use **Quantization**. This shrinks the precision of weights from 16-bit to 4-bit or 8-bit.
- **4-bit Quantization:** Reduces the size by ~4x with minimal loss in intelligence.
- **Formats:** `GGUF` (for llama.cpp/CPU+GPU), `EXL2` (optimized for pure GPU), and `bitsandbytes`.

## Key Terms for the AI Engineer

- **Token:** The atomic unit of text (usually ~0.75 words).
- **Prompt:** The input instructions you give the model.
- **Context Window:** The maximum memory limit (e.g., 8k, 32k, or 128k tokens).
- **Tool Call:** When a model generates a specific JSON structure to trigger an external function (like searching the web or checking weather).

Understanding this stack is the first step toward moving from "using AI" to "building with AI." Whether you're running a tiny Llama-3-8B or a massive Mixtral-8x22B, the same laws of physics and VRAM apply.
