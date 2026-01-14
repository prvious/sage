---
name: consolidate-dashboard-tasks
description: Merge tasks and dashboard routes to use /dashboard for kanban board
depends_on: nested-sidebar-layout
---

## Feature Description

Consolidate the `/dashboard` and `/tasks` routes by making `/dashboard` the primary location for the Kanban board. Currently, there is duplication where:

- `/dashboard` renders a dashboard page with the nested sidebar layout and Kanban board
- `/tasks` has a TaskController that also renders a Kanban board at `Kanban/Index`

This refactor will:

1. Merge the TaskController's Kanban board logic into the dashboard route
2. Remove the duplicate `/tasks` index route
3. Keep task CRUD operations (store, update, destroy) under `/tasks` resource routes
4. Keep agent-related task operations (start, stop, output) under `/tasks` prefix
5. Make `/dashboard` the single source of truth for viewing the Kanban board

This creates a clearer separation: `/dashboard` is for viewing and managing tasks via Kanban, while `/tasks/*` routes handle task API operations.

## Implementation Plan

### Backend Components

**Controllers:**

- Modify dashboard route in `routes/web.php` - Move TaskController's index logic to dashboard route
- Modify `app/Http/Controllers/TaskController.php` - Remove `index()` and `show()` methods, keep store/update/destroy
- Keep `app/Http/Controllers/AgentController.php` - No changes needed

**Routes:**

- Modify `routes/web.php` - Update dashboard route to fetch and pass tasks data
- Remove `Route::resource('tasks', TaskController::class)` and replace with explicit routes:
    - `POST /tasks` → TaskController@store
    - `PATCH /tasks/{task}` → TaskController@update
    - `DELETE /tasks/{task}` → TaskController@destroy
- Keep existing agent routes under `/tasks` prefix (start, stop, output)

**Resources:**

- Use existing `app/Http/Resources/TaskResource.php` for serializing tasks data

**No Database Changes:**

- No migrations needed
- Using existing tasks table structure

### Frontend Components

**Pages:**

- Keep `resources/js/pages/dashboard/index.tsx` - Already has AppLayout + KanbanBoard
- Delete `resources/js/pages/Kanban/Index.tsx` - No longer needed (duplicate)
- No need to create task detail pages (handled by Kanban modals/dialogs)

**Components:**

- Keep `resources/js/components/layout/app-layout.tsx` - Nested sidebar layout
- Keep `resources/js/components/kanban/board.tsx` - Kanban board component
- Keep other kanban components (column, card, etc.)

**Routing:**

- All Inertia navigation should point to `/dashboard` for viewing tasks
- Task actions (create, update, delete, start, stop) will use POST/PATCH/DELETE to `/tasks/*`

### Route Consolidation Strategy

**Before:**

```php
// Dashboard with sidebar + kanban
Route::get('/dashboard', ...)->name('dashboard');

// Tasks with separate kanban page
Route::resource('tasks', TaskController::class);
// Generates: tasks.index, tasks.create, tasks.store, tasks.show, tasks.edit, tasks.update, tasks.destroy

// Agent operations
Route::prefix('tasks')->name('tasks.')->group(function () {
    Route::post('{task}/start', [AgentController::class, 'start'])->name('start');
    Route::post('{task}/stop', [AgentController::class, 'stop'])->name('stop');
    Route::get('{task}/output', [AgentController::class, 'output'])->name('output');
});
```

**After:**

```php
// Dashboard with kanban board (primary view)
Route::get('/dashboard', function () {
    $projects = Project::select('id', 'name', 'path')
        ->orderBy('name')
        ->get();

    $tasks = Task::with(['worktree.project', 'commits'])
        ->orderBy('created_at', 'desc')
        ->get()
        ->groupBy('status');

    return Inertia::render('dashboard/index', [
        'projects' => ProjectResource::collection($projects),
        'tasks' => [
            'idea' => TaskResource::collection($tasks->get('idea', collect())),
            'in_progress' => TaskResource::collection($tasks->get('in_progress', collect())),
            'review' => TaskResource::collection($tasks->get('review', collect())),
            'done' => TaskResource::collection($tasks->get('done', collect())),
        ],
    ]);
})->name('dashboard');

// Task API routes (no index/show - handled by dashboard)
Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

// Agent operations (unchanged)
Route::prefix('tasks')->name('tasks.')->group(function () {
    Route::post('{task}/start', [AgentController::class, 'start'])->name('start');
    Route::post('{task}/stop', [AgentController::class, 'stop'])->name('stop');
    Route::get('{task}/output', [AgentController::class, 'output'])->name('output');
});
```

### Controller Refactoring

**TaskController.php After:**

```php
class TaskController extends Controller
{
    /**
     * Store a newly created task.
     */
    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $task = Task::create($request->validated());

        return redirect()->route('dashboard')
            ->with('success', 'Task created successfully.');
    }

    /**
     * Update the specified task.
     */
    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $task->update($request->validated());

        return redirect()->route('dashboard')
            ->with('success', 'Task updated successfully.');
    }

    /**
     * Remove the specified task.
     */
    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Task deleted successfully.');
    }
}
```

### Data Flow

**Dashboard Data Flow:**

1. User navigates to `/dashboard`
2. Route handler fetches projects (for sidebar) and tasks (for kanban)
3. Tasks are grouped by status (idea, in_progress, review, done)
4. Data passed to `dashboard/index` Inertia page
5. AppLayout receives projects, KanbanBoard receives tasks
6. User interacts with kanban board (drag, edit, delete)
7. Actions POST/PATCH/DELETE to `/tasks/*` routes
8. Redirects back to `/dashboard` with success message

## Acceptance Criteria

- [ ] `/dashboard` route displays Kanban board with all tasks grouped by status
- [ ] `/dashboard` route includes projects data for sidebar navigation
- [ ] `/tasks` index route is removed (no longer accessible via GET /tasks)
- [ ] Task creation POSTs to `/tasks` and redirects to `/dashboard`
- [ ] Task updates PATCH to `/tasks/{task}` and redirects to `/dashboard`
- [ ] Task deletion DELETEs to `/tasks/{task}` and redirects to `/dashboard`
- [ ] Agent operations (start, stop, output) remain under `/tasks/{task}/*` routes
- [ ] `resources/js/pages/Kanban/Index.tsx` is deleted (no longer used)
- [ ] TaskController no longer has `index()` or `show()` methods
- [ ] All navigation links/forms point to `/dashboard` instead of `/tasks`
- [ ] Sidebar navigation includes "Dashboard" link to `/dashboard`
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location:** `tests/Feature/Dashboard/DashboardTest.php`

**Key test cases:**

- Test dashboard route returns successful response with tasks data
- Test dashboard route includes projects data for sidebar
- Test dashboard route groups tasks by status correctly
- Test dashboard route handles empty tasks gracefully
- Test `/tasks` index route returns 404 or redirects (no longer exists)
- Test task creation redirects to dashboard
- Test task update redirects to dashboard
- Test task deletion redirects to dashboard
- Test agent routes still work (start, stop, output)

### Browser Tests

**Test file location:** `tests/Browser/Dashboard/DashboardKanbanTest.php`

**Key test cases:**

- Test dashboard displays kanban board with columns
- Test dashboard displays tasks in correct columns based on status
- Test creating a new task from dashboard
- Test updating a task from dashboard
- Test deleting a task from dashboard
- Test starting an agent from dashboard
- Test stopping an agent from dashboard
- Test dragging task between columns updates status
- Test sidebar navigation to dashboard works
- Test no broken links to old `/tasks` route

### Cleanup Tests

**Test file location:** `tests/Feature/Tasks/TaskRoutesTest.php`

**Key test cases:**

- Test GET `/tasks` returns 404 or redirects
- Test POST `/tasks` works (create)
- Test PATCH `/tasks/{task}` works (update)
- Test DELETE `/tasks/{task}` works (destroy)
- Test POST `/tasks/{task}/start` works (agent start)
- Test POST `/tasks/{task}/stop` works (agent stop)
- Test GET `/tasks/{task}/output` works (agent output)

## Code Formatting

Format all code using: **Prettier** and **oxfmt** (for JavaScript/TypeScript), **Pint** (for PHP)

Commands to run:

- `pnpm run format` - Format JavaScript/TypeScript files
- `vendor/bin/pint --dirty` - Format PHP files

## Additional Notes

### Migration Strategy

**Step-by-step implementation:**

1. Update dashboard route in `routes/web.php` to fetch tasks data
2. Pass tasks data to existing `dashboard/index` page (already has KanbanBoard)
3. Update KanbanBoard component to accept and display tasks prop
4. Remove `Route::resource('tasks', ...)` line
5. Add explicit POST/PATCH/DELETE routes for tasks
6. Update TaskController: remove `index()`, update redirects to `dashboard`
7. Delete `resources/js/pages/Kanban/Index.tsx` file
8. Update any navigation links/forms to use `/dashboard`
9. Update tests to reflect new routing structure

### Backward Compatibility

**Breaking Changes:**

- `/tasks` (GET) will no longer work - returns 404
- Any bookmarks or hard-coded links to `/tasks` will break
- Solution: Add a redirect from `/tasks` to `/dashboard` if needed

**Non-Breaking:**

- POST/PATCH/DELETE to `/tasks` still work (API routes)
- Agent operations still work (unchanged)
- Task model and database structure unchanged

### Route Naming Consistency

Keep named routes consistent:

- `dashboard` → GET /dashboard
- `tasks.store` → POST /tasks
- `tasks.update` → PATCH /tasks/{task}
- `tasks.destroy` → DELETE /tasks/{task}
- `tasks.start` → POST /tasks/{task}/start
- `tasks.stop` → POST /tasks/{task}/stop
- `tasks.output` → GET /tasks/{task}/output

### Why This Refactor?

**Problems with current structure:**

1. Duplication: Both `/dashboard` and `/tasks` show Kanban board
2. Confusion: Users don't know which route to use
3. Maintenance: Two places to keep Kanban logic in sync
4. Navigation: Sidebar needs to link to both or choose one

**Benefits of consolidation:**

1. Single source of truth: `/dashboard` is the main view
2. Clearer purpose: Dashboard = view, Tasks = API operations
3. Simpler navigation: One link in sidebar ("Dashboard")
4. Better UX: Users land on dashboard and see everything
5. Easier to maintain: One Kanban implementation

### Alternative Approach (Not Recommended)

Keep both routes but redirect `/tasks` to `/dashboard`:

```php
Route::get('/tasks', function () {
    return redirect()->route('dashboard');
});
```

**Why not recommended:**

- Adds unnecessary redirect layer
- Doesn't remove duplication in TaskController
- Still confusing for developers

### Future Considerations

If specific task detail pages are needed later:

```php
Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
```

This would show a dedicated page for a single task (not Kanban view).

### Dependencies

This feature depends on `nested-sidebar-layout` because:

- Dashboard page uses AppLayout component (nested sidebars)
- Projects data is needed for sidebar navigation
- Kanban board is rendered within the sidebar layout

**Implementation order:**

1. Complete `nested-sidebar-layout` first
2. Then implement `consolidate-dashboard-tasks`
