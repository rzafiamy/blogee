---
title: "AI Agents: Architectures, Memory, and How They Actually Work"
description: "From ReAct to multi-agent systems — understand the architectures that power autonomous AI agents, their memory systems, and the patterns used in production."
date: "2026-04-19"
tags: ["agents", "llm", "architecture", "memory", "multi-agent"]
---

"AI agent" is used for everything from a chatbot with memory to a fully autonomous system that plans week-long projects and executes them without human input. The term is genuinely broad. But the underlying architectures are well-defined, and understanding them will help you build — or critically evaluate — anything called an "agent."

---

## The Minimal Agent

At its core, an agent is just an LLM in a loop:

```python
def agent_loop(goal: str, tools: list) -> str:
    memory = [{"role": "user", "content": goal}]
    
    while True:
        response = llm.call(messages=memory, tools=tools)
        memory.append(response.message)
        
        if response.done:
            return response.final_answer
        
        for tool_call in response.tool_calls:
            result = execute(tool_call)
            memory.append(tool_result(tool_call.id, result))
```

That's it. The model decides what to do, your framework executes it, the result feeds back into the next decision. The sophistication in modern agents comes from what surrounds this loop: memory systems, planning layers, multi-agent orchestration, and guardrails.

---

## The ReAct Pattern — Reason Then Act

**ReAct** (Reasoning + Acting) is the most influential agent pattern. Instead of going straight to tool calls, the model first outputs explicit reasoning, then decides on an action.

```
Thought: The user wants to know the population of the 3 largest cities in France.
         I should search for this information.

Action: search("largest cities in France by population")

Observation: Paris: 2.1M, Marseille: 868K, Lyon: 516K

Thought: I have the data for all three cities. I can now answer.

Answer: The three largest cities in France are Paris (2.1M), Marseille (868K), and Lyon (516K).
```

The explicit `Thought:` step dramatically improves reliability. The model is forced to articulate its reasoning before acting, which catches errors that would otherwise silently propagate.

In practice, this is implemented via system prompts that instruct the model to use this format, or by structured output parsing.

---

## Memory Systems

This is where agent design gets interesting. A stateless LLM call has no memory between turns. Agents need multiple types of memory:

### 1. In-Context Memory (Short-Term)
The conversation history in the current context window. Everything the agent has done in this session. Limited by context length (128K–1M tokens in 2026 models).

### 2. External Memory (Long-Term)
A database the agent can read from and write to across sessions. Usually a vector database for semantic search:

```python
class AgentMemory:
    def __init__(self):
        self.vector_db = VectorDatabase()
    
    def remember(self, content: str, metadata: dict):
        embedding = embed(content)
        self.vector_db.store(embedding, content, metadata)
    
    def recall(self, query: str, top_k: int = 5) -> list[str]:
        query_embedding = embed(query)
        return self.vector_db.search(query_embedding, top_k)
```

### 3. Episodic Memory
Records of past agent runs — what worked, what failed. Used to improve future performance without retraining.

### 4. Procedural Memory
Skills and workflows the agent has learned to apply reliably. Often implemented as few-shot examples in the system prompt.

---

## Planning Architectures

For complex tasks, agents need to plan before acting.

### Linear Plans
The simplest form — generate an ordered list of steps, execute each in sequence:
```
Plan:
1. Search for recent papers on LLM reasoning
2. Identify the top 3 by citation count
3. Read abstracts of each
4. Write a 200-word summary comparing their approaches
```

### Tree of Thought
Generate multiple possible next steps, evaluate each, and explore the most promising branch. More compute-intensive but handles ambiguous problems better.

### MCTS (Monte Carlo Tree Search)
Used in the most sophisticated agents: simulate multiple possible futures, backpropagate value estimates, and commit to the highest-value path. This is how AlphaGo-style reasoning is being applied to text agents.

---

## Multi-Agent Systems

Single agents hit limits: context window overflow, task complexity, the need for parallelism. Multi-agent architectures solve this by distributing work.

### Orchestrator-Worker Pattern
An orchestrator agent breaks down the task, assigns subtasks to specialized worker agents, and synthesizes results:

```python
class OrchestratorAgent:
    def __init__(self):
        self.workers = {
            "researcher": ResearchAgent(),
            "writer": WritingAgent(),
            "critic": ReviewAgent()
        }
    
    async def execute(self, goal: str) -> str:
        # Plan the work
        plan = self.plan(goal)
        
        # Execute subtasks in parallel where possible
        results = await asyncio.gather(*[
            self.workers[task.agent].run(task.prompt)
            for task in plan.independent_tasks
        ])
        
        # Synthesize
        return self.synthesize(results, plan.sequential_tasks)
```

### Specialized Agent Teams
Different agents with different system prompts, tools, and even different base models optimized for their role:
- **Research agent**: web search tools, long context model
- **Code agent**: code execution tools, coding-optimized model
- **Critic agent**: no tools, focused on finding flaws
- **Planner agent**: structured output, reasoning-optimized model

### Agent Communication
Agents pass structured messages — not freeform text, but typed interfaces that prevent misinterpretation and enable validation:

```python
class TaskResult(BaseModel):
    status: Literal["success", "failure", "partial"]
    output: str
    confidence: float  # 0.0–1.0
    sources: list[str]
    next_suggested_action: str | None
```

---

## Guardrails and Safety

Autonomous agents can go wrong in unpredictable ways. Production agents need guardrails:

**Input validation** — Verify the task is within the agent's intended scope before starting.

**Action confirmation** — For irreversible actions (sending emails, deleting files, making purchases), pause and ask for human approval.

**Budget limits** — Set maximum API calls, tokens, or wall-clock time per session. An infinite loop in an agent is expensive.

**Sandboxed execution** — Code execution in isolated containers, not on the host system.

**Audit logging** — Every tool call, input, and output logged for debugging and compliance.

```python
class SafeAgent:
    def __init__(self, max_steps: int = 20, budget_usd: float = 1.0):
        self.steps = 0
        self.max_steps = max_steps
        self.budget = budget_usd
        self.spent = 0.0
    
    def can_continue(self) -> bool:
        if self.steps >= self.max_steps:
            raise AgentLimitError("Max steps reached")
        if self.spent >= self.budget:
            raise AgentLimitError("Budget exceeded")
        return True
```

---

## What Real Production Agents Look Like

The gap between "demo agent" and "production agent" is mainly:

| Demo | Production |
| :--- | :--- |
| Happy path only | Handles all error states |
| No cost control | Token budgets and circuit breakers |
| Sequential execution | Parallelism where possible |
| No memory across sessions | Persistent memory + episodic recall |
| Single agent | Specialized agent teams |
| No logging | Full audit trail |
| Hard-coded prompts | Versioned, tested prompts |

The framework doesn't matter as much as the design. Whether you use LangChain, LlamaIndex, or build your own loop — the agent is only as good as the thought put into its memory design, planning layer, and error handling.

---

*Next: [Token Usage, Context Windows, and the Economics of LLMs](./2026-04-20-tokens-context-economics.md)*
