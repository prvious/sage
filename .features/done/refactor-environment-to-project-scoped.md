---
name: refactor-environment-to-project-scoped
description: Refactor environment manager to be project-scoped and show .env editor immediately
depends_on: null
---

## Feature Description

Currently, the environment manager has a global index page that lists all projects and worktrees, requiring users to select what they want to edit. This creates unnecessary navigation steps. This refactor will:

1. **Change route structure**: From `/environment` to `/projects/{project}/environment`
2. **Show editor immediately**: Load the project's .env file directly without requiring selection
3. **Simplify the feature**: Remove unused pages (index, compare) that add complexity
4. **Keep essential functionality**: Maintain the .env editor and update functionality

**Current Flow**:

1. User clicks "Environment" in sidebar → `/environment` (index page)
2. Index page shows list of all projects and worktrees
3. User clicks on a project → `/environment/project/{project}` (show page with editor)

**New Flow**:

1. User clicks "Environment" in sidebar → `/projects/{project}/environment` (editor loads immediately)

**Pages to Keep**:

- `show.tsx` - The .env editor (will become the main page)

**Pages to Remove**:

- `index.tsx` - Global project/worktree selection page (no longer needed)
- `compare.tsx` - Comparison functionality (out of scope for MVP)

**Controller Methods to Keep/Refactor**:

- `showProject()` → becomes `index()`
- `update()` - Keep as-is
- `restore()` - Keep as-is

**Controller Methods to Remove**:

- `index()` - Global listing (no longer needed)
- `showWorktree()` - Worktree-specific view (out of scope)
- `sync()` - Sync to worktrees (out of scope)
- `compare()` - Comparison view (out of scope)

## Implementation Plan

### Backend Changes

#### 1. Update Routes

**File**: `routes/web.php`

**Current routes** (lines 35-43):

```php
Route::prefix('environment')->name('environment.')->group(function () {
    Route::get('/', [EnvironmentController::class, 'index'])->name('index');
    Route::get('/project/{project}', [EnvironmentController::class, 'showProject'])->name('project.show');
    Route::get('/worktree/{worktree}', [EnvironmentController::class, 'showWorktree'])->name('worktree.show');
    Route::post('/update', [EnvironmentController::class, 'update'])->name('update');
    Route::post('/sync', [EnvironmentController::class, 'sync'])->name('sync');
    Route::get('/compare/{project}/{worktree}', [EnvironmentController::class, 'compare'])->name('compare');
    Route::post('/restore', [EnvironmentController::class, 'restore'])->name('restore');
});
```

**New routes**:

```php
Route::prefix('projects/{project}')->name('projects.')->group(function () {
    Route::get('/environment', [EnvironmentController::class, 'index'])->name('environment.index');
    Route::put('/environment', [EnvironmentController::class, 'update'])->name('environment.update');
    Route::post('/environment/restore', [EnvironmentController::class, 'restore'])->name('environment.restore');
});
```

#### 2. Refactor EnvironmentController

**File**: `app/Http/Controllers/EnvironmentController.php`

**Changes**:

1. **Remove methods**:
    - Delete `index()` method (lines 23-51)
    - Delete `showWorktree()` method (lines 96-133)
    - Delete `sync()` method (lines 162-217)
    - Delete `compare()` method (lines 222-256)

2. **Rename `showProject()` to `index()`**:
    - Change method name from `showProject` to `index`
    - Update Inertia render path from `'environment/show'` to `'projects/environment'`
    - Keep all the logic for reading and validating .env file

3. **Update `update()` method**:
    - Change to use `PUT` method (already uses UpdateEnvironmentRequest)
    - Update redirect to use new route: `route('projects.environment.index', $project)`
    - Accept `Project $project` parameter from route

4. **Update `restore()` method**:
    - Accept `Project $project` parameter from route
    - Update redirect to use new route: `route('projects.environment.index', $project)`

**New controller structure**:

```php
<?php

namespace App\Http\Controllers;

use App\Actions\Env\BackupEnvFile;
use App\Actions\Env\ReadEnvFile;
use App\Actions\Env\ValidateEnvFile;
use App\Actions\Env\WriteEnvFile;
use App\Http\Requests\UpdateEnvironmentRequest;
use App\Models\Project;
use App\Support\EnvParser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EnvironmentController extends Controller
{
    /**
     * Display the project's .env editor
     */
    public function index(Project $project): Response
    {
        $envPath = $project->path.'/.env';
        $readEnvFile = new ReadEnvFile;
        $validateEnvFile = new ValidateEnvFile;

        try {
            $variables = $readEnvFile->handle($envPath);
            $grouped = EnvParser::groupBySection($variables);
            $errors = $validateEnvFile->handle($variables);
            $missing = $validateEnvFile->checkRequired($variables);

            return Inertia::render('projects/environment', [
                'project' => $project,
                'variables' => $variables,
                'grouped' => $grouped,
                'errors' => $errors,
                'missing' => $missing,
                'env_path' => $envPath,
            ]);
        } catch (\Exception $e) {
            return Inertia::render('projects/environment', [
                'project' => $project,
                'env_path' => $envPath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update the project's .env file
     */
    public function update(UpdateEnvironmentRequest $request, Project $project): RedirectResponse
    {
        $envPath = $project->path.'/.env';
        $variables = $request->input('variables');

        $backupEnvFile = new BackupEnvFile;
        $writeEnvFile = new WriteEnvFile;

        try {
            // Create backup before modification
            $backupEnvFile->handle($envPath);

            // Write the updated variables
            $writeEnvFile->handle($envPath, $variables);

            return redirect()
                ->route('projects.environment.index', $project)
                ->with('success', 'Environment file updated successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Restore from a backup
     */
    public function restore(UpdateEnvironmentRequest $request, Project $project): RedirectResponse
    {
        $backupPath = $request->input('backup_path');
        $targetPath = $project->path.'/.env';

        try {
            if (! file_exists($backupPath)) {
                throw new \RuntimeException('Backup file not found');
            }

            // Create a backup of current file before restoring
            $backupEnvFile = new BackupEnvFile;
            $backupEnvFile->handle($targetPath);

            // Copy backup to target
            copy($backupPath, $targetPath);

            return redirect()
                ->route('projects.environment.index', $project)
                ->with('success', 'Environment file restored successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
```

### Frontend Changes

#### 1. Move and Refactor Environment Page

**Action**: Move `resources/js/pages/environment/show.tsx` to `resources/js/pages/projects/environment.tsx`

**File**: `resources/js/pages/projects/environment.tsx`

**Changes**:

1. Add `AppLayout` wrapper for consistent sidebar navigation
2. Update props to receive `project` instead of `source`
3. Remove worktree-specific UI (compare button, worktree badge)
4. Remove "Back to Environment Manager" link (no longer needed)
5. Simplify header to show project name
6. Update form submit to use new route

**New implementation**:

```tsx
import { Head } from '@inertiajs/react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { AppLayout } from '@/components/layout/app-layout';
import EnvVariableForm from '@/components/env-variable-form';
import { AlertCircle, Download } from 'lucide-react';

interface EnvVariable {
    value: string;
    comment?: string | null;
    is_sensitive: boolean;
}

interface Project {
    id: number;
    name: string;
    path: string;
}

interface Props {
    project: Project;
    variables?: Record<string, EnvVariable>;
    grouped?: Record<string, EnvVariable>;
    errors?: Record<string, string[]>;
    missing?: string[];
    env_path: string;
    error?: string;
}

export default function Environment({ project, variables, grouped, errors, missing, env_path, error }: Props) {
    return (
        <>
            <Head title={`${project.name} - Environment`} />
            <AppLayout>
                <div className='p-6 space-y-6'>
                    <div>
                        <h1 className='text-3xl font-bold'>Environment Variables</h1>
                        <p className='text-muted-foreground mt-2'>Manage environment variables for {project.name}</p>
                        <p className='text-xs text-muted-foreground mt-1'>{env_path}</p>
                    </div>

                    {error && (
                        <Alert variant='destructive'>
                            <AlertCircle className='h-4 w-4' />
                            <AlertDescription>{error}</AlertDescription>
                        </Alert>
                    )}

                    {missing && missing.length > 0 && (
                        <Alert>
                            <AlertCircle className='h-4 w-4' />
                            <AlertDescription>Missing required variables: {missing.join(', ')}</AlertDescription>
                        </Alert>
                    )}

                    {grouped && <EnvVariableForm grouped={grouped} envPath={env_path} projectId={project.id} />}
                </div>
            </AppLayout>
        </>
    );
}
```

#### 2. Update EnvVariableForm Component

**File**: `resources/js/components/env-variable-form.tsx`

**Changes**:

1. Accept `projectId` prop
2. Update form submit route to use `route('projects.environment.update', projectId)`
3. Change method to `PUT`

#### 3. Delete Unused Pages

**Files to delete**:

```bash
rm resources/js/pages/environment/index.tsx
rm resources/js/pages/environment/compare.tsx
rmdir resources/js/pages/environment  # If empty after moving show.tsx
```

#### 4. Update Sidebar Navigation

**File**: `resources/js/components/layout/app-sidebar.tsx`

**Change** (line 52-55):

```tsx
// Before
{
    label: 'Environment',
    icon: Settings,
    href: EnvironmentController.index(),
},

// After
{
    label: 'Environment',
    icon: Settings,
    href: `/projects/${selectedProject.id}/environment`,
},
```

Or use Wayfinder after regenerating:

```tsx
{
    label: 'Environment',
    icon: Settings,
    href: EnvironmentController.index(selectedProject.id),
},
```

### TypeScript Types

**File**: `resources/js/types/index.d.ts`

**Remove or update**:

- Remove `Source` interface (if only used for environment)
- Update environment props interface

### Wayfinder Regeneration

After updating routes and controller:

```bash
php artisan wayfinder:generate
```

This ensures TypeScript actions match the new route structure.

## Acceptance Criteria

- [ ] Route is `/projects/{project}/environment` instead of `/environment`
- [ ] Clicking "Environment" in sidebar loads .env editor immediately
- [ ] Environment page displays with AppLayout (shows sidebars)
- [ ] .env variables load and display correctly
- [ ] Can edit and save environment variables
- [ ] Backup is created before each update
- [ ] Restore functionality works
- [ ] Validation errors display correctly
- [ ] Missing required variables warning displays
- [ ] Page works for projects without .env file (shows error)
- [ ] Old routes (`/environment`, `/environment/project/{project}`) are removed
- [ ] Unused pages (index, compare) are deleted
- [ ] Unused controller methods are removed
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location**: `tests/Feature/Environment/EnvironmentControllerTest.php`

**Key test cases**:

- Test environment index shows project .env variables
- Test environment index handles missing .env file gracefully
- Test update saves variables and creates backup
- Test update redirects back to environment page
- Test restore functionality works correctly
- Test validation errors are returned
- Test unauthorized users cannot access environment page
- Test project-specific scoping (can't access other project's environment)

### Browser Tests

**Test file location**: `tests/Browser/Environment/EnvironmentEditorTest.php`

**Key test cases**:

- Test clicking "Environment" in sidebar loads editor immediately
- Test environment page displays with sidebars
- Test .env variables render correctly
- Test can edit variable value
- Test can save changes
- Test validation errors display
- Test missing variables warning displays
- Test restore backup button works
- Test page is responsive on mobile/desktop

### Manual Testing Checklist

1. **Navigation**:
    - Click "Environment" link in sidebar
    - Verify editor loads immediately (no selection page)
    - Verify URL is `/projects/{project}/environment`

2. **Editor Functionality**:
    - View existing variables
    - Edit a variable value
    - Save changes
    - Verify changes persist

3. **Error Handling**:
    - Test with missing .env file
    - Test with invalid .env syntax
    - Verify error messages display

4. **Backup/Restore**:
    - Make changes (creates backup)
    - Restore from backup
    - Verify restore works

## Code Formatting

Format all code using:

- **Backend (PHP)**: Laravel Pint
    - Command: `vendor/bin/pint --dirty`
- **Frontend (TypeScript/React)**: Prettier/oxfmt
    - Command: `pnpm run format`

## Additional Notes

### Why This Refactor?

**Problems with current implementation**:

1. **Extra navigation step**: Users must click through index page
2. **Unused features**: Compare and sync features add complexity without clear value
3. **Inconsistent routing**: Environment uses global routes while other features are project-scoped
4. **Poor UX**: Most users just want to edit their project's .env, not compare across worktrees

**Benefits of refactor**:

1. **Faster workflow**: Edit .env in one click from sidebar
2. **Simpler codebase**: Remove unused features and complexity
3. **Consistent patterns**: Matches routing pattern of other project features
4. **Better UX**: Direct access to what users actually need

### Removed Features

**Features being removed** (can be added back later if needed):

1. **Global environment index**: List of all projects/worktrees
2. **Worktree .env editing**: Worktree-specific environment management
3. **Compare functionality**: Side-by-side comparison of .env files
4. **Sync functionality**: Syncing variables from project to worktrees

These features are out of scope for the MVP. Focus is on the core use case: editing a project's .env file.

### Future Enhancements

After completing this refactor, consider:

- Add .env template/example file generation
- Add variable search/filter
- Add variable import/export (JSON, YAML)
- Add sensitive variable masking in UI
- Add .env file history/versioning
- Add validation rules for specific variables (URL format, required values, etc.)
- Add ability to add comments to variables
- Add grouping/sections for variables

### Existing Actions to Keep

The refactor keeps all existing Action classes (they're reusable):

- `App\Actions\Env\BackupEnvFile` - Create backup before changes
- `App\Actions\Env\ReadEnvFile` - Parse .env file
- `App\Actions\Env\ValidateEnvFile` - Validate variables
- `App\Actions\Env\WriteEnvFile` - Write variables to .env
- `App\Support\EnvParser` - Parse and group variables

### Migration Path

No database migrations needed - this is purely a routing/controller refactor.

### Backwards Compatibility

**Breaking changes**:

- Old URLs (`/environment`, `/environment/project/{project}`) will no longer work
- Direct links to environment pages will need updating
- Bookmarks will break (acceptable for internal tool)

**Mitigation**:

- Consider adding temporary redirects if needed
- Update any hardcoded links in codebase

### Related Files That May Need Updates

Check these files for hardcoded environment URLs:

- Documentation files (README, AGENTS.md, etc.)
- Test files
- Email templates (if any)
- Frontend components

### Security Considerations

- Ensure project-scoped authorization (users can only access their projects)
- Sensitive variables should be masked in UI
- Backups should have proper permissions
- Validate file paths to prevent directory traversal
- Ensure .env file changes are auditable (backups serve as audit trail)

### Performance Considerations

- .env file reading is synchronous (acceptable for small files)
- Consider caching parsed variables if file is large
- Backup creation adds minimal overhead
- No database queries needed (file-based)

### Error Handling

Ensure proper error messages for:

- .env file not found
- .env file not readable
- Invalid .env syntax
- Disk space issues (backups)
- Permission issues

### Development Tips

1. Test with different .env file states:
    - Missing file
    - Empty file
    - File with syntax errors
    - File with missing required variables
    - File with sensitive variables

2. Verify backups are created correctly
3. Test restore functionality thoroughly
4. Ensure proper file permissions after writes
