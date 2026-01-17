---
name: settings-page
description: General project settings page including server driver management
depends_on: project-context-sidebar
---

## Feature Description

Create a comprehensive settings page where users can manage all project-level configuration options. The primary focus is server management (Caddy/Nginx driver configuration), but the page should be extensible for additional settings categories in the future.

The server management section will allow users to:

- View current server driver (Caddy or Nginx)
- Switch between server drivers
- Configure server-specific settings (ports, domains, TLS)
- View and test server status
- Regenerate server configurations
- View server logs (optional)

This centralizes project configuration and makes server management accessible without editing configuration files manually.

## Implementation Plan

### Backend Components

**Controller**:

- Create `app/Http/Controllers/SettingsController.php`
- Methods:
    - `index(Project $project)` - Display settings page
    - `update(Request $request, Project $project)` - Update general settings
    - `updateServerDriver(Request $request, Project $project)` - Change server driver
    - `testServer(Project $project)` - Test server connectivity
    - `regenerateConfig(Project $project)` - Regenerate server config files

**Actions**:

- Create `app/Actions/Settings/UpdateProjectSettings.php`
- Create `app/Actions/Server/SwitchServerDriver.php`
- Create `app/Actions/Server/TestServerConnection.php`
- Create `app/Actions/Server/RegenerateServerConfig.php`
- Create `app/Actions/Server/GetServerStatus.php`

**Form Request**:

- Create `app/Http/Requests/UpdateProjectSettingsRequest.php`
    - Validate server_driver (caddy, nginx)
    - Validate base_url format
    - Validate port number ranges
    - Validate domain format

**Routes**:

```php
Route::prefix('projects/{project}')->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('projects.settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('projects.settings.update');
    Route::post('/settings/server-driver', [SettingsController::class, 'updateServerDriver'])->name('projects.settings.server-driver');
    Route::post('/settings/test-server', [SettingsController::class, 'testServer'])->name('projects.settings.test-server');
    Route::post('/settings/regenerate-config', [SettingsController::class, 'regenerateConfig'])->name('projects.settings.regenerate-config');
});
```

**Server Driver Integration**:

- Integrate with existing Server Driver system (Caddy/Nginx)
- Use driver pattern from README:
    - `CaddyDriver` - Dynamic vhost generation, automatic TLS
    - `NginxDriver` - Config file management
- Handle driver switching gracefully
- Backup old config before switching
- Clean up old driver config after switch

**Database Updates**:

- Projects table already has these fields (from README data model):
    - `server_driver` - 'caddy' or 'nginx'
    - `base_url` - Base URL for project
- Consider adding:
    - `server_port` - Custom port (optional)
    - `tls_enabled` - Whether to use HTTPS
    - `custom_domain` - Custom domain override

**Server Management Operations**:

- **Switch Driver**: Change from Caddy to Nginx or vice versa
- **Test Server**: Check if server is running and accessible
- **Regenerate Config**: Rebuild server config files
- **View Status**: Show server running status, active vhosts

### Frontend Components

**Page**:

- Create `resources/js/pages/projects/settings.tsx`
- Tabbed interface with sections:
    - **General** - Basic project settings
    - **Server** - Server driver configuration
    - **Advanced** - Advanced options (future)

**Components**:

- Create `resources/js/components/settings/general-settings.tsx` - General project settings
- Create `resources/js/components/settings/server-settings.tsx` - Server configuration
- Create `resources/js/components/settings/server-driver-selector.tsx` - Choose Caddy/Nginx
- Create `resources/js/components/settings/server-status.tsx` - Server status display
- Create `resources/js/components/settings/danger-zone.tsx` - Dangerous operations (delete project)

**General Settings Section**:

- Project name (editable)
- Project path (read-only, with copy button)
- Base URL (editable)
- Description (optional, future)

**Server Settings Section**:

**Server Driver Selection**:

- Radio buttons or cards for Caddy vs Nginx
- Show current driver with status badge
- "Switch Driver" button with confirmation dialog
- Warning about switching drivers (requires regenerating configs)

**Driver-Specific Settings**:

**Caddy Settings**:

- Automatic TLS toggle
- Admin API endpoint
- Custom Caddyfile directives (textarea)

**Nginx Settings**:

- Config file path
- Port configuration
- Custom nginx directives (textarea)

**Server Status**:

- Status indicator (running, stopped, error)
- Server version
- Active vhosts count
- "Test Connection" button
- "Regenerate Config" button

**Preview URLs** (read-only):

- Main project URL
- List of worktree preview URLs

### Styling

**Shadcn Components**:

- Use `Tabs` for settings sections (General, Server, Advanced)
- Use `Card` for each settings group
- Use `Input` for text fields
- Use `RadioGroup` for server driver selection
- Use `Switch` for toggles (TLS enabled, etc.)
- Use `Button` for actions (Save, Test, Regenerate)
- Use `Badge` for status indicators
- Use `Alert` for warnings and errors
- Use `Dialog` for confirmations (switch driver, regenerate config)
- Use `Textarea` for custom directives
- Use `Separator` between sections

**Layout**:

- Sticky save button at top or bottom
- Clear visual separation between sections
- Danger zone at bottom with red border

## Acceptance Criteria

- [ ] Settings page displays at `/projects/{project}/settings`
- [ ] General settings section shows project name and base URL
- [ ] Users can edit project name and base URL
- [ ] Server driver section shows current driver (Caddy or Nginx)
- [ ] Users can switch between Caddy and Nginx drivers
- [ ] Driver switch requires confirmation dialog
- [ ] Driver switch regenerates server configuration
- [ ] Server status displays correctly (running/stopped/error)
- [ ] "Test Connection" button verifies server is accessible
- [ ] "Regenerate Config" button rebuilds server config files
- [ ] Driver-specific settings display based on selected driver
- [ ] Custom server directives can be saved
- [ ] Form validation prevents invalid URLs and ports
- [ ] Changes save successfully with success message
- [ ] Preview URLs display correctly
- [ ] No console errors or warnings
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location**: `tests/Feature/Settings/SettingsControllerTest.php`

**Key test cases**:

- Test settings page renders successfully
- Test updating project name saves to database
- Test updating base URL saves to database
- Test switching from Caddy to Nginx
- Test switching from Nginx to Caddy
- Test server driver switch regenerates config
- Test test server endpoint checks connectivity
- Test regenerate config rebuilds files
- Test validation prevents invalid server driver
- Test validation prevents invalid base URL
- Test validation prevents invalid port numbers
- Test unauthorized access is prevented

**Test file location**: `tests/Feature/Settings/ServerDriverTest.php`

**Key test cases**:

- Test Caddy driver generates correct config
- Test Nginx driver generates correct config
- Test switching drivers cleans up old config
- Test driver switch backs up old config
- Test custom directives are applied
- Test TLS settings are respected

### Browser Tests

**Test file location**: `tests/Browser/SettingsPageTest.php`

**Key test cases**:

- Test navigating to settings page
- Test editing project name and saving
- Test editing base URL and saving
- Test switching to different server driver
- Test confirmation dialog appears before driver switch
- Test confirming driver switch updates UI
- Test "Test Connection" button shows loading state
- Test "Test Connection" displays result (success/failure)
- Test "Regenerate Config" button triggers rebuild
- Test success message appears after config regeneration
- Test server status badge displays correct state
- Test custom directives textarea saves content
- Test validation errors display for invalid input
- Test different tabs work (General, Server)
- Test settings persist across page reloads

## Code Formatting

Format all code using:

- **Backend (PHP)**: Laravel Pint
    - Command: `vendor/bin/pint --dirty`
- **Frontend (TypeScript/React)**: Prettier
    - Command: `pnpm run format`

## Additional Notes

### Server Driver Architecture

Based on README, server drivers follow this pattern:

**Caddy Driver**:

- Dynamic vhost generation
- Automatic TLS via Let's Encrypt
- Admin API for hot reload (prefer over file writes)
- Zero-downtime config updates

**Nginx Driver**:

- Config file management
- Safe append/delete operations
- Don't touch user's existing setup
- Requires reload after config changes

### Driver Switching Process

When switching from one driver to another:

1. **Validation**: Ensure new driver is available/installed
2. **Backup**: Save current driver config to backup
3. **Remove**: Clean up old driver config (safely)
4. **Generate**: Create new driver config
5. **Apply**: Activate new driver config
6. **Verify**: Test new configuration works
7. **Update DB**: Save driver choice to project record

### Server Status Detection

**Check if Caddy is running**:

```php
$process = new Process(['caddy', 'version']);
$process->run();
$caddyInstalled = $process->isSuccessful();

// Check admin API
$response = Http::get('http://localhost:2019/config/');
$caddyRunning = $response->successful();
```

**Check if Nginx is running**:

```php
$process = new Process(['nginx', '-v']);
$process->run();
$nginxInstalled = $process->isSuccessful();

// Check if process is running
$process = new Process(['pgrep', 'nginx']);
$process->run();
$nginxRunning = $process->isSuccessful();
```

### Configuration File Paths

**Caddy**:

- Config file: `/etc/caddy/Caddyfile` or custom path
- Admin API: `http://localhost:2019`
- Generated vhosts: Injected via API or file

**Nginx**:

- Config file: `/etc/nginx/nginx.conf`
- Sites available: `/etc/nginx/sites-available/`
- Sites enabled: `/etc/nginx/sites-enabled/`
- Generated vhosts: `/etc/nginx/sites-available/sage-{project-name}.conf`

### Preview URL Generation

From README, preview URLs follow pattern:

- Main project: `{project-name}.local`
- Worktree: `{branch-name}.{project-name}.local`

Example:

- Project: `myapp.local`
- Feature branch: `feature-auth.myapp.local`

### Regenerating Configuration

**Caddy**:

```php
// Use admin API for hot reload
Http::post('http://localhost:2019/load', $caddyConfig);
```

**Nginx**:

```php
// Write config file
File::put('/etc/nginx/sites-available/sage-project.conf', $nginxConfig);

// Create symlink
File::link(
    '/etc/nginx/sites-available/sage-project.conf',
    '/etc/nginx/sites-enabled/sage-project.conf'
);

// Reload nginx
$process = new Process(['nginx', '-s', 'reload']);
$process->run();
```

### Permissions Considerations

From README "Gotchas":

- Nginx config modification needs appropriate permissions
- May need sudo or user added to www-data group
- Gracefully handle permission errors
- Provide helpful error messages with instructions

### Future Enhancements

- **Database Settings** - Configure database per project/worktree
- **Environment Settings** - Manage environment variables
- **Custom Commands** - Define custom artisan/npm commands
- **Notifications** - Email/Slack notifications for events
- **Backup/Restore** - Backup project settings and restore
- **Import/Export** - Share settings between projects
- **Team Settings** - Manage team member access
- **Webhooks** - Configure webhooks for external integrations
- **Security Settings** - API keys, secrets management
- **Performance Settings** - Caching, queue configuration
- **Logs Viewer** - View server and application logs
- **SSL Certificate Management** - Upload custom certificates

### Danger Zone

Include a "Danger Zone" section at the bottom for destructive operations:

- **Delete Project** - Remove project from Sage (with confirmation)
- **Reset Server Config** - Remove all server configurations
- **Clear Cache** - Clear all project caches

These should be visually distinct (red border) and require confirmation.

### Error Handling

Handle common errors:

- Server driver not installed
- Permission denied for config files
- Server not running
- Invalid configuration syntax
- Port already in use
- Domain conflicts

### Security Considerations

- **Config File Validation**: Validate custom directives to prevent code injection
- **Path Validation**: Prevent directory traversal in config paths
- **Permission Checks**: Verify user has permission before modifying system files
- **Audit Log**: Log all settings changes for security audit
- **Sudo Handling**: If sudo required, provide clear instructions

### Testing Server Configuration

After regenerating config, automatically test:

1. Config file syntax is valid
2. Server can reload without errors
3. Preview URL is accessible
4. TLS certificate is valid (if enabled)

Provide detailed feedback on any failures.
