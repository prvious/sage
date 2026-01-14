---
name: artisan-server-driver
description: Add Artisan Server as third server driver option for development
depends_on: null
---

## Feature Description

Add a third server driver option called "Artisan Server" to the project creation and management system. Unlike Caddy and Nginx which require server configuration management, the Artisan Server driver simply runs `php artisan serve` for each worktree, providing a lightweight development server option that requires no external dependencies.

Key features:

- **Simple Setup**: No config files or external server requirements
- **Development-Focused**: Designed for local development environments
- **Process Management**: Spawn and manage `php artisan serve` processes per worktree
- **Automatic Port Assignment**: Dynamically assign ports to avoid conflicts
- **Easy Integration**: Adds as a third radio button option in project forms

This is ideal for developers who want quick setup without installing Caddy or Nginx, or for projects that only need a development server.

## Implementation Plan

### Backend Components

**Driver Class:**

- Create `app/Drivers/Server/ArtisanDriver.php` - Implements ServerDriverInterface

**Manager:**

- Modify `app/Drivers/Server/Manager.php` - Add createArtisanDriver() method

**Process Management:**

- May create `app/Services/ArtisanServerProcess.php` - Manage artisan serve processes (optional)
- Use Symfony Process for spawning background processes

**Configuration:**

- Add `config/sage.php` entry for artisan server settings:
    - Base port (default: 8000)
    - Port range (8000-8999)
    - Host (default: 127.0.0.1)

**Routes:**

- No new routes needed
- Existing project routes already handle server_driver field

**Database Changes:**

- No migrations needed
- The `server_driver` column already accepts string values
- Will store 'artisan' as the driver name

### ArtisanDriver Implementation

**Key Methods:**

```php
<?php

namespace App\Drivers\Server;

use App\Drivers\Server\Contracts\ServerDriverInterface;
use App\Models\Project;
use App\Models\Worktree;
use Illuminate\Support\Facades\Process;

class ArtisanDriver implements ServerDriverInterface
{
    /**
     * Generate configuration (not applicable for artisan serve).
     * Returns empty string as artisan serve doesn't need config files.
     */
    public function generateConfig(Project $project, Worktree $worktree): string
    {
        // Artisan serve doesn't require config files
        return '';
    }

    /**
     * Reload server (not applicable for artisan serve).
     * Artisan serve processes are managed per worktree.
     */
    public function reload(): void
    {
        // Artisan serve doesn't have a reload concept
        // Each worktree runs its own process
    }

    /**
     * Validate if PHP is available on the system.
     */
    public function validate(): bool
    {
        $result = Process::run('php -v');

        return $result->successful();
    }

    /**
     * Start artisan serve for a specific worktree.
     */
    public function start(Worktree $worktree): void
    {
        $port = $this->getAvailablePort($worktree);
        $host = config('sage.artisan_server.host', '127.0.0.1');

        Process::path($worktree->path)
            ->start("php artisan serve --host={$host} --port={$port}");

        // Store the port in worktree metadata
        $worktree->update([
            'preview_url' => "http://{$host}:{$port}",
        ]);
    }

    /**
     * Stop artisan serve for a specific worktree.
     */
    public function stop(Worktree $worktree): void
    {
        // Find and kill the process running on the worktree's port
        $url = parse_url($worktree->preview_url);
        $port = $url['port'] ?? null;

        if ($port) {
            // Kill process listening on this port
            Process::run("lsof -ti:{$port} | xargs kill -9");
        }
    }

    /**
     * Get an available port for the worktree.
     */
    protected function getAvailablePort(Worktree $worktree): int
    {
        $basePort = config('sage.artisan_server.base_port', 8000);
        $maxPort = config('sage.artisan_server.max_port', 8999);

        // Use worktree ID as offset for predictable port assignment
        $port = $basePort + ($worktree->id % 1000);

        // Check if port is available
        while (!$this->isPortAvailable($port) && $port <= $maxPort) {
            $port++;
        }

        return $port;
    }

    /**
     * Check if a port is available.
     */
    protected function isPortAvailable(int $port): bool
    {
        $result = Process::run("lsof -i:{$port}");

        return !$result->successful(); // Port is available if lsof fails
    }
}
```

### Manager Update

```php
// app/Drivers/Server/Manager.php

/**
 * Create an instance of the Artisan driver.
 */
public function createArtisanDriver(): ArtisanDriver
{
    return new ArtisanDriver;
}
```

### Frontend Components

**Pages:**

- Modify `resources/js/pages/projects/create.tsx` - Add "artisan" radio button option
- Modify `resources/js/pages/projects/edit.tsx` - Add "artisan" radio button option

**Form Update:**

```tsx
<div>
    <Label className='block text-sm font-medium'>Server Driver</Label>
    <div className='mt-2 flex gap-4'>
        <label className='flex items-center'>
            <input
                type='radio'
                value='caddy'
                checked={data.server_driver === 'caddy'}
                onChange={(e) => setData('server_driver', e.target.value)}
                className='h-4 w-4 border-gray-300 text-primary focus:ring-primary'
            />
            <span className='ml-2 text-sm'>Caddy</span>
        </label>
        <label className='flex items-center'>
            <input
                type='radio'
                value='nginx'
                checked={data.server_driver === 'nginx'}
                onChange={(e) => setData('server_driver', e.target.value)}
                className='h-4 w-4 border-gray-300 text-primary focus:ring-primary'
            />
            <span className='ml-2 text-sm'>Nginx</span>
        </label>
        <label className='flex items-center'>
            <input
                type='radio'
                value='artisan'
                checked={data.server_driver === 'artisan'}
                onChange={(e) => setData('server_driver', e.target.value)}
                className='h-4 w-4 border-gray-300 text-primary focus:ring-primary'
            />
            <span className='ml-2 text-sm'>Artisan Server</span>
        </label>
    </div>
    {errors.server_driver && <p className='mt-1 text-sm text-destructive'>{errors.server_driver}</p>}
</div>
```

### Configuration

**Add to `config/sage.php`:**

```php
return [
    // ... existing config

    /*
    |--------------------------------------------------------------------------
    | Artisan Server Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Artisan Server driver (php artisan serve).
    |
    */
    'artisan_server' => [
        'host' => env('ARTISAN_SERVER_HOST', '127.0.0.1'),
        'base_port' => env('ARTISAN_SERVER_BASE_PORT', 8000),
        'max_port' => env('ARTISAN_SERVER_MAX_PORT', 8999),
    ],
];
```

### TypeScript Type Update

```tsx
// In relevant TypeScript files
type ServerDriver = 'caddy' | 'nginx' | 'artisan';
```

### Validation Rules

**Update form requests:**

```php
// app/Http/Requests/StoreProjectRequest.php
// app/Http/Requests/UpdateProjectRequest.php

public function rules(): array
{
    return [
        // ... other rules
        'server_driver' => ['required', 'string', Rule::in(['caddy', 'nginx', 'artisan'])],
    ];
}
```

## Acceptance Criteria

- [ ] ArtisanDriver class implements ServerDriverInterface
- [ ] Manager has createArtisanDriver() method
- [ ] Artisan radio button appears on project create page
- [ ] Artisan radio button appears on project edit page
- [ ] Selecting "artisan" sets server_driver to 'artisan'
- [ ] Projects can be created with artisan server driver
- [ ] Projects with artisan driver can be edited
- [ ] ArtisanDriver.validate() checks if PHP is available
- [ ] ArtisanDriver can start `php artisan serve` for a worktree
- [ ] ArtisanDriver assigns available ports automatically
- [ ] ArtisanDriver can stop running artisan serve processes
- [ ] Preview URL is generated as http://127.0.0.1:{port}
- [ ] Config file has artisan_server settings
- [ ] Validation rules accept 'artisan' as valid server_driver
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location:** `tests/Feature/Drivers/ArtisanDriverTest.php`

**Key test cases:**

- Test ArtisanDriver.validate() returns true when PHP is available
- Test ArtisanDriver.validate() returns false when PHP is not available
- Test ArtisanDriver.generateConfig() returns empty string
- Test ArtisanDriver.reload() does nothing (no-op)
- Test ArtisanDriver.start() spawns php artisan serve process
- Test ArtisanDriver.start() assigns available port
- Test ArtisanDriver.stop() kills the artisan serve process
- Test ArtisanDriver handles port conflicts by finding next available port
- Test multiple worktrees can run simultaneously on different ports

### Feature Tests (Project Creation)

**Test file location:** `tests/Feature/Projects/ArtisanServerProjectTest.php`

**Key test cases:**

- Test project can be created with artisan server driver
- Test project creation validates artisan as valid server_driver
- Test project creation fails with invalid server_driver
- Test project edit can change server_driver to artisan
- Test projects with artisan driver display correctly on show page

### Browser Tests

**Test file location:** `tests/Browser/Projects/ArtisanServerFormTest.php`

**Key test cases:**

- Test "Artisan Server" radio button is visible on create page
- Test "Artisan Server" radio button is visible on edit page
- Test selecting "Artisan Server" updates form state
- Test creating project with Artisan Server succeeds
- Test editing project to use Artisan Server succeeds
- Test created project shows "artisan" as server driver

## Code Formatting

Format all code using: **Prettier** and **oxfmt** (for JavaScript/TypeScript), **Pint** (for PHP)

Commands to run:

- `pnpm run format` - Format JavaScript/TypeScript files
- `vendor/bin/pint --dirty` - Format PHP files

## Additional Notes

### Process Management Considerations

**Background Processes:**

- `php artisan serve` runs in foreground by default
- Use Symfony Process `start()` to run in background
- Store process PID for later termination

**Process Monitoring:**

- May need to track if artisan serve is still running
- Add health check endpoint or ping mechanism
- Restart automatically if process dies

**Graceful Shutdown:**

- Send SIGTERM before SIGKILL for clean shutdown
- Allow Laravel to finish current requests

### Port Management

**Port Assignment Strategy:**

1. **Predictable Ports**: Use worktree ID as offset (e.g., 8000 + worktree_id)
2. **Availability Check**: Verify port is not in use before starting
3. **Fallback**: Increment port number if taken
4. **Port Range**: Limit to 8000-8999 range

**Port Conflict Resolution:**

```php
protected function getAvailablePort(Worktree $worktree): int
{
    $basePort = 8000;
    $port = $basePort + ($worktree->id % 1000);

    while (!$this->isPortAvailable($port) && $port < 8999) {
        $port++;
    }

    if ($port >= 8999) {
        throw new \RuntimeException('No available ports in range 8000-8999');
    }

    return $port;
}
```

### Limitations of Artisan Server

**Production Warning:**

- Artisan serve is **NOT** for production
- Display warning in UI when artisan driver is selected
- Consider adding a badge or tooltip: "Development Only"

**Performance:**

- Single-threaded server
- Not suitable for high concurrency
- Slower than Caddy/Nginx for static assets

**Features:**

- No HTTPS support (only HTTP)
- No advanced routing rules
- No load balancing

### UI Enhancements

**Visual Indicators:**
Add badges or icons to differentiate drivers:

```tsx
<label className="flex items-center gap-2">
  <input type="radio" value="artisan" ... />
  <span className="text-sm">Artisan Server</span>
  <Badge variant="secondary" className="text-xs">
    Development Only
  </Badge>
</label>
```

**Tooltips:**
Add helpful tooltips explaining each driver:

```tsx
<label className="flex items-center gap-2">
  <input type="radio" value="artisan" ... />
  <span className="text-sm">Artisan Server</span>
  <TooltipProvider>
    <Tooltip>
      <TooltipTrigger>
        <Info className="h-4 w-4 text-muted-foreground" />
      </TooltipTrigger>
      <TooltipContent>
        <p>Lightweight PHP development server</p>
        <p>No external dependencies required</p>
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>
</label>
```

### Alternative: Radio Group Component

Use Shadcn RadioGroup component for better styling:

```bash
pnpm dlx shadcn@latest add radio-group
```

```tsx
<RadioGroup value={data.server_driver} onValueChange={(value) => setData('server_driver', value)}>
    <div className='flex items-center space-x-2'>
        <RadioGroupItem value='caddy' id='caddy' />
        <Label htmlFor='caddy'>Caddy</Label>
    </div>
    <div className='flex items-center space-x-2'>
        <RadioGroupItem value='nginx' id='nginx' />
        <Label htmlFor='nginx'>Nginx</Label>
    </div>
    <div className='flex items-center space-x-2'>
        <RadioGroupItem value='artisan' id='artisan' />
        <Label htmlFor='artisan'>Artisan Server</Label>
        <Badge variant='secondary'>Dev Only</Badge>
    </div>
</RadioGroup>
```

### Process Persistence

**Challenge:**

- Artisan serve processes need to survive across Sage restarts
- Current implementation spawns processes but doesn't track them

**Solutions:**

1. **PID File Storage:**

```php
// Store PID when starting
$process = Process::path($worktree->path)->start('php artisan serve ...');
file_put_contents($worktree->path . '/.artisan-serve.pid', $process->id());

// Read PID when stopping
$pid = file_get_contents($worktree->path . '/.artisan-serve.pid');
Process::run("kill {$pid}");
```

2. **Database Tracking:**
   Add `server_process_id` column to worktrees table:

```php
$worktree->update([
    'server_process_id' => $process->id(),
]);
```

3. **Supervisor/PM2:**
   Use a process manager for production-grade process management (overkill for dev servers)

### Cross-Platform Compatibility

**Port Check Commands:**

- Linux/macOS: `lsof -i:{port}`
- Windows: `netstat -ano | findstr :{port}`

**Kill Process Commands:**

- Linux/macOS: `kill -9 {pid}`
- Windows: `taskkill /PID {pid} /F`

**Implementation:**

```php
protected function killProcess(int $pid): void
{
    if (PHP_OS_FAMILY === 'Windows') {
        Process::run("taskkill /PID {$pid} /F");
    } else {
        Process::run("kill -9 {$pid}");
    }
}
```

### Future Enhancements (Out of Scope)

- **Auto-restart**: Restart artisan serve if it crashes
- **Log Viewing**: Stream artisan serve output to UI
- **Resource Monitoring**: Show CPU/memory usage per server
- **Port Pool Management**: Reserve port ranges per project
- **Custom Flags**: Allow passing custom flags to `php artisan serve`

### Security Considerations

**Localhost Only:**

- Default to `127.0.0.1` to prevent external access
- Warn if user changes to `0.0.0.0`

**Process Isolation:**

- Each worktree runs separate process
- Processes can't interfere with each other
- No shared state between servers

**Resource Limits:**

- Consider limiting number of concurrent artisan servers
- Monitor total CPU/memory usage

### Dependencies

This feature has no dependencies and can be implemented independently.

**Implementation order:**

- Can be implemented at any time
- Does not depend on other features
- Other features do not depend on this
