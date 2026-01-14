# Sage

**AI Agent Orchestrator for Laravel Applications**

Sage is a local development dashboard that orchestrates AI coding agents across Git worktrees, providing instant preview URLs for each feature branch — like GitHub PR previews, but on your machine.

---

## Core Philosophy

- **Environment agnostic** — Works on macOS, Linux, and Windows (via WSL)
- **Container optional** — Run natively or in Docker, as long as project folders are accessible
- **Zero config server management** — Sage handles Caddy/Nginx configs automatically
- **Single binary distribution** — Download and run. No PHP install required (FrankenPHP)

---

## Key Features

### Git Worktree Management

- Create/delete worktrees from the dashboard
- Each worktree gets an instant preview URL (e.g., `feature-auth.myapp.local`)
- Visual status of all active worktrees

### Server Driver System

- **Caddy driver** — Dynamic vhost generation, automatic TLS
- **Nginx driver** — Config file management with safe append/delete
- Drivers handle config injection without touching user's existing setup

### AI Agent Orchestration

- Spawn agents (Claude Code, etc.) on specific worktrees
- Real-time terminal output streaming
- Model selection (claude-sonnet-4-20250514, opus, etc.)
- Agent switching (future: Cursor, Aider, etc.)

### Dashboard Views

- **Kanban** — Drag tasks through stages (idea → in-progress → review → done)
- **Terminal** — Interact with main repo or any worktree
- **CLAUDE.md Editor** — Manage agent instructions per project/worktree
- **Spec Generator** — AI-assisted feature specification from ideas
- **Env Manager** — View/edit `.env` files across worktrees

---

## Tech Stack

| Layer    | Tech                                              |
| -------- | ------------------------------------------------- | --------------------------------------------------- |
| Backend  | Laravel 11, Octane, Reverb                        |
| Frontend | React 19, shadcn/ui, Tailwind v4, Inertia.js      |
| Database | SQLite (single file for data + queues)            |
| Runtime  | FrankenPHP (static binary)                        |
| Process  | Laravel Queues (database driver), Symfony Process |
| Testing  | Pest + Pest Browser                               | Full coverage: unit, feature, and E2E browser tests |
| Agents   | Claude Code, OpenCode                             |

---

## Distribution

1. **Git clone** — `git clone https://github.com/Prvious/sage && cd sage && ./sage serve`
2. **Binary download** — Single executable for each platform, built with FrankenPHP static builds

---

## Gotchas & Considerations

### Must Address

| Issue                         | Solution                                                                                                             |
| ----------------------------- | -------------------------------------------------------------------------------------------------------------------- |
| **APP_URL per worktree**      | Each worktree needs its own `.env` with correct `APP_URL`. Sage should auto-generate/patch this on worktree creation |
| **Database isolation**        | Worktrees sharing a DB will collide. Options: separate SQLite per worktree, or DB prefix, or user chooses            |
| **Port conflicts**            | If user runs Sage + their app both via Octane, ports clash. Sage should use a dedicated port (e.g., 1984)            |
| **File permissions**          | Sage modifying Nginx configs needs appropriate permissions. May need sudo or user to add Sage to www-data group      |
| **Caddy API vs file**         | Caddy supports hot reload via admin API — prefer this over file writes for zero-downtime                             |
| **Windows path hell**         | WSL2 file access from Windows is slow. Recommend keeping projects inside WSL filesystem                              |
| **Reverb + Octane**           | Both need to run. Sage binary needs to boot both (Reverb on separate port)                                           |
| **Pest Browser + FrankenPHP** | Need to verify Dusk/Pest Browser works when app runs via Octane. May need `php artisan serve` fallback for tests     |
| **Agent PATH**                | Binary might not find `claude` or `opencode` if not in PATH. Allow explicit binary path config                       |

### Nice to Have

| Feature              | Notes                                                                          |
| -------------------- | ------------------------------------------------------------------------------ |
| **Tunnel support**   | Expose preview URLs publicly via Cloudflare Tunnel or ngrok for mobile testing |
| **Webhook on merge** | Auto-cleanup worktree when branch is merged on GitHub                          |
| **Cost tracking**    | Track API token usage per task/agent                                           |
| **Diff viewer**      | Show what agent changed before merge                                           |
| **Snapshot/restore** | Save worktree state before risky agent operations                              |
| **Multi-project**    | Manage multiple Laravel apps from one Sage instance                            |

### Security Considerations

- Sage runs with access to your codebase + can execute commands. It should **never** be exposed publicly
- Consider adding optional auth even for local (PIN code, etc.)
- Agent API keys stored in Sage's `.env` — make sure it's gitignored

---

## Proposed Data Model

```
Project
├── id, name, path, server_driver, base_url
│
├── Worktree (hasMany)
│   ├── id, branch_name, path, preview_url, status
│   └── env_overrides (JSON)
│
├── Task (hasMany)
│   ├── id, title, description, status, worktree_id
│   ├── prompt, agent_output, model, agent_type
│   └── commits (hasMany → Commit)
│
└── Spec (hasMany)
    ├── id, title, content (markdown)
    └── generated_from_idea
```

---

## CLI Commands (Future)

```bash
sage serve                    # Start dashboard
sage worktree:create feature-x  # Create worktree + preview
sage worktree:list            # Show all worktrees
sage task:run "add dark mode" # Create task + spawn agent
sage preview:open feature-x   # Open preview URL in browser
```

---

## Single binary build

Use frankenphp to build a single static binary of the application 'Sage'
the binary will start the webserver(Caddy), start the queue(queue:work), and the schedule(schedule:work)

---

## Agent Abstraction via Manager class

```php
use Illuminate\Support\Manager;
class Agent extends Manager
{
    public function createClaudeDriver(): ClaudeDriver
    {
        return $this->container->make(ClaudeDriver::class);
    }

    public function getDefaultDriver(){
        return config('sage.agents.default', default: 'claude');
    }
}

// Implementations
ClaudeDriver::class   // claude --worktree /path --prompt "..."
OpenCodeDriver::class     // opencode --dir /path "..."
FakeAgentDriver::class // Fake agent for testing/mocks
```

Both Claude Code and OpenCode are CLI-based, so the abstraction is straightforward — spawn a process, stream output, track status.
There must be a FakeAgentDriver::class

Config would look like:

```php
// config/sage.php
'agents' => [
    'claude' => [
        'driver' => ClaudeCodeDriver::class,
        'binary' => env('CLAUDE_CODE_PATH', 'claude'),
    ],
    'opencode' => [
        'driver' => OpenCodeDriver::class,
        'binary' => env('OPENCODE_PATH', 'opencode'),
    ],
],

'default' => env('SAGE_AGENT', 'claude'),
```

---

## Testing Strategy

### Pest (Unit + Feature)

```php
// Unit: isolated logic
it('generates correct preview url from branch name', function () {
    expect(PreviewUrl::fromBranch('feature/auth-system'))
        ->toBe('feature-auth-system.myapp.local');
});

// Feature: full Laravel stack
it('creates a worktree and updates server config', function () {
    $project = Project::factory()->create(['server_driver' => 'caddy']);

    post('/api/worktrees', [
        'branch' => 'feature-payments',
        'project_id' => $project->id,
    ])->assertCreated();

    expect(Worktree::where('branch_name', 'feature-payments')->exists())->toBeTrue();

    // Assert Caddy config was updated
    Caddy::assertConfigContains('feature-payments.myapp.local');
});
```

### Pest Browser (E2E)

```php
// Browser: real user flows
it('can create a task from the kanban board', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/dashboard')
            ->click('@new-task-button')
            ->type('@task-prompt', 'Add user authentication with Laravel Breeze')
            ->select('@agent-select', 'claude-code')
            ->press('Create Task')
            ->waitForText('Task created')
            ->assertSee('Add user authentication');
    });
});

it('streams agent output in real-time', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/tasks/1')
            ->waitFor('@terminal-output')
            ->waitForText('Creating files...', 30) // websocket streaming
            ->assertPresent('@agent-running-indicator');
    });
});
```

### Test Isolation Challenges

| Challenge         | Solution                                              |
| ----------------- | ----------------------------------------------------- |
| Agent processes   | Mock `Process` facade or use a `FakeAgentDriver`      |
| Reverb websockets | Pest Browser can wait for DOM changes triggered by WS |

---
