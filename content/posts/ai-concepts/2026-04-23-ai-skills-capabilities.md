---
title: "AI Skills: How Models Learn Capabilities and What They Can Really Do"
description: "What does it mean for an AI to have a 'skill'? Understand how capabilities emerge from training, what in-context learning is, and the real limits of what LLMs know how to do."
date: "2026-04-23"
tags: ["ai-skills", "capabilities", "emergent-behavior", "in-context-learning", "llm"]
---

When someone says "Claude is good at coding" or "GPT-4 has strong reasoning skills," what do they actually mean? What is a "skill" in the context of a language model, and where does it come from?

This post cuts through the abstraction and explains how AI capabilities work — from training, to emergent behaviors, to how you can steer and extend them at runtime.

---

## Skills Are Not Installed — They're Learned

A traditional software program has capabilities because a developer wrote them. A language model has capabilities because they were learned from patterns in training data. There's no "coding module" or "translation function" hard-coded inside GPT-4. The ability to write Python code emerged from exposure to millions of code files, documentation pages, Stack Overflow answers, and tutorials.

This has a profound implication: **the model's capabilities are a reflection of its training data's coverage and quality.** A model trained mostly on English web text will be worse at Japanese than at English, not because anyone decided that, but because there was less Japanese in the training data.

---

## Emergent Capabilities

Some capabilities were not explicitly trained for — they appeared at sufficient model scale. Researchers call these **emergent capabilities**: behaviors that are essentially absent at small scale and appear suddenly as the model gets larger.

Examples documented across model generations:
- **Multi-step arithmetic** — Small models fail; larger models solve reliably
- **Chain-of-thought reasoning** — Models can reason step-by-step without being trained on step-by-step examples
- **In-context learning** — See a few examples in the prompt, generalize the pattern to new inputs
- **Code execution simulation** — Mentally "run" simple code and predict output

The mechanism isn't fully understood. One hypothesis: at sufficient scale, the model develops internal representations that support more abstract computation. Another: scale increases the diversity of training examples, enabling better generalization.

What matters practically: **you can't always predict what a model can do by looking at smaller versions.**

---

## In-Context Learning — Skills on Demand

One of the most powerful and practical model capabilities is **in-context learning (ICL)**: the model learns how to perform a new task from examples given in the prompt, without any weight updates.

```python
# Zero-shot: no examples, rely on pre-trained knowledge
prompt = "Classify the sentiment of: 'The product broke after two days.'"
# → Model uses general understanding of sentiment

# Few-shot: provide examples, model learns the format and criteria
prompt = """
Classify product reviews as POSITIVE, NEGATIVE, or NEUTRAL.

Review: "Arrived on time, exactly as described." → POSITIVE
Review: "Stopped working after one week." → NEGATIVE
Review: "It's okay, nothing special." → NEUTRAL

Review: "The product broke after two days." →
"""
# → Model follows the pattern and format you demonstrated
```

This is enormously useful in practice. You can define new classification schemes, custom output formats, domain-specific terminology, or specialized judgment criteria — all through examples in the prompt, no fine-tuning required.

**Chain-of-thought prompting** is a variant: include examples that show reasoning steps, and the model will produce reasoning steps for new inputs:

```
Q: A store has 15 apples. They sell 7 and receive a new shipment of 12. How many apples?
A: Start with 15. Sell 7: 15 - 7 = 8. Receive 12: 8 + 12 = 20. Answer: 20 apples.

Q: A library has 240 books. They loan out 55 and receive 30 returns. How many books?
A: [Model produces step-by-step reasoning before answering]
```

The key insight: the model already knows *how* to reason — you're activating that capability through the prompt structure.

---

## The Skills Taxonomy

A useful way to think about what LLMs can and can't do:

### Core Linguistic Skills (Very Strong)
- Text generation, summarization, paraphrasing
- Translation across languages
- Grammar correction and style editing
- Named entity recognition

### Reasoning Skills (Strong, Model-Dependent)
- Multi-step logical deduction
- Mathematical problem solving
- Causal reasoning ("if X then Y because...")
- Analogical reasoning

### Knowledge Application (Strong but Bounded by Training Cutoff)
- Factual Q&A from training data
- Domain expertise synthesis
- Historical context and explanation

### Instruction Following (Variable by Model)
- Format adherence (JSON, tables, specific structures)
- Constraint satisfaction (word limits, tone requirements)
- Complex multi-part instruction parsing

### Tool-Augmented Skills (Requires External Tools)
- Real-time information (needs search/web access)
- File I/O (needs filesystem tools)
- Computation precision (needs code execution)
- Long-term memory (needs vector database)

---

## The Hallucination Problem

Understanding skills means understanding their limits. The most consequential limit is **hallucination**: the model generates confident, plausible-sounding text that is factually wrong.

This happens because LLMs are trained to produce *fluent, coherent text* — not to produce *true* text. They don't have a truth-checking mechanism. When the correct answer isn't strongly represented in training data, the model pattern-matches to something plausible instead.

Practical implications:
- Never trust model output for specific facts without verification
- Hallucination rates vary significantly by domain (less common for well-represented topics)
- RAG systems dramatically reduce hallucination by grounding answers in retrieved documents
- Models with extended thinking / chain-of-thought reason better and hallucinate less on complex tasks

---

## Extended Thinking — Reasoning as a Skill

Claude Sonnet 4.6 and newer models support **extended thinking**: a mode where the model writes out an internal scratchpad of reasoning before producing a final answer. This is different from regular chain-of-thought in that the thinking is a genuine internal state, not just formatting.

```python
import anthropic

client = anthropic.Anthropic()

response = client.messages.create(
    model="claude-sonnet-4-6",
    max_tokens=16000,
    thinking={
        "type": "enabled",
        "budget_tokens": 10000  # Max tokens for thinking
    },
    messages=[{
        "role": "user",
        "content": "A bat and ball cost $1.10 total. The bat costs $1.00 more than the ball. How much does the ball cost?"
    }]
)

for block in response.content:
    if block.type == "thinking":
        print("Internal reasoning:", block.thinking[:200], "...")
    elif block.type == "text":
        print("Answer:", block.text)
# Answer: 5 cents (not 10 — extended thinking gets the Kahneman System 1 trap right)
```

Extended thinking is particularly valuable for math, logic puzzles, complex planning, and multi-step code reasoning — tasks where the initial "intuitive" response is often wrong.

---

## How to Leverage Model Skills Effectively

**Match task to model strength:**
- Structured extraction, classification, formatting → small, fast models
- Complex reasoning, nuanced judgment → frontier models with extended thinking
- Real-time knowledge → RAG + any capable model
- Consistent style/domain behavior → fine-tuned model

**Prompt for the skill you need:**
- Want careful reasoning? Ask for it: "Think step by step before answering."
- Want format compliance? Show an example, don't just describe it.
- Want expertise? Assign a role: "You are a senior security auditor..."

**Know the limits:**
- Long multiplication without a calculator: unreliable
- Events after training cutoff: impossible without tools
- Counting characters in a string: surprisingly bad without code execution
- Consistent behavior across a 10,000-message conversation: degrades over time

---

## The Honest Picture

LLMs are remarkable at synthesizing and applying knowledge from training data. They're unreliable at computation, real-time information, and tasks that require true working memory. They're improving rapidly but are not magic.

The best builders in this space know exactly what these systems are good at, design around their weaknesses, and augment with tools where needed. That's the job.

---

*Start from the beginning: [Understanding Fine-Tuning](../../finetuning/)*
