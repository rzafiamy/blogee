---
title: "Build Your First AI App: From API Key to Production in Python and TypeScript"
description: "A practical, end-to-end walkthrough — set up your environment, call your first LLM, add streaming and tool use, handle errors, and deploy. The guide you wish you had on day one."
date: "2026-04-15"
tags: ["api", "python", "typescript", "tutorial", "openai", "anthropic", "getting-started"]
---

You have an API key and a problem to solve. What now?

This is the guide that takes you from "I have credentials" to "I have a working application" — with real code, practical patterns, and the pitfalls explained upfront. We'll build the same app in both Python and TypeScript/Node.js.

---

## What We're Building

A document Q&A assistant: given a text document, answer questions about it. Simple enough to follow, practical enough to be the foundation of real products (customer support bots, documentation assistants, knowledge bases).

---

## Step 1: Environment Setup

**Python:**
```bash
python -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate
pip install openai anthropic python-dotenv
```

**TypeScript/Node.js:**
```bash
mkdir my-ai-app && cd my-ai-app
npm init -y
npm install openai @anthropic-ai/sdk dotenv
npm install -D typescript @types/node ts-node
npx tsc --init
```

Create a `.env` file — **never hardcode keys in source**:
```
OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...
```

Add `.env` to `.gitignore` immediately. One accidental commit with a live API key can cost thousands before you notice.

---

## Step 2: Your First API Call

**Python:**
```python
# app.py
from openai import OpenAI
from dotenv import load_dotenv

load_dotenv()
client = OpenAI()  # Reads OPENAI_API_KEY from environment

response = client.chat.completions.create(
    model="gpt-4o-mini",
    messages=[
        {"role": "system", "content": "You answer questions clearly and concisely."},
        {"role": "user", "content": "What is an API?"}
    ]
)

print(response.choices[0].message.content)
print(f"\n[{response.usage.total_tokens} tokens used]")
```

**TypeScript:**
```typescript
// src/app.ts
import OpenAI from "openai";
import "dotenv/config";

const client = new OpenAI(); // Reads OPENAI_API_KEY from environment

async function main() {
  const response = await client.chat.completions.create({
    model: "gpt-4o-mini",
    messages: [
      { role: "system", content: "You answer questions clearly and concisely." },
      { role: "user", content: "What is an API?" }
    ]
  });

  console.log(response.choices[0].message.content);
  console.log(`\n[${response.usage?.total_tokens} tokens used]`);
}

main();
```

Run it:
```bash
# Python
python app.py

# TypeScript
npx ts-node src/app.ts
```

You just called an LLM. Now let's make it useful.

---

## Step 3: Document Q&A with RAG

The naive approach — stuff the whole document in the prompt — works for short documents. Let's build it properly:

**Python:**
```python
from openai import OpenAI
from dotenv import load_dotenv

load_dotenv()
client = OpenAI()

def answer_from_document(document: str, question: str) -> str:
    # For documents under ~100K tokens, this direct approach works fine
    # For larger documents, you'd need proper RAG with chunking + vector search
    
    response = client.chat.completions.create(
        model="gpt-4o-mini",
        messages=[
            {
                "role": "system",
                "content": (
                    "You are a document assistant. Answer questions based ONLY on the "
                    "provided document. If the answer isn't in the document, say so clearly. "
                    "Quote the relevant section when helpful."
                )
            },
            {
                "role": "user",
                "content": f"Document:\n\n{document}\n\n---\n\nQuestion: {question}"
            }
        ],
        temperature=0.1  # Low temperature for factual extraction
    )
    return response.choices[0].message.content

# Test it
doc = """
Refund Policy:
- Items can be returned within 30 days of purchase.
- Items must be in original, unopened condition.
- Digital downloads are non-refundable once accessed.
- Shipping costs are non-refundable unless the item was defective.
- To initiate a return, email returns@company.com with your order number.
"""

print(answer_from_document(doc, "Can I return a digital download?"))
print()
print(answer_from_document(doc, "How do I start the return process?"))
print()
print(answer_from_document(doc, "What if I just changed my mind after 45 days?"))
```

**TypeScript:**
```typescript
import OpenAI from "openai";
import "dotenv/config";

const client = new OpenAI();

async function answerFromDocument(document: string, question: string): Promise<string> {
  const response = await client.chat.completions.create({
    model: "gpt-4o-mini",
    messages: [
      {
        role: "system",
        content:
          "You are a document assistant. Answer questions based ONLY on the " +
          "provided document. If the answer isn't in the document, say so clearly."
      },
      {
        role: "user",
        content: `Document:\n\n${document}\n\n---\n\nQuestion: ${question}`
      }
    ],
    temperature: 0.1
  });

  return response.choices[0].message.content ?? "";
}

const doc = `
Refund Policy:
- Items can be returned within 30 days of purchase.
- Digital downloads are non-refundable once accessed.
- Email returns@company.com to initiate a return.
`;

console.log(await answerFromDocument(doc, "Can I return a digital download?"));
```

---

## Step 4: Add Streaming for Better UX

Without streaming, your app shows nothing for 3–10 seconds, then dumps the entire response. With streaming, users see tokens as they're generated — much better.

**Python:**
```python
def answer_streaming(document: str, question: str) -> None:
    stream = client.chat.completions.create(
        model="gpt-4o-mini",
        messages=[
            {"role": "system", "content": "Answer based only on the document provided."},
            {"role": "user", "content": f"Document:\n{document}\n\nQuestion: {question}"}
        ],
        stream=True
    )
    
    for chunk in stream:
        delta = chunk.choices[0].delta.content
        if delta:
            print(delta, end="", flush=True)
    print()  # Final newline

answer_streaming(doc, "What's the refund window?")
```

**TypeScript:**
```typescript
async function answerStreaming(document: string, question: string): Promise<void> {
  const stream = await client.chat.completions.create({
    model: "gpt-4o-mini",
    messages: [
      { role: "system", content: "Answer based only on the document provided." },
      { role: "user", content: `Document:\n${document}\n\nQuestion: ${question}` }
    ],
    stream: true
  });

  for await (const chunk of stream) {
    const delta = chunk.choices[0]?.delta?.content;
    if (delta) process.stdout.write(delta);
  }
  console.log();
}
```

---

## Step 5: Error Handling

LLM APIs will fail. Rate limits, timeouts, invalid inputs — you need to handle these gracefully:

**Python:**
```python
import time
from openai import RateLimitError, APITimeoutError, APIError

def robust_completion(messages: list, model: str = "gpt-4o-mini", retries: int = 3) -> str:
    for attempt in range(retries):
        try:
            response = client.chat.completions.create(
                model=model,
                messages=messages,
                timeout=30  # 30 second timeout
            )
            return response.choices[0].message.content
        
        except RateLimitError:
            if attempt < retries - 1:
                wait = 2 ** attempt  # Exponential backoff: 1s, 2s, 4s
                print(f"Rate limited. Waiting {wait}s...")
                time.sleep(wait)
            else:
                raise
        
        except APITimeoutError:
            print(f"Timeout on attempt {attempt + 1}")
            if attempt == retries - 1:
                raise
        
        except APIError as e:
            print(f"API error: {e.status_code} - {e.message}")
            raise
```

**TypeScript:**
```typescript
import { RateLimitError, APIError } from "openai";

async function robustCompletion(
  messages: OpenAI.Chat.Completions.ChatCompletionMessageParam[],
  retries = 3
): Promise<string> {
  for (let attempt = 0; attempt < retries; attempt++) {
    try {
      const response = await client.chat.completions.create({
        model: "gpt-4o-mini",
        messages
      });
      return response.choices[0].message.content ?? "";
    } catch (error) {
      if (error instanceof RateLimitError && attempt < retries - 1) {
        const wait = Math.pow(2, attempt) * 1000;
        await new Promise((r) => setTimeout(r, wait));
        continue;
      }
      throw error;
    }
  }
  throw new Error("Max retries exceeded");
}
```

---

## Step 6: Cost Monitoring

Add a usage tracker from day one:

**Python:**
```python
from dataclasses import dataclass, field
from datetime import datetime

@dataclass
class UsageTracker:
    sessions: list = field(default_factory=list)
    
    PRICES = {
        "gpt-4o-mini": {"input": 0.15, "output": 0.60},
        "gpt-4o":       {"input": 2.50, "output": 10.00},
    }
    
    def record(self, model: str, input_tokens: int, output_tokens: int):
        p = self.PRICES.get(model, {"input": 1.0, "output": 1.0})
        cost = (input_tokens * p["input"] + output_tokens * p["output"]) / 1_000_000
        self.sessions.append({
            "time": datetime.now().isoformat(),
            "model": model,
            "input_tokens": input_tokens,
            "output_tokens": output_tokens,
            "cost_usd": cost
        })
    
    def total_cost(self) -> float:
        return sum(s["cost_usd"] for s in self.sessions)
    
    def report(self):
        print(f"Total API calls: {len(self.sessions)}")
        print(f"Total cost: ${self.total_cost():.4f}")

tracker = UsageTracker()

# After each API call:
response = client.chat.completions.create(model="gpt-4o-mini", messages=messages)
tracker.record("gpt-4o-mini", response.usage.prompt_tokens, response.usage.completion_tokens)
```

---

## Production Checklist

Before going live:
- [ ] API keys in environment variables, never in code
- [ ] `.env` in `.gitignore`
- [ ] Rate limit handling with exponential backoff
- [ ] Request timeouts set
- [ ] Token usage logging from day one
- [ ] Spending alerts on your API dashboard
- [ ] Input validation before sending to the API
- [ ] System prompt stored separately from application code (easy to iterate)

---

## What's Next

You now have a working AI application and the foundation to extend it. The natural progression:
1. Add a vector database for proper RAG on large document sets
2. Add tool use to let the model query live data
3. Add a web interface (FastAPI + React, or Next.js)
4. Evaluate model outputs systematically before shipping

Every production AI application is built on these same primitives. The complexity comes from what you do with them.

---

*Dive deeper: [Tool Use & Function Calling →](./2026-04-18-tool-use-function-calling.md) | [How RAG Works →](../../rag/)*
