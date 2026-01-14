---
name: terminal-view
description: Interactive terminal for executing commands in main repo or any worktree
depends_on: git-worktree-management
---

## Detailed Description

This feature provides an interactive web-based terminal that allows developers to run shell commands in the main repository or any worktree directly from the Sage dashboard. It's useful for running ad-hoc commands without switching to a real terminal.

### Key Capabilities
- Web-based terminal emulator with command input
- Execute commands in main repo or selected worktree
- Command history (up/down arrow navigation)
- Real-time output streaming
- Support for long-running commands
- Command autocomplete (basic)
- Copy output to clipboard
- Clear terminal
- Switch between main repo and different worktrees
- Persist terminal state per worktree

### User Stories
1. As a developer, I want to run artisan commands on a worktree without leaving the dashboard
2. As a developer, I want to see command output in real-time
3. As a developer, I want to access my command history
4. As a developer, I want to switch between different worktrees easily

### Security Considerations
- Terminal has full shell access - must be local only
- Sanitize output to prevent XSS
- Consider command whitelist/blacklist
- Rate limit command execution
- Log all commands for audit

## Detailed Implementation Plan

### Step 1: Install Terminal Library

Use xterm.js for terminal emulation:
```bash
npm install xterm @xterm/addon-fit @xterm/addon-web-links
```

### Step 2: Create Terminal Component
```typescript
// resources/js/Components/Terminal.tsx
```

**Features:**
- Initialize xterm.js instance
- Connect to WebSocket for I/O
- Handle keyboard input
- Display output with ANSI color support
- Fit terminal to container size
- Clickable URLs in output

### Step 3: Create Terminal Controller
```bash
php artisan make:controller TerminalController --no-interaction
```

**Methods:**
- `execute()` - Execute command and return output
- `stream()` - Stream command output via WebSocket

### Step 4: Create Command Execution Service
```bash
php artisan make:class Services/TerminalService --no-interaction
```

**Methods:**
```php
public function execute(string $command, string $workingDirectory): Process
{
    // Validate command is not empty
    // Create Symfony Process
    // Set working directory
    // Set timeout (e.g., 300 seconds)
    // Execute command
    // Return process
}

public function streamOutput(Process $process, string $sessionId): void
{
    // Read stdout/stderr line by line
    // Broadcast each line via WebSocket
    // Handle completion
}
```

### Step 5: Create WebSocket Event for Output
```bash
php artisan make:event TerminalOutputReceived --no-interaction
```

**Properties:**
- `sessionId` - Unique terminal session ID
- `output` - Command output line
- `type` - stdout or stderr

Broadcast on channel: `terminal.{sessionId}`

### Step 6: Create Terminal Page Component
```typescript
// resources/js/Pages/Terminal/Index.tsx
```

**Layout:**
- Worktree selector dropdown (main + all worktrees)
- Terminal container (full width, resizable height)
- Control buttons: Clear, Copy Output, Kill Process
- Status indicator (ready, running, error)

### Step 7: Implement WebSocket Integration

In React component:
```typescript
const terminal = new Terminal()
const sessionId = generateSessionId()

Echo.channel(`terminal.${sessionId}`)
    .listen('TerminalOutputReceived', (e) => {
        terminal.write(e.output)
    })

// Send command input
terminal.onData((data) => {
    if (data === '\r') { // Enter key
        executeCommand(currentCommand)
    } else {
        currentCommand += data
        terminal.write(data)
    }
})
```

### Step 8: Add Command History

Store in localStorage:
```typescript
const history = useCommandHistory('terminal-history', 100) // max 100 commands

terminal.onData((data) => {
    if (data === '\x1B[A') { // Up arrow
        const previous = history.previous()
        if (previous) showCommand(previous)
    } else if (data === '\x1B[B') { // Down arrow
        const next = history.next()
        showCommand(next)
    }
})
```

### Step 9: Create Terminal Session Model
```bash
php artisan make:model TerminalSession -m --no-interaction
```

**Fields:**
- `id`
- `session_id` (unique)
- `worktree_id` (nullable, foreign key)
- `current_directory`
- `last_command`
- `status` (enum: idle, running)
- `timestamps`

Track active terminal sessions.

### Step 10: Implement Process Management

Add to TerminalService:
```php
private array $processes = [];

public function startProcess(string $sessionId, string $command, string $cwd): void
{
    $process = $this->execute($command, $cwd);
    $this->processes[$sessionId] = $process;

    $process->start();

    // Stream output asynchronously
    $this->streamOutput($process, $sessionId);
}

public function killProcess(string $sessionId): bool
{
    if (isset($this->processes[$sessionId])) {
        $this->processes[$sessionId]->stop();
        unset($this->processes[$sessionId]);
        return true;
    }
    return false;
}
```

### Step 11: Add Routes

Define routes:
- `GET /terminal` - Terminal page
- `POST /terminal/execute` - Execute command
- `POST /terminal/kill/{sessionId}` - Kill running process
- `GET /terminal/history` - Get command history

### Step 12: Create Form Request Validation
```bash
php artisan make:request ExecuteCommandRequest --no-interaction
```

**Validation Rules:**
- `command` - required, string, max:1000
- `worktree_id` - nullable, exists:worktrees,id
- `session_id` - required, string

### Step 13: Add Security Features

**Command Sanitization:**
- Strip dangerous commands (optional, based on user preference)
- Prevent command injection
- Validate session IDs

**Rate Limiting:**
```php
Route::post('/terminal/execute')
    ->middleware('throttle:60,1'); // 60 requests per minute
```

### Step 14: Implement Terminal Themes

Add theme switcher:
- Light theme
- Dark theme
- Custom themes (Dracula, Monokai, etc.)

Store preference in localStorage.

### Step 15: Add Copy Output Feature

Add button to copy terminal output:
```typescript
const copyOutput = () => {
    const selection = terminal.getSelection()
    navigator.clipboard.writeText(selection || terminal.getAllOutput())
}
```

### Step 16: Handle Long-running Commands

Show spinner when command is running:
- Update status indicator
- Enable "Kill Process" button
- Disable new command input until complete

### Step 17: Create Feature Tests

Test coverage:
- `it('can execute command in main repo')`
- `it('can execute command in worktree')`
- `it('streams output correctly')`
- `it('can kill running process')`
- `it('validates command input')`
- `it('rate limits command execution')`
- `it('tracks command history')`

### Step 18: Create Browser Tests

E2E test coverage:
- `it('can open terminal')`
- `it('can type and execute command')`
- `it('displays command output')`
- `it('can switch between worktrees')`
- `it('can navigate command history with arrows')`
- `it('can copy output to clipboard')`

### Step 19: Add Documentation

Create guide for common commands:
- Laravel artisan commands
- Git commands
- Composer commands
- NPM commands

### Step 20: Format Code
```bash
vendor/bin/pint --dirty
npm run format
```
