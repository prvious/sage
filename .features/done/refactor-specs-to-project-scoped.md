---
name: refactor-specs-to-project-scoped
description: Refactor specs to be project-scoped and automatically show project specs
depends_on: null
---

## Feature Description

Currently, the specs feature uses global routes (`/specs`) that show all specs from all projects, requiring users to filter or navigate to find specs for a specific project. This refactor will:

1. **Change route structure**: From `/specs` to `/projects/{project}/specs`
2. **Auto-filter specs**: Show only specs for the selected project immediately
3. **Simplify the feature**: Remove unused global pages (keeping project-scoped pages only)
4. **Keep essential functionality**: Maintain spec creation, viewing, editing, generation, and refinement

**Current Flow**:

1. User clicks "Specs" in sidebar → `/specs` (shows ALL specs from ALL projects)
2. User must manually find or filter specs for their project
3. Global context lacks project awareness

**New Flow**:

1. User clicks "Specs" in sidebar → `/projects/{project}/specs` (shows only that project's specs)
2. Specs are automatically filtered to the current project
3. All spec operations are project-scoped

**Similar to Environment Refactor**: This follows the same pattern as the recent environment refactor where we moved from `/environment` to `/projects/{project}/environment`.

## Implementation Plan

### Backend Changes

#### 1. Update Routes

**File**: `routes/web.php`

**Current routes** (lines 50-53):

```php
Route::resource('specs', SpecController::class);
Route::post('/specs/generate', [SpecController::class, 'generate'])->name('specs.generate');
Route::post('/specs/{spec}/refine', [SpecController::class, 'refine'])->name('specs.refine');
```

**New routes**:

```php
Route::prefix('projects/{project}')->name('projects.')->group(function () {
    // ... existing project routes (settings, environment, etc.)

    // Specs routes (project-scoped)
    Route::get('/specs', [SpecController::class, 'index'])->name('specs.index');
    Route::get('/specs/create', [SpecController::class, 'create'])->name('specs.create');
    Route::post('/specs', [SpecController::class, 'store'])->name('specs.store');
    Route::post('/specs/generate', [SpecController::class, 'generate'])->name('specs.generate');
    Route::get('/specs/{spec}', [SpecController::class, 'show'])->name('specs.show');
    Route::get('/specs/{spec}/edit', [SpecController::class, 'edit'])->name('specs.edit');
    Route::put('/specs/{spec}', [SpecController::class, 'update'])->name('specs.update');
    Route::delete('/specs/{spec}', [SpecController::class, 'destroy'])->name('specs.destroy');
    Route::post('/specs/{spec}/refine', [SpecController::class, 'refine'])->name('specs.refine');
});
```

#### 2. Refactor SpecController

**File**: `app/Http/Controllers/SpecController.php`

**Changes to each method**:

1. **Update `index()` method**:
    - Accept `Project $project` parameter
    - Filter specs by project: `Spec::where('project_id', $project->id)`
    - Update Inertia render path from `'Specs/Index'` to `'projects/specs/index'`
    - Pass `$project` in props

2. **Update `create()` method**:
    - Accept `Project $project` parameter
    - Update Inertia render path to `'projects/specs/create'`
    - Pass `$project` in props

3. **Update `store()` method**:
    - Accept `Project $project` parameter
    - Auto-set `project_id` to `$project->id` on spec creation
    - Update redirect to `route('projects.specs.show', [$project, $spec])`

4. **Update `generate()` method**:
    - Accept `Project $project` parameter
    - Associate generated spec with project

5. **Update `show()` method**:
    - Accept `Project $project` parameter (for route consistency)
    - Verify spec belongs to project (security check)
    - Update Inertia render path to `'projects/specs/show'`
    - Pass `$project` in props

6. **Update `edit()` method**:
    - Accept `Project $project` parameter
    - Verify spec belongs to project
    - Update Inertia render path to `'projects/specs/edit'`
    - Pass `$project` in props

7. **Update `update()` method**:
    - Accept `Project $project` parameter
    - Verify spec belongs to project
    - Update redirect to `route('projects.specs.show', [$project, $spec])`

8. **Update `destroy()` method**:
    - Accept `Project $project` parameter
    - Verify spec belongs to project
    - Update redirect to `route('projects.specs.index', $project)`

9. **Update `refine()` method**:
    - Accept `Project $project` parameter
    - Verify spec belongs to project

**New controller signature example**:

```php
public function index(Project $project): Response
{
    $specs = Spec::where('project_id', $project->id)
        ->orderBy('created_at', 'desc')
        ->get();

    return Inertia::render('projects/specs/index', [
        'project' => $project->only(['id', 'name', 'path']),
        'specs' => SpecResource::collection($specs),
    ]);
}
```

### Frontend Changes

#### 1. Move Specs Pages to Project Scope

**Action**: Move all Specs pages to `resources/js/pages/projects/specs/` directory

- Move `Specs/Index.tsx` → `projects/specs/index.tsx`
- Move `Specs/Create.tsx` → `projects/specs/create.tsx`
- Move `Specs/Show.tsx` → `projects/specs/show.tsx`
- Move `Specs/Edit.tsx` → `projects/specs/edit.tsx`

#### 2. Update Each Page Component

**Changes for ALL spec pages**:

1. Already have `AppLayout` wrapper (from previous feature)
2. Update props to receive `project` instead of relying on global context
3. Update any form submissions to use project-scoped routes
4. Update navigation links to include project ID

**Example for `projects/specs/index.tsx`**:

```tsx
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';

interface Spec {
    id: number;
    title: string;
    content: string;
    generated_from_idea: string | null;
    created_at: string;
    updated_at: string;
}

interface Project {
    id: number;
    name: string;
    path: string;
}

interface IndexProps {
    project: Project;
    specs: Spec[];
}

export default function Index({ project, specs }: IndexProps) {
    return (
        <>
            <Head title={`${project.name} - Specs`} />
            <AppLayout>
                <div className='p-6 space-y-6'>
                    <div className='flex items-center justify-between'>
                        <div>
                            <h1 className='text-3xl font-bold'>Feature Specifications</h1>
                            <p className='text-muted-foreground mt-2'>Manage feature specifications for {project.name}</p>
                        </div>
                        <Link href={`/projects/${project.id}/specs/create`}>
                            <Button>Create Spec</Button>
                        </Link>
                    </div>

                    {specs.length === 0 ? (
                        <div className='text-center py-12'>
                            <p className='text-muted-foreground'>No specs yet. Create your first spec!</p>
                        </div>
                    ) : (
                        <div className='grid gap-4'>
                            {specs.map((spec) => (
                                <Card key={spec.id}>
                                    <CardHeader>
                                        <Link href={`/projects/${project.id}/specs/${spec.id}`}>
                                            <CardTitle className='hover:underline'>{spec.title}</CardTitle>
                                        </Link>
                                    </CardHeader>
                                </Card>
                            ))}
                        </div>
                    )}
                </div>
            </AppLayout>
        </>
    );
}
```

#### 3. Update Sidebar Navigation

**File**: `resources/js/components/layout/app-sidebar.tsx`

**Change** (line 46-49):

```tsx
// Before
{
    label: 'Specs',
    icon: FileText,
    href: SpecController.index(),
},

// After
{
    label: 'Specs',
    icon: FileText,
    href: `/projects/${selectedProject.id}/specs`,
},
```

#### 4. Delete Old Specs Directory

After moving files to `projects/specs/`:

```bash
rmdir resources/js/pages/Specs  # Should be empty after moving files
```

### Database Changes

**No schema changes needed** - The `specs` table already has `project_id` column based on the controller code.

However, if there are any specs without a `project_id`, you may want to:

1. **Create a migration** to ensure `project_id` is NOT NULL:

```php
Schema::table('specs', function (Blueprint $table) {
    $table->foreignId('project_id')->nullable(false)->change();
});
```

2. **Clean up orphaned specs** (optional):

```php
// In migration or as a command
Spec::whereNull('project_id')->delete();
```

### Wayfinder Regeneration

After updating routes and controller:

```bash
php artisan wayfinder:generate
```

This ensures TypeScript actions match the new route structure.

### Form Updates

Update any forms that submit to spec routes:

**Spec creation forms**:

```tsx
// Before
<Form action='/specs' method='post'>

// After
<Form action={`/projects/${project.id}/specs`} method='post'>
```

**Spec update forms**:

```tsx
// Before
<Form action={`/specs/${spec.id}`} method='put'>

// After
<Form action={`/projects/${project.id}/specs/${spec.id}`} method='put'>
```

## Acceptance Criteria

- [ ] Route is `/projects/{project}/specs` instead of `/specs`
- [ ] Clicking "Specs" in sidebar loads project-scoped specs immediately
- [ ] Specs index page displays with AppLayout (shows sidebars)
- [ ] Only specs for the selected project are displayed
- [ ] Can create new specs (automatically associated with project)
- [ ] Can view individual specs
- [ ] Can edit existing specs
- [ ] Can delete specs
- [ ] Can generate specs using AI (project-scoped)
- [ ] Can refine specs (project-scoped)
- [ ] Specs from other projects are NOT visible
- [ ] Old routes (`/specs`, `/specs/*`) are removed
- [ ] All navigation links updated to project-scoped routes
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location**: `tests/Feature/Http/Controllers/SpecControllerTest.php`

**Update existing tests to be project-scoped**:

- Test specs index shows only project specs (not all specs)
- Test specs index with empty project (no specs)
- Test create spec with project association
- Test viewing spec belongs to correct project
- Test updating spec verifies project ownership
- Test deleting spec verifies project ownership
- Test generating spec associates with project
- Test refining spec verifies project ownership
- Test unauthorized users cannot access project specs
- Test users cannot access specs from other projects (security)

**Example test**:

```php
it('displays only specs for the specified project', function () {
    $project1 = Project::factory()->create();
    $project2 = Project::factory()->create();

    $spec1 = Spec::factory()->create(['project_id' => $project1->id, 'title' => 'Project 1 Spec']);
    $spec2 = Spec::factory()->create(['project_id' => $project2->id, 'title' => 'Project 2 Spec']);

    $response = $this->actingAs($this->user)
        ->get("/projects/{$project1->id}/specs");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/specs/index')
        ->has('specs', 1) // Should only see 1 spec
        ->where('specs.0.title', 'Project 1 Spec')
    );
});

it('prevents accessing specs from other projects', function () {
    $project1 = Project::factory()->create();
    $project2 = Project::factory()->create();

    $spec = Spec::factory()->create(['project_id' => $project2->id]);

    // Try to access project2's spec via project1's route
    $response = $this->actingAs($this->user)
        ->get("/projects/{$project1->id}/specs/{$spec->id}");

    // Should fail (404 or 403)
    $response->assertNotFound();
});
```

### Browser Tests

**Test file location**: `tests/Browser/Specs/ProjectScopedSpecsTest.php`

**Key test cases**:

- Test clicking "Specs" link navigates to project-scoped route
- Test specs page displays project-specific specs
- Test creating spec from project specs page
- Test spec is associated with correct project
- Test switching projects shows different specs
- Test specs from other projects are not visible

## Code Formatting

Format all code using:

- **Backend (PHP)**: Laravel Pint
    - Command: `vendor/bin/pint --dirty`
- **Frontend (TypeScript/React)**: Prettier/oxfmt
    - Command: `pnpm run format`

## Additional Notes

### Why This Refactor?

**Problems with current implementation**:

1. **Global context**: Shows specs from ALL projects, creating confusion
2. **Extra filtering needed**: Users must manually find their project's specs
3. **Inconsistent routing**: Specs use global routes while other features are project-scoped
4. **Poor UX**: Most users work on one project at a time, don't need to see all specs

**Benefits of refactor**:

1. **Clearer context**: Users immediately see their project's specs
2. **Consistent patterns**: Matches routing pattern of Environment, Settings, and other project features
3. **Better security**: Project scoping enforces access control at route level
4. **Simplified UI**: No need for project filtering or selection

### Similar to Environment Refactor

This refactor follows the EXACT same pattern as the recently completed environment refactor:

| Aspect             | Environment Refactor                     | Specs Refactor                   |
| ------------------ | ---------------------------------------- | -------------------------------- |
| Old Route          | `/environment`                           | `/specs`                         |
| New Route          | `/projects/{project}/environment`        | `/projects/{project}/specs`      |
| Controller Changes | Renamed methods, added Project param     | Add Project param to all methods |
| Frontend Move      | `environment/*` → `projects/environment` | `Specs/*` → `projects/specs/*`   |
| Benefits           | Direct access, project context           | Direct access, project context   |

### Breaking Changes

**Old URLs will no longer work**:

- `/specs` → 404
- `/specs/create` → 404
- `/specs/{id}` → 404

**Mitigation**:

- Update all hardcoded links in codebase
- Bookmarks will break (acceptable for internal tool)
- Consider temporary redirects if needed

### Security Considerations

**Important**: Add authorization checks to ensure:

- Users can only access specs for projects they have access to
- Spec show/edit/update/delete verify spec belongs to the specified project
- This prevents URL manipulation attacks (accessing project1 spec via project2 URL)

**Example security check in controller**:

```php
public function show(Project $project, Spec $spec): Response
{
    // Verify spec belongs to project
    if ($spec->project_id !== $project->id) {
        abort(404);
    }

    // ... rest of method
}
```

### Migration Path

**For existing specs without project_id**:

If there are specs in the database without a `project_id` (which shouldn't happen based on current schema), you'll need to:

1. Identify orphaned specs
2. Either:
    - Assign them to a default project
    - Delete them
    - Prompt admin to manually associate them

### Related Files That May Need Updates

Check these files for hardcoded spec URLs:

- Documentation files (README, AGENTS.md, etc.)
- Test files
- Email templates (if any)
- Frontend components referencing specs

### Performance Considerations

- Filtering specs by `project_id` is indexed (assuming foreign key exists)
- Query should be fast even with many specs
- No pagination needed initially (add later if needed)

### Future Enhancements

After completing this refactor, consider:

- Add spec templates per project
- Add spec search/filter within project
- Add spec tags/categories
- Add spec status tracking (draft, approved, implemented)
- Add spec version history
- Add spec export (markdown, PDF)
- Add AI-powered spec suggestions based on project context
- Add spec linking to tasks/issues

### Development Tips

1. **Test route binding**: Ensure Laravel route model binding works correctly with nested routes
2. **Test security**: Manually try to access specs from other projects
3. **Test navigation**: Click through all spec pages to verify links work
4. **Test forms**: Submit forms to ensure they post to correct project-scoped endpoints
5. **Check Wayfinder**: Verify TypeScript types are generated correctly

### Rollback Plan

If issues arise:

1. Revert route changes in `web.php`
2. Revert controller parameter changes
3. Move frontend files back to `Specs/` directory
4. Restore sidebar navigation link
5. Regenerate Wayfinder types
6. Rebuild frontend
