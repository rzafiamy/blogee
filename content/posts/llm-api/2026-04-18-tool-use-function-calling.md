---
title: "Tool Use & Function Calling: Give Your AI Hands"
description: "How to define tools, handle tool calls, and build real AI-powered workflows that interact with APIs, databases, and the real world — in Python and TypeScript."
date: "2026-04-18"
tags: ["api", "tool-use", "function-calling", "agents", "python", "typescript"]
---

A language model on its own can only generate text. It can describe how to check the weather, but it can't actually check it. **Tool use** changes that.

Tool use (also called function calling) lets you define a set of functions that the model can request to call. Your code executes those calls and feeds the results back. The model reasons about when to use tools, what arguments to pass, and how to synthesize the results into a final answer.

This is the foundation of every AI agent.

---

## The Mental Model

```
User: "What's the weather in Paris and should I bring an umbrella?"

LLM: [thinks] I need weather data. I'll use get_weather.
     → Tool call: get_weather(city="Paris")

Your code: → Calls weather API → Returns {"temp": 18, "condition": "rainy"}

LLM: [thinks] It's rainy. I'll answer.
     → "It's 18°C and rainy in Paris — yes, bring an umbrella."
```

The model never directly calls your API. It outputs a *structured request*, your code handles execution, and you return the result. The model then incorporates that result into its reasoning.

---

## OpenAI Tool Definition

Tools are defined as JSON Schema objects describing the function name, description, and parameters:

```python
tools = [
    {
        "type": "function",
        "function": {
            "name": "get_weather",
            "description": "Get the current weather for a city. Call this when the user asks about weather.",
            "parameters": {
                "type": "object",
                "properties": {
                    "city": {
                        "type": "string",
                        "description": "The city name, e.g. 'Paris' or 'Tokyo'"
                    },
                    "units": {
                        "type": "string",
                        "enum": ["celsius", "fahrenheit"],
                        "description": "Temperature units"
                    }
                },
                "required": ["city"]
            }
        }
    }
]
```

**The description is critical.** The model uses it to decide when to call the tool. Be specific about when it should and shouldn't be used.

---

## Python: Full Tool-Use Loop with OpenAI

```python
import json
from openai import OpenAI

client = OpenAI()

# Your actual function implementations
def get_weather(city: str, units: str = "celsius") -> dict:
    # In reality: call a weather API
    return {"city": city, "temperature": 18, "condition": "rainy", "units": units}

def search_web(query: str) -> str:
    # In reality: call a search API
    return f"Search results for '{query}': [result 1, result 2, ...]"

# Map function names to implementations
available_tools = {
    "get_weather": get_weather,
    "search_web": search_web
}

def run_agent(user_message: str) -> str:
    messages = [{"role": "user", "content": user_message}]
    
    while True:
        response = client.chat.completions.create(
            model="gpt-4o",
            messages=messages,
            tools=tools,
            tool_choice="auto"  # Model decides when to use tools
        )
        
        choice = response.choices[0]
        messages.append(choice.message)  # Add assistant message to history
        
        # If no tool calls, we're done
        if choice.finish_reason == "stop":
            return choice.message.content
        
        # Execute each tool call
        if choice.finish_reason == "tool_calls":
            for tool_call in choice.message.tool_calls:
                func_name = tool_call.function.name
                func_args = json.loads(tool_call.function.arguments)
                
                # Execute the function
                result = available_tools[func_name](**func_args)
                
                # Add result to conversation
                messages.append({
                    "role": "tool",
                    "tool_call_id": tool_call.id,
                    "content": json.dumps(result)
                })

answer = run_agent("What's the weather in Paris and Tokyo? Which is warmer?")
print(answer)
```

---

## Anthropic Tool Use

Anthropic's tool use follows the same concept with slightly different syntax:

```python
import anthropic
import json

client = anthropic.Anthropic()

tools = [
    {
        "name": "get_stock_price",
        "description": "Get the current stock price for a ticker symbol.",
        "input_schema": {
            "type": "object",
            "properties": {
                "ticker": {
                    "type": "string",
                    "description": "Stock ticker symbol, e.g. 'AAPL', 'MSFT'"
                }
            },
            "required": ["ticker"]
        }
    }
]

def get_stock_price(ticker: str) -> dict:
    # Mock implementation
    prices = {"AAPL": 189.50, "MSFT": 420.30, "NVDA": 875.00}
    return {"ticker": ticker, "price": prices.get(ticker, 0), "currency": "USD"}

messages = [{"role": "user", "content": "Compare AAPL and NVDA stock prices."}]

while True:
    response = client.messages.create(
        model="claude-sonnet-4-6",
        max_tokens=1024,
        tools=tools,
        messages=messages
    )
    
    messages.append({"role": "assistant", "content": response.content})
    
    if response.stop_reason == "end_turn":
        # Extract text from response
        for block in response.content:
            if block.type == "text":
                print(block.text)
        break
    
    if response.stop_reason == "tool_use":
        tool_results = []
        for block in response.content:
            if block.type == "tool_use":
                result = get_stock_price(**block.input)
                tool_results.append({
                    "type": "tool_result",
                    "tool_use_id": block.id,
                    "content": json.dumps(result)
                })
        
        messages.append({"role": "user", "content": tool_results})
```

---

## TypeScript: Tool Use with OpenAI

```typescript
import OpenAI from "openai";

const client = new OpenAI();

type WeatherResult = { city: string; temperature: number; condition: string };

async function getWeather(city: string): Promise<WeatherResult> {
  // Mock: replace with real API call
  return { city, temperature: 18, condition: "rainy" };
}

const tools: OpenAI.Chat.Completions.ChatCompletionTool[] = [
  {
    type: "function",
    function: {
      name: "get_weather",
      description: "Get current weather for a city",
      parameters: {
        type: "object",
        properties: {
          city: { type: "string", description: "City name" }
        },
        required: ["city"]
      }
    }
  }
];

async function runAgent(userMessage: string): Promise<string> {
  const messages: OpenAI.Chat.Completions.ChatCompletionMessageParam[] = [
    { role: "user", content: userMessage }
  ];

  while (true) {
    const response = await client.chat.completions.create({
      model: "gpt-4o",
      messages,
      tools,
      tool_choice: "auto"
    });

    const choice = response.choices[0];
    messages.push(choice.message);

    if (choice.finish_reason === "stop") {
      return choice.message.content ?? "";
    }

    if (choice.finish_reason === "tool_calls" && choice.message.tool_calls) {
      for (const toolCall of choice.message.tool_calls) {
        const args = JSON.parse(toolCall.function.arguments);
        const result = await getWeather(args.city);
        
        messages.push({
          role: "tool",
          tool_call_id: toolCall.id,
          content: JSON.stringify(result)
        });
      }
    }
  }
}

const answer = await runAgent("Is it raining in London?");
console.log(answer);
```

---

## Designing Good Tools

Tool design is a skill. These principles separate reliable agents from flaky ones:

**1. One purpose per tool.** `search_and_summarize()` is two tools forced together. Split them.

**2. Idempotent where possible.** Tools that can safely be called twice are easier to handle in error recovery.

**3. Structured output.** Return JSON, not prose. The model can reason about structured data better than freeform text.

**4. Explicit error states.** Return `{"error": "City not found"}` rather than throwing exceptions — the model can handle that gracefully.

**5. Descriptions that prevent misuse.** If a tool is destructive, say so: `"Permanently deletes a record. Only call this after confirming with the user."`

---

## What You Can Build

Once you understand tool use, the ceiling is very high:

- **Research agents** — search the web, read URLs, synthesize findings
- **Data analysis agents** — query databases, run calculations, generate charts
- **Customer support agents** — look up orders, update records, escalate tickets
- **DevOps agents** — check deployment status, roll back releases, query logs
- **Personal assistants** — read/send email, manage calendar, create documents

The pattern is always the same: define what the agent *can do*, write the execution layer, and let the model decide how to orchestrate the tools to solve the user's problem.

---

*Next: [AI Agents — Architectures, Patterns, and How They Actually Work](../../ai-concepts/agents/)*
