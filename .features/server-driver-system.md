---
name: server-driver-system
description: Pluggable server drivers (Caddy/Nginx) for dynamic vhost management
depends_on: git-worktree-management
---

## Detailed Description

This feature implements a driver-based system for managing web server configurations. It allows Sage to dynamically create and remove virtual host configurations for preview URLs without disrupting the user's existing server setup.

### Key Capabilities
- Pluggable driver architecture using Laravel's Manager pattern
- **Caddy Driver**: Dynamic vhost generation via Caddy Admin API (no file writes, zero-downtime)
- **Nginx Driver**: Config file management with safe append/delete operations
- Automatic TLS certificates for local development (via Caddy)
- Detection of which server is running on the system
- Validation that server is properly configured
- Graceful error handling if server is unavailable

### Driver Responsibilities
Each driver must implement:
- `addVirtualHost(string $domain, string $documentRoot): bool` - Add new preview URL
- `removeVirtualHost(string $domain): bool` - Remove preview URL
- `listVirtualHosts(): array` - List all managed domains
- `isAvailable(): bool` - Check if server is running and accessible
- `testConfiguration(): bool` - Validate server can be managed by Sage

### Technical Considerations
- **Caddy**: Use Admin API (`http://localhost:2019/config/`) for hot-reload without restarts
- **Nginx**: Write to separate config file (e.g., `/etc/nginx/sage.d/`) and reload nginx
- **Permissions**: May need sudo or specific user groups for Nginx file writes
- **Validation**: Check server responds to test domains before marking as ready
- **Rollback**: If config fails, revert to previous working state

## Detailed Implementation Plan

### Step 1: Create Server Driver Manager
```bash
php artisan make:class Services/ServerDriverManager --no-interaction
```

Extend `Illuminate\Support\Manager`:
```php
class ServerDriverManager extends Manager
{
    public function createCaddyDriver(): CaddyDriver
    {
        return $this->container->make(CaddyDriver::class);
    }

    public function createNginxDriver(): NginxDriver
    {
        return $this->container->make(NginxDriver::class);
    }

    public function getDefaultDriver(): string
    {
        return config('sage.server.default', 'caddy');
    }
}
```

### Step 2: Create Server Driver Contract
```bash
php artisan make:interface Contracts/ServerDriver --no-interaction
```

Define interface:
```php
interface ServerDriver
{
    public function addVirtualHost(string $domain, string $documentRoot, int $port = 8000): bool;
    public function removeVirtualHost(string $domain): bool;
    public function listVirtualHosts(): array;
    public function isAvailable(): bool;
    public function testConfiguration(): bool;
    public function getServerInfo(): array;
}
```

### Step 3: Implement Caddy Driver
```bash
php artisan make:class Drivers/CaddyDriver --no-interaction
```

**Key Methods:**

**addVirtualHost:**
- Use Caddy Admin API: `POST http://localhost:2019/config/apps/http/servers/sage/routes`
- Build route config JSON with:
  - Match: `{"host": ["feature-auth.myapp.local"]}`
  - Handle: reverse proxy to `localhost:8000` (Octane port for that worktree)
  - TLS: automatic via Caddy's auto-HTTPS
- Make HTTP request using Laravel's HTTP client
- Validate response (200 OK = success)

**removeVirtualHost:**
- Use Caddy Admin API: `DELETE http://localhost:2019/config/apps/http/servers/sage/routes/{id}`
- Find route ID by domain first
- Delete route
- Validate removal

**isAvailable:**
- Check if `http://localhost:2019/config/` responds
- Return true if Caddy is reachable

**testConfiguration:**
- Add a test vhost
- Try to reach it
- Remove test vhost
- Return success/failure

### Step 4: Implement Nginx Driver
```bash
php artisan make:class Drivers/NginxDriver --no-interaction
```

**Key Methods:**

**addVirtualHost:**
- Generate Nginx server block config:
```nginx
server {
    listen 80;
    server_name feature-auth.myapp.local;

    location / {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```
- Write to `/etc/nginx/sage.d/{domain}.conf`
- Test config: `nginx -t`
- Reload: `nginx -s reload` or `systemctl reload nginx`
- Handle permission errors gracefully

**removeVirtualHost:**
- Delete config file `/etc/nginx/sage.d/{domain}.conf`
- Test config: `nginx -t`
- Reload nginx

**isAvailable:**
- Check if nginx is running: `systemctl is-active nginx` or `pgrep nginx`
- Check if sage.d directory exists and is writable

**testConfiguration:**
- Try writing a test config file
- Test nginx config syntax
- Remove test file
- Return success/failure

### Step 5: Create Configuration File
Create `config/sage.php`:
```php
return [
    'server' => [
        'default' => env('SAGE_SERVER_DRIVER', 'caddy'),

        'caddy' => [
            'admin_url' => env('CADDY_ADMIN_URL', 'http://localhost:2019'),
            'server_name' => 'sage',
        ],

        'nginx' => [
            'config_path' => env('NGINX_SAGE_CONFIG_PATH', '/etc/nginx/sage.d'),
            'reload_command' => env('NGINX_RELOAD_COMMAND', 'nginx -s reload'),
            'test_command' => 'nginx -t',
        ],
    ],
];
```

### Step 6: Create Server Driver Facade
```bash
php artisan make:class Facades/ServerDriver --no-interaction
```

Register facade in `config/app.php` aliases.

### Step 7: Integrate with Worktree Creation

Update `WorktreeObserver`:
```php
public function created(Worktree $worktree): void
{
    ServerDriver::driver($worktree->project->server_driver)
        ->addVirtualHost(
            $worktree->preview_url,
            $worktree->path . '/public'
        );
}

public function deleted(Worktree $worktree): void
{
    ServerDriver::driver($worktree->project->server_driver)
        ->removeVirtualHost($worktree->preview_url);
}
```

### Step 8: Create Server Detection Service
```bash
php artisan make:class Services/ServerDetector --no-interaction
```

**Detect available servers:**
- Check for Caddy: Try hitting admin API
- Check for Nginx: Look for nginx binary and running process
- Return array of available drivers
- Suggest best driver based on what's available

### Step 9: Add Artisan Command for Testing
```bash
php artisan make:command TestServerDriver --no-interaction
```

Command: `php artisan sage:test-server {driver?}`
- Test if server driver is available
- Test configuration
- Add/remove test vhost
- Report results

### Step 10: Create Feature Tests

Test coverage:
- `it('caddy driver can add virtual host via API')`
- `it('caddy driver can remove virtual host')`
- `it('nginx driver can generate correct config')`
- `it('nginx driver can add and remove config files')`
- `it('detects available server drivers correctly')`
- `it('handles permission errors gracefully')`
- `it('rolls back on configuration errors')`

**Note:** Mock external HTTP calls and file system operations in tests.

### Step 11: Create Unit Tests for Config Generation

Test coverage:
- `it('generates valid caddy JSON config')`
- `it('generates valid nginx server block')`
- `it('handles special characters in domain names')`
- `it('sets correct proxy headers')`

### Step 12: Add Documentation

Create `docs/server-drivers.md`:
- Explain how server drivers work
- Setup instructions for Caddy
- Setup instructions for Nginx
- Troubleshooting permission issues
- How to add custom drivers

### Step 13: Format Code
```bash
vendor/bin/pint --dirty
```
