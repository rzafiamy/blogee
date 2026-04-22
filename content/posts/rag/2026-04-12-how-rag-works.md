---
title: "How RAG Works: Give Your AI a Memory It Can Actually Use"
description: "Retrieval-Augmented Generation explained from first principles — embeddings, vector stores, chunking strategies, and the best tools to build production RAG in 2026."
date: "2026-04-12"
tags: ["rag", "retrieval", "embeddings", "vector-database", "llm"]
---

LLMs have a fundamental limitation: they only know what they were trained on. Ask Claude or GPT-4 about your internal documentation, last week's product changelog, or a PDF uploaded five minutes ago, and they'll either hallucinate or admit ignorance.

**RAG solves this.** Instead of retraining the model every time your data changes, you retrieve relevant context at query time and inject it into the prompt. The model stays static. Your knowledge base stays current.

---

## The Core Concept: Retrieval + Generation

The name says it all. RAG is two systems working together:

1. **Retrieval** — Given a user question, find the most relevant pieces of information from your knowledge base.
2. **Generation** — Give the LLM the question *plus* those retrieved pieces, and let it synthesize a grounded answer.

```
User Question
     │
     ▼
[Embedding Model] ──► Query Vector
                              │
                              ▼
                    [Vector Database] ──► Top-K Chunks
                                                │
                              ┌─────────────────┘
                              ▼
                    [LLM Prompt: Question + Chunks]
                              │
                              ▼
                         Final Answer
```

---

## Step 1: Embeddings — Turning Text into Numbers

The first challenge: how do you search semantically? You can't do keyword search and expect to find "how do I reset my password?" when the doc says "account credential recovery procedure."

The answer is **embeddings** — mathematical representations of text as vectors (arrays of numbers). Similar meanings cluster nearby in vector space. "password reset" and "credential recovery" end up close together.

```python
from openai import OpenAI

client = OpenAI()

def embed(text: str) -> list[float]:
    response = client.embeddings.create(
        input=text,
        model="text-embedding-3-small"  # 1536 dimensions, cheap
    )
    return response.data[0].embedding

query_vector = embed("How do I reset my password?")
# Returns [0.023, -0.041, 0.198, ...] — 1536 numbers
```

Popular embedding models in 2026:
| Model | Dimensions | Best For |
| :--- | :--- | :--- |
| `text-embedding-3-small` (OpenAI) | 1536 | General purpose, low cost |
| `text-embedding-3-large` (OpenAI) | 3072 | Higher accuracy, 5x cost |
| `nomic-embed-text` | 768 | Open-source, runs locally |
| `mxbai-embed-large` | 1024 | Best open-source quality |

---

## Step 2: Chunking — How to Split Your Documents

Before you embed anything, you need to break your documents into chunks. The chunking strategy is often the biggest lever on RAG quality.

**Fixed-size chunking** (simple baseline):
```python
def chunk_text(text: str, chunk_size: int = 512, overlap: int = 64) -> list[str]:
    words = text.split()
    chunks = []
    for i in range(0, len(words), chunk_size - overlap):
        chunk = " ".join(words[i:i + chunk_size])
        chunks.append(chunk)
    return chunks
```

**Recursive semantic chunking** (better): split on paragraph boundaries first, then sentences, then words. Preserve natural thought units.

**Key rules:**
- Chunks should be self-contained and meaningful in isolation
- 256–512 tokens per chunk is the sweet spot for most use cases
- Add overlap (64–128 tokens) to avoid cutting context mid-sentence
- Store metadata with each chunk: source file, page number, section header

---

## Step 3: Vector Databases — Where Your Embeddings Live

Once you have vectors, you need to store and search them efficiently. Enter the vector database.

**Top options in 2026:**

| Tool | Best For | Deployment |
| :--- | :--- | :--- |
| **Qdrant** | Production, best performance | Self-hosted / Cloud |
| **Chroma** | Local dev, prototyping | In-process or server |
| **Weaviate** | Multi-modal, GraphQL API | Self-hosted / Cloud |
| **Pinecone** | Managed, zero infra | Cloud only |
| **pgvector** | You already use Postgres | Postgres extension |

For a quick start with Python:
```python
import chromadb

client = chromadb.Client()
collection = client.create_collection("docs")

# Index your chunks
collection.add(
    documents=["Reset your password via the account settings page.", "Contact support at help@company.com"],
    metadatas=[{"source": "faq.md"}, {"source": "contact.md"}],
    ids=["doc1", "doc2"]
)

# Query
results = collection.query(
    query_texts=["how to reset password"],
    n_results=3
)
print(results["documents"])
```

---

## Step 4: The Full RAG Pipeline

```python
from openai import OpenAI
import chromadb

client = OpenAI()
db = chromadb.Client()
collection = db.get_collection("docs")

def rag_query(question: str) -> str:
    # 1. Retrieve relevant chunks
    results = collection.query(query_texts=[question], n_results=4)
    context = "\n\n".join(results["documents"][0])

    # 2. Generate answer grounded in context
    response = client.chat.completions.create(
        model="gpt-4o",
        messages=[
            {
                "role": "system",
                "content": "Answer using only the context provided. If the answer isn't in the context, say so."
            },
            {
                "role": "user",
                "content": f"Context:\n{context}\n\nQuestion: {question}"
            }
        ]
    )
    return response.choices[0].message.content
```

---

## Advanced RAG Patterns

**Hybrid Search** — Combine vector search with BM25 keyword search. Catches cases where exact terms matter (product codes, names, error codes).

**Re-ranking** — After retrieving top-20 chunks, run a cross-encoder model to re-rank and keep only the top 4. Dramatically improves precision.

**HyDE (Hypothetical Document Embeddings)** — Generate a hypothetical answer to the question first, then embed *that* to search. Bridges the gap between question and answer style.

**Multi-query retrieval** — Generate 3–5 reformulations of the question, retrieve for each, then deduplicate results. Catches different phrasings of the same concept.

---

## Production-Ready Frameworks

- **[LangChain](https://langchain.com)** — Most popular, huge ecosystem. Can be over-engineered for simple cases.
- **[LlamaIndex](https://www.llamaindex.ai)** — Purpose-built for RAG and document intelligence. Excellent for complex document pipelines.
- **[Haystack](https://haystack.deepset.ai)** — Production-grade, especially strong for enterprise search.

---

## The Honest Limits of RAG

RAG is not perfect. It fails when:
- The right answer requires synthesizing information across many chunks
- Documents are poorly structured or chunked badly
- The query is ambiguous
- The knowledge base has contradicting information

For these cases, fine-tuning (or a combination of fine-tuning + RAG) is the answer. But for keeping an LLM grounded in current, private, or large-scale knowledge — RAG is the right tool, and in 2026 it's never been easier to build.

---

*Next: [Why Hugging Face is the Center of the AI Universe](../hugging-face/)*
