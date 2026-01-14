---
name: agent-orchestration
description: Spawn and manage AI coding agents (Claude Code, OpenCode) on worktrees
depends_on: git-worktree-management
---

## Detailed Description

This feature enables spawning AI coding agents on specific worktrees, streaming their terminal output in real-time, and tracking their progress. It provides a pluggable agent system supporting multiple AI assistants.

### Key Capabilities

- Pluggable agent system using Laravel's Manager pattern
- Support for Claude Code and OpenCode (extensible to others)
- Spawn agents on specific worktrees with custom prompts
- Real-time terminal output streaming via WebSockets
- Model selection (claude-sonnet-4-20250514, opus, etc.)
- Agent process management (start, stop, kill)
- Track agent outputs and associate with tasks
- Capture Git commits made by agents
- Handle agent errors and failures gracefully
- FakeAgentDriver for testing without real agents

### Supported Agents

1. **Claude Code**: `claude --worktree /path --prompt "add authentication"`
2. **OpenCode**: `opencode --dir /path "add authentication"`
3. **Fake Agent** (for testing): Simulates agent behavior

### User Stories

1. As a developer, I want to spawn Claude Code on a worktree with a prompt
2. As a developer, I want to see real-time terminal output as the agent works
3. As a developer, I want to switch between different AI models
4. As a developer, I want to stop a running agent if needed
5. As a developer, I want to see what commits the agent made

## Detailed Implementation Plan

### Step 1: Create Agent Driver Manager

```bash
php artisan make:class Drivers/Agent/AgentManager --no-interaction
```

Extend `Illuminate\Support\Manager`:

```php
class AgentManager extends Manager
{
    public function createClaudeDriver(): ClaudeDriver
    {
        return $this->container->make(ClaudeDriver::class);
    }

    public function createOpencodeDriver(): OpenCodeDriver
    {
        return $this->container->make(OpenCodeDriver::class);
    }

    public function createFakeDriver(): FakeAgentDriver
    {
        return $this->container->make(FakeAgentDriver::class);
    }

    public function getDefaultDriver(): string
    {
        return config('sage.agents.default', 'claude');
    }
}
```

### Step 2: Create Agent Driver Contract

```bash
php artisan make:interface Drivers/Agent/Contracts/AgentDriver --no-interaction
```

Define interface:

```php
interface AgentDriver
{
    public function spawn(Worktree $worktree, string $prompt, array $options = []): Process;
    public function stop(Process $process): bool;
    public function isAvailable(): bool;
    public function getSupportedModels(): array;
    public function getBinaryPath(): string;
}
```

### Step 3: Implement Claude Code Driver

```bash
php artisan make:class Drivers/Agent/ClaudeDriver --no-interaction
```

**Key Methods:**

**spawn:**

- Build command: `claude --worktree {path} --prompt "{prompt}" --model {model}`
- Create Symfony Process instance
- Set working directory to worktree path
- Set environment variables (API keys)
- Start process asynchronously
- Return process instance

**stop:**

- Send SIGTERM to process
- Wait for graceful shutdown (timeout 10s)
- If still running, send SIGKILL
- Return success status

**isAvailable:**

- Check if `claude` binary exists in PATH or configured path
- Try running `claude --version`
- Return true if successful

**getSupportedModels:**

- Return array: `['claude-sonnet-4-20250514', 'claude-opus-4-20250514', 'claude-3-5-sonnet-20241022']`

### Step 4: Implement OpenCode Driver

```bash
php artisan make:class Drivers/OpenCodeDriver --no-interaction
```

**Key Methods:**

**spawn:**

- Build command: `opencode --dir {path} "{prompt}"`
- Create and start Symfony Process
- Return process instance

**Other methods:** Similar to ClaudeDriver but adapted for OpenCode CLI

### Step 5: Implement Fake Agent Driver

```bash
php artisan make:class Drivers/FakeAgentDriver --no-interaction
```

**Purpose:** For testing without real agents

**spawn:**

- Create a mock process that outputs predefined text
- Simulate agent behavior (creating files, making commits)
- Return fake process

**stop:**

- Immediately mark process as stopped
- Return true

**isAvailable:**

- Always return true

### Step 6: Create Agent Execution Job

```bash
php artisan make:job Agent/RunAgent --no-interaction
```

**Job Responsibilities:**

1. Update task status to `in_progress`
2. Get agent driver from manager
3. Spawn agent process on worktree
4. Stream output line-by-line
5. Broadcast each line via WebSocket
6. Store complete output in task
7. Monitor process for completion
8. Detect Git commits made during execution
9. Update task with commits
10. Update task status to `done` or `failed`

Implement `ShouldQueue` interface.

### Step 7: Create Process Output Streamer

```bash
php artisan make:class Services/ProcessStreamer --no-interaction
```

**Methods:**

- `stream(Process $process, callable $callback): void`
- Read stdout/stderr line by line
- Call callback for each line
- Handle process completion
- Handle process errors

### Step 8: Create Git Commit Detector

```bash
php artisan make:class Services/CommitDetector --no-interaction
```

**Methods:**

- `detectNewCommits(Worktree $worktree, string $since): array`
- Run `git log --since="{timestamp}" --format="%H|%an|%s"`
- Parse output into array of commits
- Return commit data

### Step 9: Create WebSocket Events

```bash
php artisan make:event Agent/OutputReceived --no-interaction
php artisan make:event Agent/StatusChanged --no-interaction
```

**AgentOutputReceived:**

- Properties: `taskId`, `line`, `type` (stdout/stderr)
- Broadcast on channel: `task.{taskId}`

**AgentStatusChanged:**

- Properties: `taskId`, `status`, `message`
- Broadcast on channel: `task.{taskId}`

### Step 10: Create Configuration File

Add to `config/sage.php`:

```php
'agents' => [
    'default' => env('SAGE_AGENT', 'claude'),

    'claude' => [
        'driver' => ClaudeDriver::class,
        'binary' => env('CLAUDE_CODE_PATH', 'claude'),
        'default_model' => env('CLAUDE_DEFAULT_MODEL', 'claude-sonnet-4-20250514'),
    ],

    'opencode' => [
        'driver' => OpenCodeDriver::class,
        'binary' => env('OPENCODE_PATH', 'opencode'),
    ],

    'fake' => [
        'driver' => FakeAgentDriver::class,
    ],
],
```

### Step 11: Create Agent Facade

```bash
php artisan make:class Facades/Agent --no-interaction
```

Register in `config/app.php`.

### Step 12: Create API Endpoints for Agent Control

```bash
php artisan make:controller AgentController --no-interaction
```

Routes:

- `POST /tasks/{task}/start` - Start agent on task
- `POST /tasks/{task}/stop` - Stop running agent
- `GET /tasks/{task}/output` - Get agent output (fallback if WS not available)

### Step 13: Create Inertia Components

**Components:**

- `resources/js/components/agent-terminal.tsx` - Terminal output display with syntax highlighting
- `resources/js/components/agent-controls.tsx` - Start/stop buttons, model selector
- `resources/js/components/commit-list.tsx` - Show commits made by agent

### Step 14: Integrate WebSocket Listener

In React component:

```typescript
import { useEcho } from '@laravel/echo-react';

useEcho(`task.${taskId}`, 'Agent.OutputReceived', (e) => {
    console.log(e.order);
});
```

### Step 15: Create Feature Tests

Test coverage:

- `it('can spawn claude code agent on worktree')`
- `it('can spawn opencode agent on worktree')`
- `it('streams agent output correctly')`
- `it('detects commits made by agent')`
- `it('can stop running agent')`
- `it('handles agent errors gracefully')`
- `it('updates task status correctly')`
- `it('fake agent driver works for testing')`

### Step 16: Create Browser Tests

E2E test coverage:

- `it('can start agent from UI')`
- `it('displays real-time terminal output')`
- `it('can stop agent from UI')`
- `it('shows commits made by agent')`

### Step 17: Add Artisan Command for Testing Agents

```bash
php artisan make:command TestAgentCommand --no-interaction
```

Command: `php artisan sage:test-agent {driver?}`

- Test if agent binary is available
- Test spawning with simple prompt
- Display output
- Report success/failure

### Step 18: Format Code

```bash
vendor/bin/pint --dirty
```
