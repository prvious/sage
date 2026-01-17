---
name: terminal-page
description: Full-page interactive terminal with connection management for project workspaces
depends_on: project-context-sidebar
---

## Feature Description

Create a dedicated terminal page that allows users to interact with their project's command line interface. The terminal will feature a "Connect" button to prevent accidental terminal sessions when users navigate to the page, giving them explicit control over when to start a terminal connection.

Users will be able to execute commands within the context of the selected project's directory, view command output in real-time, and maintain persistent terminal sessions during their workflow.

## Implementation Plan

### Backend Components

**Controller**:

- Create `app/Http/Controllers/TerminalController.php`
- Methods:
    - `show(Project $project)` - Display terminal page
    - `connect(Project $project)` - Initiate terminal session
    - `execute(Request $request, Project $project)` - Execute command
    - `disconnect(Project $project)` - Close terminal session

**Actions**:

- Create `app/Actions/Terminal/CreateTerminalSession.php`
- Create `app/Actions/Terminal/ExecuteCommand.php`
- Create `app/Actions/Terminal/CloseTerminalSession.php`

**Models** (optional):

- Consider creating `app/Models/TerminalSession.php` to track active sessions
- Fields: `id`, `project_id`, `user_id`, `pid`, `status`, `started_at`, `ended_at`

**Routes**:

```php
Route::prefix('projects/{project}')->group(function () {
    Route::get('/terminal', [TerminalController::class, 'show'])->name('projects.terminal.show');
    Route::post('/terminal/connect', [TerminalController::class, 'connect'])->name('projects.terminal.connect');
    Route::post('/terminal/execute', [TerminalController::class, 'execute'])->name('projects.terminal.execute');
    Route::post('/terminal/disconnect', [TerminalController::class, 'disconnect'])->name('projects.terminal.disconnect');
});
```

**Process Management**:

- Use `Symfony\Component\Process\Process` for command execution
- Consider using Laravel Queues for long-running commands
- Use Laravel Reverb for real-time output streaming via WebSockets

**Security**:

- Validate and sanitize all commands
- Restrict dangerous commands (e.g., `rm -rf`, `sudo`, etc.)
- Set working directory to project path
- Implement command history logging for audit trail
- Consider command whitelist/blacklist configuration

### Frontend Components

**Page**:

- Create `resources/js/pages/projects/terminal.tsx`
- Terminal interface with xterm.js or similar terminal emulator
- Connect/Disconnect button
- Command history
- Status indicators (connected/disconnected)

**Components**:

- Create `resources/js/components/terminal/terminal-emulator.tsx` - Main terminal component
- Create `resources/js/components/terminal/connection-status.tsx` - Connection status indicator
- Create `resources/js/components/terminal/command-history.tsx` - Command history sidebar (optional)

**State Management**:

- Track connection status (disconnected, connecting, connected)
- Store command history in local state
- Handle WebSocket connection for real-time output
- Manage terminal session lifecycle

**WebSocket Integration**:

- Connect to Laravel Reverb for real-time command output
- Subscribe to project-specific terminal channel
- Handle connection, disconnection, and reconnection logic

**UI/UX Features**:

- Prominent "Connect" button when disconnected
- Loading state while connecting
- Clear visual indication of connection status
- Command input disabled until connected
- Auto-scroll to latest output
- Keyboard shortcuts (Ctrl+C, Ctrl+L for clear, etc.)
- Terminal theme options (dark/light)

### Styling

**Shadcn Components**:

- Use `Button` for Connect/Disconnect actions
- Use `Card` for terminal container
- Use `Badge` for status indicators
- Use `Alert` for error messages

**Terminal Styling**:

- Use monospace font (e.g., JetBrains Mono, Fira Code)
- Dark theme by default (matches developer expectations)
- Proper ANSI color support for command output
- Responsive design for different screen sizes

## Acceptance Criteria

- [ ] Terminal page is accessible at `/projects/{project}/terminal`
- [ ] "Connect" button is displayed when terminal is disconnected
- [ ] Clicking "Connect" initiates terminal session in project directory
- [ ] Users can execute commands and see real-time output
- [ ] Terminal shows connection status clearly (connected/disconnected)
- [ ] Users can disconnect from terminal session
- [ ] Terminal session closes when user navigates away or closes tab
- [ ] Command output supports ANSI colors and formatting
- [ ] Dangerous commands are prevented or warned about
- [ ] Terminal automatically scrolls to show latest output
- [ ] Keyboard shortcuts work (Ctrl+C, Ctrl+L)
- [ ] Terminal persists across page refreshes while session is active
- [ ] Error messages display clearly when commands fail
- [ ] No console errors or warnings
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location**: `tests/Feature/Terminal/TerminalControllerTest.php`

**Key test cases**:

- Test terminal page renders successfully
- Test terminal connect endpoint creates session
- Test command execution returns output
- Test disconnect endpoint closes session
- Test commands execute in correct project directory
- Test dangerous commands are blocked
- Test session cleanup on disconnect
- Test unauthorized access is prevented

### Browser Tests

**Test file location**: `tests/Browser/TerminalTest.php`

**Key test cases**:

- Test "Connect" button is visible when disconnected
- Test clicking "Connect" initiates terminal session
- Test executing simple command (e.g., `pwd`, `ls`)
- Test command output displays in terminal
- Test connection status updates correctly
- Test disconnect functionality works
- Test error handling for failed commands
- Test terminal auto-scrolls to latest output
- Test terminal works with different project paths
- Test WebSocket connection handles reconnection
- Test keyboard shortcuts (Ctrl+C, Ctrl+L)

## Code Formatting

Format all code using:

- **Backend (PHP)**: Laravel Pint
    - Command: `vendor/bin/pint --dirty`
- **Frontend (TypeScript/React)**: Prettier
    - Command: `pnpm run format`

## Additional Notes

### Terminal Emulator Library

Consider using one of these libraries:

- **xterm.js** (recommended) - Full-featured terminal emulator
- **react-console-emulator** - Simpler React-specific option
- **terminal-kit** - Node.js terminal toolkit

### Security Considerations

**Command Restrictions**:

- Block or warn on dangerous commands: `rm -rf`, `sudo`, `chmod`, `chown`
- Prevent directory traversal outside project path
- Sanitize input to prevent command injection
- Log all executed commands for audit trail

**Session Management**:

- Limit concurrent sessions per project
- Auto-timeout inactive sessions (e.g., 30 minutes)
- Cleanup orphaned processes on session end

### Process Management

**Working Directory**:

- Set working directory to `$project->path`
- For worktrees, allow switching context to worktree path

**Environment Variables**:

- Load project's `.env` file for command execution
- Set appropriate PATH and environment variables

**Output Handling**:

- Stream output in real-time via WebSockets
- Buffer output for large responses
- Handle both stdout and stderr
- Support ANSI escape codes for colors

### Future Enhancements

- **Multiple terminals** - Tab interface for multiple concurrent sessions
- **Command history** - Persistent command history across sessions
- **Worktree context switching** - Switch terminal context to specific worktree
- **Command snippets** - Saved frequently-used commands
- **File upload/download** - Drag-and-drop file transfer
- **Split panes** - Multiple terminal views simultaneously
- **Terminal themes** - Multiple color schemes (Dracula, Monokai, etc.)
- **Session recording** - Record and replay terminal sessions
- **Collaborative mode** - Share terminal with other users (future)

### WebSocket Channel Structure

```php
// Example channel structure
Broadcast::channel('project.{projectId}.terminal', function ($user, $projectId) {
    return $user->hasAccessToProject($projectId);
});
```

### Sample Terminal Output Event

```php
// Broadcast terminal output
TerminalOutputEvent::dispatch($project, $output, $type); // type: stdout/stderr
```

### Integration Points

- Integrate with existing project path from `Project` model
- Use same authentication/authorization as other project routes
- Consider integration with Agent execution output (future)
- Could link to Worktree-specific terminals (future)
