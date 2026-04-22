---
title: "LLM API Endpoints: How to Talk Directly to AI Models"
description: "OpenAI, Anthropic, Google — learn how LLM APIs actually work, what the OpenAI standard means, and how to integrate AI into your Python or TypeScript application today."
date: "2026-04-17"
tags: ["api", "openai", "anthropic", "llm", "python", "typescript"]
---

Every AI product you use — ChatGPT, Claude, Gemini — is built on an API. An endpoint that accepts text, sends it to a model, and returns a response. Once you understand how that API works, you can build the same products yourself.

This post covers the mechanics: the OpenAI-compatible standard, the Anthropic API, authentication, request structure, and practical code in both Python and TypeScript.

---

## The OpenAI Standard — The REST API That Won

OpenAI's Chat Completions API became the de facto standard for LLM APIs. It's simple: a POST request with a list of messages, and you get back a completion.

**Request format:**
```json
POST https://api.openai.com/v1/chat/completions

{
  "model": "gpt-4o",
  "messages": [
    { "role": "system", "content": "You are a helpful assistant." },
    { "role": "user", "content": "Explain gradient descent in one paragraph." }
  ],
  "temperature": 0.7,
  "max_tokens": 256
}
```

**Response format:**
```json
{
  "id": "chatcmpl-abc123",
  "object": "chat.completion",
  "model": "gpt-4o",
  "choices": [{
    "message": {
      "role": "assistant",
      "content": "Gradient descent is an optimization algorithm..."
    },
    "finish_reason": "stop"
  }],
  "usage": {
    "prompt_tokens": 38,
    "completion_tokens": 64,
    "total_tokens": 102
  }
}
```

The message array is the **context window** — everything the model "sees." You append each turn to build a conversation.

---

## Python: OpenAI SDK

```python
from openai import OpenAI

client = OpenAI(api_key="sk-...")  # Or set OPENAI_API_KEY env var

response = client.chat.completions.create(
    model="gpt-4o-mini",
    messages=[
        {"role": "system", "content": "You are a concise technical writer."},
        {"role": "user", "content": "What is tokenization in NLP?"}
    ],
    temperature=0.3,
    max_tokens=200
)

print(response.choices[0].message.content)
print(f"Used {response.usage.total_tokens} tokens")
```

**Streaming** (see tokens as they arrive):
```python
stream = client.chat.completions.create(
    model="gpt-4o-mini",
    messages=[{"role": "user", "content": "Write a haiku about Python."}],
    stream=True
)

for chunk in stream:
    if chunk.choices[0].delta.content:
        print(chunk.choices[0].delta.content, end="", flush=True)
```

---

## TypeScript/Node.js: OpenAI SDK

```typescript
import OpenAI from "openai";

const client = new OpenAI({ apiKey: process.env.OPENAI_API_KEY });

async function chat(userMessage: string): Promise<string> {
  const response = await client.chat.completions.create({
    model: "gpt-4o-mini",
    messages: [
      { role: "system", content: "You are a helpful assistant." },
      { role: "user", content: userMessage }
    ],
    temperature: 0.7,
    max_tokens: 512
  });

  return response.choices[0].message.content ?? "";
}

// Streaming in TypeScript
async function chatStream(userMessage: string): Promise<void> {
  const stream = await client.chat.completions.create({
    model: "gpt-4o-mini",
    messages: [{ role: "user", content: userMessage }],
    stream: true
  });

  for await (const chunk of stream) {
    const delta = chunk.choices[0]?.delta?.content;
    if (delta) process.stdout.write(delta);
  }
}
```

---

## The Anthropic API — Claude's Native Interface

Anthropic's API has a slightly different structure. The main difference: the `system` prompt is a top-level parameter, not a message in the array.

```python
import anthropic

client = anthropic.Anthropic(api_key="sk-ant-...")

message = client.messages.create(
    model="claude-sonnet-4-6",
    max_tokens=1024,
    system="You are an expert in machine learning. Be precise and cite examples.",
    messages=[
        {"role": "user", "content": "What's the difference between precision and recall?"}
    ]
)

print(message.content[0].text)
print(f"Input tokens: {message.usage.input_tokens}")
print(f"Output tokens: {message.usage.output_tokens}")
```

**TypeScript with Anthropic:**
```typescript
import Anthropic from "@anthropic-ai/sdk";

const client = new Anthropic({ apiKey: process.env.ANTHROPIC_API_KEY });

const message = await client.messages.create({
  model: "claude-sonnet-4-6",
  max_tokens: 1024,
  system: "You are a helpful coding assistant.",
  messages: [
    { role: "user", content: "Explain async/await in JavaScript." }
  ]
});

console.log(message.content[0].type === "text" ? message.content[0].text : "");
```

---

## The OpenAI-Compatible Standard — One SDK to Rule Them All

Many providers implement the OpenAI API format, meaning you can use the OpenAI SDK with minimal changes:

```python
from openai import OpenAI

# Use Mistral via OpenAI-compatible endpoint
client = OpenAI(
    api_key="your-mistral-key",
    base_url="https://api.mistral.ai/v1"
)

# Use a local model via Ollama
client = OpenAI(
    api_key="ollama",  # Any non-empty string
    base_url="http://localhost:11434/v1"
)

# Use Groq (ultra-fast inference)
client = OpenAI(
    api_key="your-groq-key",
    base_url="https://api.groq.com/openai/v1"
)

# All use identical call syntax:
response = client.chat.completions.create(
    model="mistral-large-latest",  # or "llama3.2:3b", or "llama-3.1-70b-versatile"
    messages=[{"role": "user", "content": "Hello!"}]
)
```

This portability is enormously valuable. Write once, switch providers by changing two lines.

---

## Key Parameters Explained

| Parameter | What It Controls | Typical Values |
| :--- | :--- | :--- |
| `temperature` | Randomness/creativity | 0.0 (deterministic) to 1.0 (creative) |
| `max_tokens` | Maximum response length | 256–4096 depending on task |
| `top_p` | Nucleus sampling — alternate to temperature | Usually leave at 1.0 |
| `frequency_penalty` | Reduces word repetition | 0.0–1.0 |
| `stop` | Stop sequences — end generation on these strings | `["\n\n", "###"]` |
| `stream` | Return tokens as they're generated | `true` for UI, `false` for batch |

**Rule of thumb:** For factual tasks, use `temperature=0.1–0.3`. For creative tasks, use `0.7–1.0`. Never set `max_tokens` too low or you'll get cut-off responses.

---

## Building a Conversation

The API is stateless — it doesn't remember previous requests. You maintain history yourself:

```python
conversation = []

def chat(user_input: str) -> str:
    conversation.append({"role": "user", "content": user_input})
    
    response = client.chat.completions.create(
        model="gpt-4o-mini",
        messages=conversation
    )
    
    assistant_message = response.choices[0].message.content
    conversation.append({"role": "assistant", "content": assistant_message})
    
    return assistant_message

# Multi-turn conversation
print(chat("What is a transformer in ML?"))
print(chat("How does the attention mechanism work in that?"))
print(chat("Give me a simple Python example."))
```

---

## Cost Awareness

LLM API calls cost money. Every token in and out is billed. Approximate rates in 2026:

| Model | Input (per 1M tokens) | Output (per 1M tokens) |
| :--- | :--- | :--- |
| GPT-4o | $2.50 | $10.00 |
| GPT-4o-mini | $0.15 | $0.60 |
| Claude Sonnet 4.6 | $3.00 | $15.00 |
| Claude Haiku 4.5 | $0.25 | $1.25 |
| Mistral Large | $2.00 | $6.00 |

**Practical tips:**
- Use smaller models for classification, routing, and simple tasks
- Cache identical prompts (Anthropic and OpenAI offer prompt caching)
- Log your token usage from the start — surprises are expensive
- Set spending limits on your API dashboard before you forget

---

*Next: [Understanding AI Benchmarks — What the Numbers Actually Mean](../../benchmarks/)*
