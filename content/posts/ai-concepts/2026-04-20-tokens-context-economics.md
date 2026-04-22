---
title: "Tokens, Context Windows, and the Economics of LLMs"
description: "What tokens actually are, how context windows work, why they matter for cost and performance, and how to use them efficiently in your AI applications."
date: "2026-04-20"
tags: ["tokens", "context-window", "cost", "llm", "optimization"]
---

Every time you call an LLM API, two numbers define your bill and your model's capability: **tokens used** and **context window available**. Most developers learn about these the hard way — a surprise invoice or a model that can't "remember" something you told it three messages ago.

This post makes both concrete.

---

## What is a Token?

A token is the basic unit of text that LLMs process. It's not a word, not a character, not a syllable — it's somewhere in between.

Modern LLMs use **Byte-Pair Encoding (BPE)** tokenization. Common words become single tokens; rare or long words get split:

```
"Hello" → ["Hello"]           # 1 token
"tokenization" → ["token", "ization"]  # 2 tokens
"supercalifragilistic" → ["super", "cal", "if", "rag", "il", "istic"]  # 6 tokens
" the" → [" the"]             # 1 token (space included)
"2026-04-21" → ["2026", "-", "04", "-", "21"]  # 5 tokens
```

**The rough rule of thumb**: 1 token ≈ 0.75 words, or about 4 characters in English. A 1,000-word document is approximately 1,300 tokens.

You can count tokens before sending a request:
```python
import tiktoken

enc = tiktoken.encoding_for_model("gpt-4o")
tokens = enc.encode("How many tokens is this sentence?")
print(len(tokens))  # 8

# More practical usage
def count_tokens(text: str, model: str = "gpt-4o") -> int:
    enc = tiktoken.encoding_for_model(model)
    return len(enc.encode(text))
```

For Anthropic models:
```python
import anthropic

client = anthropic.Anthropic()
result = client.beta.messages.count_tokens(
    model="claude-sonnet-4-6",
    messages=[{"role": "user", "content": "How many tokens is this?"}]
)
print(result.input_tokens)
```

---

## The Context Window

The context window is the maximum number of tokens the model can process in a single call — everything in the prompt *plus* the response.

```
┌─────────────────────────────────────────────────────────┐
│                    Context Window (128K tokens)          │
│  ┌──────────────────────────────┐  ┌────────────────┐   │
│  │   Input / Prompt             │  │  Output        │   │
│  │   (system + history + query) │  │  (completion)  │   │
│  └──────────────────────────────┘  └────────────────┘   │
└─────────────────────────────────────────────────────────┘
```

Context window sizes by model (2026):
| Model | Context Window |
| :--- | :--- |
| GPT-4o | 128K tokens |
| Claude Sonnet 4.6 | 200K tokens |
| Gemini 1.5 Pro | 1M tokens |
| Llama 3.1 70B | 128K tokens |
| Mistral Large | 128K tokens |

200K tokens is roughly 150,000 words — an entire book. 1M tokens is a small codebase.

---

## Why Context Window Size Matters

**For conversations:** If your chat history exceeds the context window, older messages get cut off. The model literally can't see what was said earlier.

**For documents:** With a 200K context window, you can send an entire technical manual in a single prompt and ask questions about any part of it.

**For code:** Large context models can ingest an entire codebase and reason about cross-file dependencies.

**Performance note:** Even when content fits in the window, very long contexts can degrade quality. Models tend to pay less attention to content in the middle of very long inputs — the **"lost in the middle"** problem. For critical information, position it at the start or end.

---

## Token Costs — The Real Math

Pricing is always per million tokens (input and output billed separately):

```python
def estimate_cost(
    input_text: str,
    expected_output_words: int,
    model: str = "gpt-4o"
) -> float:
    prices = {
        "gpt-4o":          {"input": 2.50, "output": 10.00},
        "gpt-4o-mini":     {"input": 0.15, "output": 0.60},
        "claude-sonnet-4-6": {"input": 3.00, "output": 15.00},
        "claude-haiku-4-5":  {"input": 0.25, "output": 1.25},
    }
    
    input_tokens = count_tokens(input_text)
    output_tokens = int(expected_output_words * 1.33)  # words to tokens
    
    p = prices[model]
    cost = (input_tokens * p["input"] + output_tokens * p["output"]) / 1_000_000
    return cost

# Example:
cost = estimate_cost(
    input_text="Summarize this 10-page document: " + document_text,
    expected_output_words=200,
    model="gpt-4o-mini"
)
print(f"Estimated cost: ${cost:.4f}")
```

At scale, model choice becomes a major lever. Processing 1 million customer support messages:
- GPT-4o: ~$3,750
- GPT-4o-mini: ~$225
- Claude Haiku: ~$375

The smallest model that meets your quality bar is the right choice for high-volume workloads.

---

## Prompt Caching — The Hidden Cost Saver

Both OpenAI and Anthropic offer **prompt caching**: if the beginning of your prompt is identical across multiple requests, the cached portion is cheaper (often 75–90% discount on cached input tokens).

This is enormously valuable for:
- RAG systems where the system prompt + retrieved docs repeat across queries
- Applications where a long system prompt is constant
- Multi-turn conversations where early context is stable

```python
# Anthropic prompt caching
import anthropic

client = anthropic.Anthropic()

# Mark the static part of the prompt for caching
response = client.messages.create(
    model="claude-sonnet-4-6",
    max_tokens=1024,
    system=[
        {
            "type": "text",
            "text": "You are a customer support agent for Acme Corp.\n\n" + 
                    company_knowledge_base,  # This could be 50K tokens
            "cache_control": {"type": "ephemeral"}  # Cache this prefix
        }
    ],
    messages=[{"role": "user", "content": customer_question}]
)
# First call: full price. Subsequent calls with same prefix: 90% discount on cached tokens
```

---

## Practical Context Management

When building applications, you'll hit context limits. Here's how to handle them:

**Summarize old turns:**
```python
def compress_history(messages: list, threshold: int = 6000) -> list:
    if count_tokens(str(messages)) < threshold:
        return messages
    
    # Summarize older messages
    old_messages = messages[:-4]  # Keep last 4 turns as-is
    summary_response = client.chat.completions.create(
        model="gpt-4o-mini",
        messages=[
            {"role": "user", "content": f"Summarize this conversation history in 200 words:\n{old_messages}"}
        ]
    )
    summary = summary_response.choices[0].message.content
    
    return [
        {"role": "system", "content": f"Previous conversation summary: {summary}"},
        *messages[-4:]
    ]
```

**Sliding window**: Keep only the last N turns.

**Selective retrieval**: Store all history in a vector DB and retrieve the most relevant past turns for each new query.

---

## The Economics in One Sentence

Use the smallest model that meets quality requirements, cache everything you can, and monitor token usage from day one. The difference between a well-optimized and poorly-optimized LLM application at scale is often 10–50x in cost.

---

*Next: [Understanding AI Benchmarks — What MMLU, HumanEval, and the Rest Actually Tell You](../../benchmarks/)*
