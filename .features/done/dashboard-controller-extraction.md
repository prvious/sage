---
name: dashboard-controller-extraction
description: Extract dashboard route logic into DashboardController invokable
depends_on: null
---

## Feature Description

Currently, the dashboard route in `routes/web.php` contains inline logic for fetching projects and tasks. This feature extracts that logic into a dedicated `DashboardController` invokable controller, following Laravel best practices and maintaining consistency with the existing `HomeController` pattern.

The dashboard displays:

- All projects (ordered by name)
- Tasks grouped by status (idea, in_progress, review, done)
- Uses Eloquent API Resources for consistent data transformation

## Implementation Plan

### Backend Components

- **Controllers**: Create `App\Http\Controllers\DashboardController` as an invokable controller
    - Single `__invoke()` method
    - Return type: `\Inertia\Response`
    - Query projects and tasks with appropriate eager loading
    - Transform data using existing `ProjectResource` and `TaskResource`

- **Routes**: Update `routes/web.php`
    - Replace closure with `[DashboardController::class, '__invoke']`
    - Keep the named route `'dashboard'`

- **Models**: No changes needed
    - Continue using `Project` and `Task` models
    - Maintain existing relationships

- **Services/Actions**: No additional actions needed
    - Logic is straightforward querying and transformation
    - Does not warrant extraction into an Action class

### Configuration/Infrastructure

- No environment variables needed
- No third-party integrations
- No build/deployment changes

## Acceptance Criteria

- [x] `DashboardController` created as an invokable controller with `__invoke()` method
- [x] Route in `web.php` updated to use `DashboardController::class`
- [x] Named route `'dashboard'` preserved
- [x] Dashboard displays projects ordered by name
- [x] Dashboard displays tasks grouped by status (idea, in_progress, review, done)
- [x] Uses `ProjectResource::collection()` for projects
- [x] Uses `TaskResource::collection()` for each task status group
- [x] Eager loads `worktree.project` and `commits` relationships on tasks (prevents N+1)
- [x] All tests pass
- [x] Code is formatted according to project standards (Laravel Pint)

## Testing Strategy

### Feature Tests

**Test file location**: `tests/Feature/Dashboard/DashboardControllerTest.php`

Key test cases:

- **Test dashboard renders successfully**
    - Visit `/dashboard` route
    - Assert successful response
    - Assert Inertia page component is `'dashboard/index'`

- **Test dashboard includes projects**
    - Create multiple projects
    - Visit dashboard
    - Assert projects are present in Inertia props
    - Assert projects are ordered by name

- **Test dashboard includes tasks grouped by status**
    - Create tasks with different statuses (idea, in_progress, review, done)
    - Visit dashboard
    - Assert tasks are grouped correctly in Inertia props
    - Assert each status group contains correct tasks

- **Test dashboard eager loads task relationships**
    - Create tasks with worktrees and commits
    - Assert no N+1 query issues when rendering dashboard
    - Use `\DB::enableQueryLog()` to verify relationship loading

### Browser Tests (Optional Enhancement)

**Test file location**: `tests/Browser/DashboardTest.php`

Key test cases:

- Verify dashboard page loads and displays project cards
- Verify kanban board shows tasks in correct columns
- Verify no JavaScript errors on page load

## Code Formatting

Format all code using: **Laravel Pint**

Command to run: `vendor/bin/pint --dirty`

## Additional Notes

### Design Decisions

**Why invokable controller?**

- Single route endpoint (`GET /dashboard`)
- No CRUD operations (index, show, create, etc.)
- Follows pattern established by `HomeController`
- Keeps route definition clean and type-safe

**Why not extract to an Action?**

- Logic is simple: query models and transform via resources
- No business logic or complex operations
- No need for reusability across multiple controllers
- Follows "avoid over-engineering" principle from CLAUDE.md

### Alternative Approaches Considered

1. **Regular controller with `index()` method**
    - More traditional resource controller pattern
    - Would allow future expansion (show, create, etc.)
    - Overkill for single route endpoint
    - **Not chosen**: Invokable is more appropriate

2. **Extract queries into Action classes**
    - Would follow the Action pattern guidance
    - Could be reused if dashboard logic grows
    - Adds unnecessary abstraction layer
    - **Not chosen**: Premature optimization

### Migration Path

1. Create `DashboardController` with identical logic from closure
2. Update route to use new controller
3. Run tests to ensure no regressions
4. Format code with Pint
5. Delete no code (route closure is replaced, not deleted)

### Edge Cases

- Empty states already handled by frontend (React components)
- N+1 queries prevented by eager loading `with(['worktree.project', 'commits'])`
- Task grouping handles missing status keys via `$tasks->get('status', collect())`
