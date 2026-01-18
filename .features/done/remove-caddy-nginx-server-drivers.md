---
name: remove-caddy-nginx-server-drivers
description: Remove Caddy and Nginx server drivers, keeping only PHP Artisan serve
depends_on: null
---

## Feature Description

This feature simplifies Sage by removing the Caddy and Nginx server driver implementations, keeping only the built-in PHP Artisan serve option. This provides a baseline server management system that works out-of-the-box without external dependencies, making Sage easier to set up and maintain for local development.

### Why This Change?

- **Simpler onboarding**: Users don't need to install/configure Caddy or Nginx
- **Fewer dependencies**: Works with just PHP, no external web servers required
- **Clearer scope**: Focus on core functionality before adding advanced features
- **Easier testing**: Simpler to test with only one server driver
- **Baseline established**: Can extend with Caddy/Nginx later once core features are stable

## Implementation Plan

### Backend Components

**Remove Driver Files:**
- Delete `app/Drivers/Server/CaddyDriver.php`
- Delete `app/Drivers/Server/NginxDriver.php`
- Delete `app/Drivers/CaddyDriver.php` (if duplicate exists)
- Delete `app/Drivers/NginxDriver.php` (if duplicate exists)

**Update Server Driver Manager:**
- Modify `app/Drivers/Server/ServerDriverManager.php`:
  - Remove `createCaddyDriver()` method
  - Remove `createNginxDriver()` method
  - Update `getDefaultDriver()` to return `'artisan'`

**Update Server Driver Interface:**
- Review `app/Drivers/Server/Contracts/ServerDriverInterface.php`
- Consider adding `start()` and `stop()` methods to the interface (currently only in ArtisanDriver)
- Or create a separate interface for drivers that manage processes vs config files

**Update Worktree Observer:**
- Modify `app/Observers/WorktreeObserver.php`:
  - Replace `addVirtualHost()` call with `start()` method for ArtisanDriver
  - Replace `removeVirtualHost()` call with `stop()` method for ArtisanDriver
  - Handle errors appropriately for artisan serve failures

**Remove Server Driver Actions:**
- Delete or update these Action classes:
  - `app/Actions/Server/SwitchServerDriver.php` (likely can be deleted)
  - Review `app/Actions/Server/GetServerStatus.php` (may need updating)
  - Review `app/Actions/Server/RegenerateServerConfig.php` (may not be needed)

**Remove Server Detection Service:**
- Review `app/Services/ServerDetector.php` - may no longer be needed

**Update Controllers:**
- Modify `app/Http/Controllers/SettingsController.php`:
  - Remove server driver switching endpoints
- Modify `app/Http/Controllers/ProjectController.php`:
  - Remove server_driver from creation if it's auto-set to 'artisan'
  - Or keep it but set default to 'artisan' and hide from UI

**Update Request Validation:**
- Modify `app/Http/Requests/StoreProjectRequest.php`:
  - Remove server_driver field OR lock it to 'artisan' value only
- Modify `app/Http/Requests/UpdateProjectRequest.php`:
  - Remove server_driver field
- Modify `app/Http/Requests/UpdateProjectSettingsRequest.php`:
  - Remove server_driver validation

**Update Configuration:**
- Modify `config/sage.php`:
  - Remove `server.caddy` configuration block
  - Remove `server.nginx` configuration block
  - Update `server.default` to return `'artisan'`
  - Keep `artisan_server` configuration block

**Database Changes:**
- Create migration: `2026_01_17_update_projects_remove_server_drivers.php`
  - Update all existing projects to set `server_driver = 'artisan'`
  - Consider adding a check constraint to ensure only 'artisan' is allowed
  - Update any server-related columns that are Caddy/Nginx specific

**Update Factories:**
- Modify `database/factories/ProjectFactory.php`:
  - Set `server_driver` to always be `'artisan'`

### Frontend Components

**Remove Server Driver Selector:**
- Delete `resources/js/components/settings/server-driver-selector.tsx`

**Update Project Creation Page:**
- Modify `resources/js/pages/projects/create.tsx`:
  - Remove the entire Server Driver RadioGroup section (lines ~134-170)
  - Remove server_driver from form submission (it will default on backend)

**Update Settings Pages:**
- Find any settings pages that use `ServerDriverSelector` and remove it
- Search for references to `server-driver-selector` across the frontend

**Update TypeScript Types:**
- Review `resources/js/types/index.d.ts`:
  - Update `Project` interface if `server_driver` type is defined
  - Change from `'caddy' | 'nginx' | 'artisan'` to just `'artisan'` or remove if not exposed

### Routes

**Remove Routes:**
- Check `routes/web.php` for server driver switching routes
- Remove routes like `POST /projects/{project}/settings/server-driver`

## Acceptance Criteria

- [ ] CaddyDriver and NginxDriver files are deleted
- [ ] ServerDriverManager only supports ArtisanDriver
- [ ] WorktreeObserver uses `start()` and `stop()` methods instead of `addVirtualHost()`/`removeVirtualHost()`
- [ ] Project creation defaults to 'artisan' server driver
- [ ] Server driver selection UI is removed from create project page
- [ ] ServerDriverSelector component is removed from settings
- [ ] All existing projects in database are migrated to 'artisan' driver
- [ ] Config file only contains artisan server configuration
- [ ] Creating a new project automatically starts artisan serve
- [ ] Deleting a worktree automatically stops its artisan serve process
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Unit Tests

**Test file location:** `tests/Unit/Drivers/ArtisanDriverTest.php`

Create if missing, ensure it tests:
- `validate()` returns true when PHP is available
- `start()` launches artisan serve on correct port
- `stop()` kills the process on the correct port
- `getAvailablePort()` finds unused ports correctly
- Port conflict handling

### Feature Tests

**Update existing tests:**
- `tests/Feature/Http/Controllers/ProjectControllerTest.php`
  - Remove server driver selection tests
  - Ensure projects default to 'artisan'
- `tests/Feature/Settings/SettingsControllerTest.php`
  - Remove server driver switching tests
- `tests/Feature/Drivers/CaddyDriverTest.php` - **DELETE**
- `tests/Feature/Drivers/NginxDriverTest.php` - **DELETE**
- `tests/Feature/Kanban/KanbanFeatureTest.php`
  - Update to use 'artisan' if it creates projects

**New feature test:** `tests/Feature/Worktrees/WorktreeServerManagementTest.php`
- Test that creating a worktree starts artisan serve
- Test that worktree gets a preview_url with correct port
- Test that deleting a worktree stops the artisan process

### Browser Tests

**Update existing tests:**
- `tests/Browser/Projects/ServerDriverChoiceCardsTest.php` - **DELETE** or update
- `tests/Browser/Projects/ProjectPagesLayoutTest.php`
  - Remove assertions about server driver UI

**Create new browser test:** `tests/Browser/Worktrees/WorktreePreviewUrlTest.php`
- Visit dashboard
- Create a worktree
- Assert preview URL is displayed
- Assert preview URL is accessible (HTTP 200)
- Delete worktree
- Assert preview URL is no longer accessible

## Code Formatting

Format all code using:
- **PHP:** Laravel Pint - `./vendor/bin/pint --dirty`
- **TypeScript/React:** Prettier - `pnpm prettier --write <files>`

## Migration Strategy

Since existing databases may have projects using 'caddy' or 'nginx':

```php
// Migration: 2026_01_17_update_projects_remove_server_drivers.php
public function up(): void
{
    // Update all projects to use artisan driver
    DB::table('projects')->update(['server_driver' => 'artisan']);

    // Optional: Add check constraint (SQLite 3.37.2+ needed)
    // Schema::table('projects', function (Blueprint $table) {
    //     $table->check("server_driver = 'artisan'");
    // });
}

public function down(): void
{
    // Cannot reliably reverse this - previous driver choice is lost
    // Projects remain as 'artisan'
}
```

## Additional Notes

### Future Extension Path

When ready to add Caddy/Nginx back:
1. Re-introduce driver files
2. Update ServerDriverManager
3. Add UI for selection
4. Add configuration sections
5. Update WorktreeObserver to detect driver type and call appropriate methods

### Breaking Changes

This is a **breaking change** for existing users who:
- Have projects configured with Caddy or Nginx
- Rely on automatic HTTPS via Caddy
- Have custom Nginx configurations

**Mitigation:**
- Migration automatically updates all projects to artisan
- Document in changelog that Caddy/Nginx support is temporarily removed
- Provide timeline for when it will be added back

### Security Considerations

- Artisan serve is for development only - not production
- Document clearly that Sage is for local development
- Preview URLs are only accessible on localhost by default

### Performance Considerations

- Multiple artisan serve processes (one per worktree) consume more memory than single Caddy/Nginx instance
- Document recommended worktree limits (e.g., max 10 active worktrees)
- Consider adding a warning when too many worktrees are active

### Edge Cases

1. **Port exhaustion:** If user creates 1000+ worktrees, ports may run out
   - Solution: Clean up old worktrees automatically, or warn user
2. **Process orphans:** If Sage crashes, artisan processes may not be killed
   - Solution: On startup, check for orphaned processes and clean them up
3. **Windows compatibility:** Process management differs on Windows
   - ArtisanDriver already handles this with `PHP_OS_FAMILY` checks
