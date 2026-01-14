---
name: last-opened-project
description: Store and redirect to user's last opened project
depends_on: null
---

## Feature Description

Implement a system to track and remember the last project a user was working on. When a user navigates to the root URL ("/"), they will be automatically redirected to the tasks page of their last opened project. If no project has been opened yet, they will be redirected to the projects list page.

This improves the user experience by allowing users to quickly resume work on their current project without having to navigate through the project list every time they visit the application.

## Implementation Plan

### Backend Components

**Controllers:**

- Modify `app/Http/Controllers/ProjectController.php` - Update methods to track last opened project
- Create `app/Http/Controllers/HomeController.php` - Handle root "/" route and redirection logic

**Services/Actions:**

- Create `app/Actions/UpdateLastOpenedProject.php` - Reusable action to update the last opened project in cache
- Create `app/Actions/GetLastOpenedProject.php` - Retrieve the last opened project from cache

**Routes:**

- Add `GET /` route to `routes/web.php` that handles redirection
- The route should call HomeController to determine redirect destination

**Database Changes:**

- No migrations needed - using existing `cache` table
- Cache key format: `last_opened_project:{session_id}` or `last_opened_project:guest`
- Cache value: project ID (integer)
- TTL: 30 days (configurable via config file)

**Configuration:**

- Add `config/sage.php` entry for cache TTL: `'last_project_ttl' => env('LAST_PROJECT_TTL', 60 * 24 * 30)` (30 days in minutes)

### Frontend Components

**Middleware/Events:**

- Create middleware `app/Http/Middleware/TrackLastOpenedProject.php` - Automatically update last opened project when viewing project-related pages
- Apply middleware to project routes

**Inertia Integration:**

- No frontend changes needed - all logic handled server-side via redirects

### Cache Strategy

**Cache Key Structure:**

```php
// Use session ID as the key to track per-session without requiring authentication
$cacheKey = 'last_opened_project:' . session()->getId();
```

**Cache Storage:**

- Store project ID as integer value
- Set TTL to 30 days (configurable)
- Use Laravel's Cache facade for consistency

**Update Triggers:**

- Middleware detects when user visits:
    - `/projects/{project}/tasks` (task list)
    - `/projects/{project}` (project show page)
    - `/projects/{project}/edit` (project edit page)
    - Any route with `{project}` parameter in URL
- Action updates cache with current project ID

### Redirection Logic

**Root Route ("/") Handler:**

1. Get session ID
2. Check cache for last opened project using session key
3. If project ID found in cache:
    - Verify project still exists in database
    - Redirect to `/projects/{project}/tasks`
4. If no project ID found or project doesn't exist:
    - Redirect to `/projects` (project list)

**Edge Cases:**

- If cached project was deleted: redirect to `/projects`
- If session expired: treat as new session, redirect to `/projects`
- If cache service unavailable: gracefully fall back to `/projects`

## Acceptance Criteria

- [ ] Root route "/" successfully redirects to last opened project's tasks
- [ ] Root route "/" redirects to projects list when no last opened project exists
- [ ] Last opened project is updated when viewing project tasks page
- [ ] Last opened project is updated when viewing project details page
- [ ] Last opened project is updated when clicking project in sidebar
- [ ] Cache key uses session ID for per-session tracking
- [ ] Cache TTL is configurable via environment variable
- [ ] Deleted projects don't cause errors (graceful fallback to projects list)
- [ ] Cache failures don't break the application (fallback to projects list)
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location:** `tests/Feature/LastOpenedProjectTest.php`

**Key test cases:**

- Test root route redirects to last opened project tasks when project exists in cache
- Test root route redirects to projects list when no cached project exists
- Test root route redirects to projects list when cached project was deleted
- Test visiting project tasks page updates cache with correct project ID
- Test visiting project show page updates cache with correct project ID
- Test visiting project edit page updates cache with correct project ID
- Test cache key uses correct session ID format
- Test cache TTL is set correctly (30 days default)
- Test multiple projects can be tracked (last one wins)
- Test cache survives across multiple requests within same session

### Unit Tests

**Test file location:** `tests/Unit/Actions/LastOpenedProjectTest.php`

**Key test cases:**

- Test `UpdateLastOpenedProject` action stores correct project ID in cache
- Test `UpdateLastOpenedProject` action uses correct cache key format
- Test `UpdateLastOpenedProject` action sets correct TTL
- Test `GetLastOpenedProject` action retrieves correct project ID from cache
- Test `GetLastOpenedProject` action returns null when no cache entry exists
- Test `GetLastOpenedProject` action handles cache misses gracefully

### Browser Tests

**Test file location:** `tests/Browser/LastOpenedProjectTest.php`

**Key test cases:**

- Test user visits "/", gets redirected to projects list (no cached project)
- Test user clicks project in sidebar, then visits "/", gets redirected to that project's tasks
- Test user visits project A, then project B, then "/", gets redirected to project B's tasks
- Test user visits project, deletes it, then visits "/", gets redirected to projects list
- Test redirection maintains session across page loads

## Code Formatting

Format all code using: **Prettier** and **oxfmt** (for JavaScript/TypeScript), **Pint** (for PHP)

Commands to run:

- `pnpm run format` - Format JavaScript/TypeScript files
- `vendor/bin/pint --dirty` - Format PHP files

## Additional Notes

### Security Considerations

- Cache key includes session ID to prevent cross-session data leaks
- No authentication required since this is session-scoped
- Project existence is verified before redirect to prevent invalid states

### Performance Considerations

- Cache lookups are very fast (single key-value retrieval)
- No database queries on root route if project exists in cache
- Single query to verify project existence before redirect
- Middleware only runs on project-related routes (minimal overhead)

### Configuration Options

Add to `config/sage.php`:

```php
return [
    // ... existing config

    /*
    |--------------------------------------------------------------------------
    | Last Opened Project Cache TTL
    |--------------------------------------------------------------------------
    |
    | This value determines how long (in minutes) the last opened project
    | will be remembered in the cache. Default is 30 days.
    |
    */
    'last_project_ttl' => env('LAST_PROJECT_TTL', 60 * 24 * 30), // 30 days
];
```

### Alternative Cache Key Strategies (Not Implemented)

If the app later adds user authentication, consider migrating to:

```php
// User-based cache key (requires auth)
$cacheKey = 'last_opened_project:user:' . auth()->id();
```

This would allow the last opened project to persist across devices for authenticated users.

### Middleware Implementation Strategy

The middleware should be applied to specific route groups:

```php
// In bootstrap/app.php or routes/web.php
Route::middleware(['web', 'track.last.project'])->group(function () {
    Route::resource('projects', ProjectController::class);
    // ... other project routes
});
```

### Potential Future Enhancements (Out of Scope)

- Store multiple recent projects (last 5) instead of just one
- Add UI indicator showing which project was last opened
- Add "Continue where you left off" banner on projects list
- Track last opened page within project (not just tasks)
- Add keyboard shortcut to jump to last project (e.g., Cmd+L)
- Sync across devices for authenticated users
