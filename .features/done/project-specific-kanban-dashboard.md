---
name: project-specific-kanban-dashboard
description: Consolidate to single project-scoped kanban dashboard only
depends_on: null
---

## Feature Description

Remove the global `/dashboard` route entirely and consolidate to a single kanban route at `/projects/{project}/dashboard`. There will be **only ONE kanban page and route** that is always scoped to a specific project.

This feature makes the following changes:

1. Move existing kanban from `/dashboard` to `/projects/{project}/dashboard`
2. Delete the old `/dashboard` route completely
3. Remove `projects.show` route and page
4. Update all redirects and links to use the new project-specific dashboard
5. Update HomeController to redirect to `/projects/{project}/dashboard`

The kanban board will **always** display tasks from exactly one project - there is no "all projects" view.

## Implementation Plan

### Backend Components

- **Controllers**:
    - **DashboardController**:
        - Replace `__invoke()` method with `show(Project $project)` method
        - Accept `Project` parameter via route model binding
        - Filter tasks to only include tasks belonging to the specified project
        - Pass the active project to the view
        - Remove global dashboard logic entirely

    - **ProjectController**:
        - Update `store()` method redirect: `projects.show` → `projects.dashboard`
        - Remove `show()` method completely

    - **HomeController**:
        - Update redirect from `dashboard` to `projects.dashboard`
        - Change line 19: `redirect()->route('dashboard', ['project' => $projectId])` → `redirect()->route('projects.dashboard', $projectId)`

- **Routes** (`routes/web.php`):
    - **Remove**: `Route::get('/dashboard', DashboardController::class)->name('dashboard')`
    - **Remove**: `Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show')`
    - **Add**: `Route::get('/projects/{project}/dashboard', [DashboardController::class, 'show'])->name('projects.dashboard')`

- **Resources**:
    - `TaskResource` should include project relationship data (likely already exists)
    - No changes needed to `ProjectResource`

- **Database changes**: None required

### Frontend Components

- **Pages**:
    - **Delete**: `resources/js/pages/projects/show.tsx` completely
    - **Move**: `resources/js/pages/dashboard/index.tsx` → `resources/js/pages/projects/dashboard.tsx`
    - **Update** the moved dashboard page to:
        - Accept required `project` prop (not optional)
        - Display project name prominently in the header
        - Remove any "all projects" logic/UI elements
        - Accept `tasks` prop that is already filtered by project from backend

- **Components**:
    - Update `KanbanBoard` component (if needed):
        - Component likely doesn't need changes as filtering happens server-side
        - Ensure it displays project context clearly
    - Update any navigation components:
        - Remove links to old `/dashboard` route
        - Remove links to `projects.show` route
        - Add links to `projects.dashboard` where appropriate

- **Routing**:
    - Update all Wayfinder imports throughout codebase:
        - Replace any imports from `@/actions/.../DashboardController` that reference the old `__invoke`
        - Replace `show` from ProjectController with `dashboard` route references
    - Update all `<Link>` components:
        - Change `href="/dashboard"` → `href={projects.dashboard(project.id)}`
        - Change `route('projects.show', project)` → `route('projects.dashboard', project)`

- **Styling**:
    - Display project name badge/header in kanban view
    - Use existing Shadcn components (Badge, Card, etc.)
    - Ensure consistent styling with existing dashboard

### Configuration/Infrastructure

No configuration changes needed.

## Acceptance Criteria

- [ ] The `/dashboard` route returns 404 (route removed completely)
- [ ] The `/projects/{project}/dashboard` route displays the kanban board scoped to that project only
- [ ] Creating a new project redirects to `/projects/{id}/dashboard`
- [ ] The home route `/` redirects to `/projects/{id}/dashboard` for the last opened project
- [ ] The project name is prominently displayed in the dashboard header
- [ ] The `projects.show` route returns 404 (route removed)
- [ ] The `DashboardController@__invoke` method no longer exists (replaced with `show(Project $project)`)
- [ ] The `ProjectController@show` method no longer exists
- [ ] The `resources/js/pages/projects/show.tsx` file is deleted
- [ ] The `resources/js/pages/dashboard/index.tsx` file is moved to `resources/js/pages/projects/dashboard.tsx`
- [ ] The kanban displays only tasks belonging to the specific project
- [ ] Tasks can be created, updated, and moved across columns for the specific project
- [ ] No UI elements reference "all projects" or global dashboard
- [ ] All navigation links use the new `projects.dashboard` route
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location**: `tests/Feature/Dashboard/DashboardControllerTest.php`

- Key test cases:
    - Test `/projects/{project}/dashboard` displays only tasks from that project
    - Test creating project redirects to `/projects/{id}/dashboard`
    - Test `/` redirects to project dashboard for last opened project
    - Test accessing old `/dashboard` route returns 404
    - Test accessing old `/projects/{id}` (show) route returns 404
    - Test task filtering by project returns correct tasks only
    - Test project with no tasks displays empty kanban
    - Test multiple projects have isolated task lists

**Test file location**: `tests/Feature/Projects/ProjectControllerTest.php`

- Key test cases:
    - Test `ProjectController@show` method no longer exists
    - Test project creation redirects to correct dashboard route
    - Update existing tests that reference `projects.show` route

**Test file location**: `tests/Feature/HomeControllerTest.php`

- Key test cases:
    - Test home redirects to `projects.dashboard` (not old `dashboard`)
    - Test home redirects to projects.index if no last opened project

### Browser Tests

**Test file location**: `tests/Browser/Dashboard/ProjectDashboardTest.php`

- Key test cases:
    - Test creating project and verifying redirect to project dashboard
    - Test kanban board shows correct project tasks only (no tasks from other projects)
    - Test drag-and-drop tasks within project dashboard
    - Test project name displays prominently in header
    - Test switching between different project dashboards
    - Test creating tasks from project dashboard adds to correct project
    - Test URL shows `/projects/{id}/dashboard` format

**Test file location**: `tests/Browser/Navigation/DashboardNavigationTest.php`

- Key test cases:
    - Test no navigation links point to old `/dashboard`
    - Test no navigation links point to `projects.show`
    - Test clicking project navigates to `/projects/{id}/dashboard`

## Code Formatting

Format all code using: Laravel Pint (PHP), Prettier (TypeScript/React)

Commands to run:

- `vendor/bin/pint --dirty`
- `pnpm run format` (if configured)

## Additional Notes

### Critical: Single Source of Truth

After this change, `/projects/{project}/dashboard` is the **ONLY** place to view and manage tasks. There is no global task view. This enforces project-scoped workflow.

### Task Filtering Logic

Tasks must be filtered by the project relationship. Ensure the query is optimized with proper eager loading:

```php
// In DashboardController@show(Project $project)
Task::whereHas('worktree', function ($query) use ($project) {
    $query->where('project_id', $project->id);
})
->with(['worktree.project', 'commits'])
->orderBy('created_at', 'desc')
->get()
->groupBy('status');
```

**Important**: Verify the Task → Worktree → Project relationship chain is correct. Check if tasks have a direct `project_id` or only through worktree relationship.

### DashboardController Refactoring

The controller changes from:

```php
// OLD - invokable controller
public function __invoke(): Response
{
    // Shows all tasks from all projects
}
```

To:

```php
// NEW - single method with route model binding
public function show(Project $project): Response
{
    // Shows tasks only from specified project
}
```

### Files to Delete

Ensure these files are completely removed:

1. `resources/js/pages/projects/show.tsx`
2. `resources/js/pages/dashboard/index.tsx` (moved, not deleted)

### Files to Move

1. Move `resources/js/pages/dashboard/index.tsx` → `resources/js/pages/projects/dashboard.tsx`

### Wayfinder Regeneration

After route changes, regenerate Wayfinder types:

```bash
php artisan wayfinder:generate
```

This ensures TypeScript has correct route helpers for the new `projects.dashboard` route.

### Search & Replace Checklist

Search the entire codebase for:

- `route('dashboard')` → replace with `route('projects.dashboard', $project)`
- `route('projects.show')` → replace with `route('projects.dashboard')`
- `href="/dashboard"` → replace with project-specific link
- `@/actions/.../DashboardController` imports → update to reference new method signature

### Future Enhancements

Consider adding in future iterations:

- Project switcher dropdown in dashboard header
- Breadcrumb: Projects → {Project Name} → Dashboard
- Quick stats panel (total tasks, tasks by status) for the project
- Keyboard shortcut to quickly switch projects
