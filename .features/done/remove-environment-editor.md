---
name: remove-environment-editor
description: Remove Environment page, controller, actions, and all .env file editing functionality
depends_on: null
---

## Feature Description

Remove all Environment (.env file editing) functionality from the application. This includes:

- Environment page UI (`resources/js/pages/projects/environment.tsx`)
- EnvironmentController and its routes
- All Env-related Action classes (`app/Actions/Env/*`)
- Env-related Form Requests
- EnvParser support class
- Environment navigation link in sidebar
- All related tests

**Rationale**: The application is not intended to be a code editor. Users should manage their .env files using their preferred text editor or IDE.

## Current Implementation

The Environment editing system currently includes:

**Backend**:

- `app/Http/Controllers/EnvironmentController.php` - Controller with index, update, restore methods
- `app/Http/Requests/UpdateEnvironmentRequest.php` - Validation for env updates
- `app/Actions/Env/ReadEnvFile.php` - Read and parse .env files
- `app/Actions/Env/WriteEnvFile.php` - Write .env files
- `app/Actions/Env/BackupEnvFile.php` - Create backups of .env files
- `app/Actions/Env/ValidateEnvFile.php` - Validate env variables
- `app/Actions/Env/CompareEnvFiles.php` - Compare env files
- `app/Support/EnvParser.php` - Parse and group env variables
- Routes in `routes/web.php`:
    - `GET /projects/{project}/environment` - projects.environment.index
    - `PUT /projects/{project}/environment` - projects.environment.update
    - `POST /projects/{project}/environment/restore` - projects.environment.restore

**Frontend**:

- `resources/js/pages/projects/environment.tsx` - Environment editor page
- Navigation link in `resources/js/components/layout/app-sidebar.tsx` (line 60-63)

**Tests**:

- `tests/Feature/Http/Controllers/EnvironmentControllerTest.php`
- `tests/Feature/Actions/Env/ReadEnvFileTest.php`
- `tests/Feature/Support/EnvParserTest.php`
- Any other tests referencing environment functionality

## Implementation Plan

### Backend Components

**Files to Delete**:

- `app/Http/Controllers/EnvironmentController.php`
- `app/Http/Requests/UpdateEnvironmentRequest.php`
- `app/Actions/Env/ReadEnvFile.php`
- `app/Actions/Env/WriteEnvFile.php`
- `app/Actions/Env/BackupEnvFile.php`
- `app/Actions/Env/ValidateEnvFile.php`
- `app/Actions/Env/CompareEnvFiles.php`
- `app/Support/EnvParser.php`
- Directory: `app/Actions/Env/` (entire directory)

**Routes to Remove** (`routes/web.php`):

- Remove import: `use App\Http\Controllers\EnvironmentController;`
- Remove routes:
    ```php
    // Environment Routes (project-scoped)
    Route::get('/environment', [EnvironmentController::class, 'index'])->name('environment.index');
    Route::put('/environment', [EnvironmentController::class, 'update'])->name('environment.update');
    Route::post('/environment/restore', [EnvironmentController::class, 'restore'])->name('environment.restore');
    ```

### Frontend Components

**Files to Delete**:

- `resources/js/pages/projects/environment.tsx`

**Files to Modify**:

- `resources/js/components/layout/app-sidebar.tsx`:
    - Remove Environment navigation item (lines 60-63):
        ```tsx
        {
            label: 'Environment',
            icon: Settings,
            href: `/projects/${selectedProject.id}/environment`,
        },
        ```

### Tests to Remove

**Test Files to Delete**:

- `tests/Feature/Http/Controllers/EnvironmentControllerTest.php`
- `tests/Feature/Actions/Env/ReadEnvFileTest.php`
- `tests/Feature/Support/EnvParserTest.php`
- Any other test files in `tests/` that specifically test environment editing

**Search and Clean**:

- Search for any remaining references to:
    - `EnvironmentController`
    - `EnvParser`
    - `ReadEnvFile`, `WriteEnvFile`, `BackupEnvFile`, `ValidateEnvFile`, `CompareEnvFiles`
    - `/environment` routes
    - `environment.tsx` page

### Configuration/Infrastructure

**No Changes Required**:

- No database migrations needed
- No configuration changes needed
- No environment variable changes needed

## Acceptance Criteria

- [ ] EnvironmentController deleted
- [ ] UpdateEnvironmentRequest deleted
- [ ] All Action classes in `app/Actions/Env/` deleted
- [ ] `app/Actions/Env/` directory removed
- [ ] EnvParser support class deleted
- [ ] Environment routes removed from `routes/web.php`
- [ ] EnvironmentController import removed from routes
- [ ] Environment page (`environment.tsx`) deleted
- [ ] Environment navigation link removed from sidebar
- [ ] All Environment-related tests deleted
- [ ] No references to environment editing remain in codebase
- [ ] Application builds without errors
- [ ] All remaining tests pass
- [ ] Code formatted with Pint and Prettier

## Testing Strategy

### Manual Verification

**After Removal**:

- [ ] Run `pnpm run build` - should complete without errors
- [ ] Run `php artisan route:list` - no environment routes should appear
- [ ] Check sidebar navigation - Environment link should not appear
- [ ] Visit project dashboard - all navigation should work
- [ ] Search codebase for `EnvironmentController` - no results
- [ ] Search codebase for `EnvParser` - no results
- [ ] Search codebase for `environment.tsx` - no results

### Test Suite

**Run Full Test Suite**:

```bash
php artisan test --compact
```

All tests should pass after removing environment-related test files.

### Grep Verification

After implementation, verify no references remain:

```bash
# Should return no results
grep -r "EnvironmentController" app/
grep -r "EnvParser" app/
grep -r "environment.tsx" resources/
grep -r "Actions\\\\Env" app/
```

## Code Formatting

**PHP**:

```bash
vendor/bin/pint
```

**TypeScript/React**:

```bash
pnpm run format
```

## Additional Notes

### Removal Order

1. **Frontend First**: Remove environment.tsx page and sidebar link
2. **Routes**: Remove routes from web.php
3. **Controllers**: Delete EnvironmentController
4. **Form Requests**: Delete UpdateEnvironmentRequest
5. **Actions**: Delete all files in `app/Actions/Env/`, then directory
6. **Support**: Delete EnvParser class
7. **Tests**: Delete all environment-related tests
8. **Verification**: Build frontend and run tests

### Why This Order

- Removing frontend first prevents accidental navigation to broken pages
- Removing routes prevents routing errors
- Deleting entire directories at once is cleaner than individual files
- Tests last ensures we can verify nothing breaks during removal

### Search Patterns for Cleanup

After main deletion, search for these patterns to catch any stragglers:

- `use App\Http\Controllers\EnvironmentController`
- `use App\Actions\Env\`
- `use App\Support\EnvParser`
- `'environment'` (route names)
- `/environment` (route paths)
- `environment.index`, `environment.update`, `environment.restore`

### Future Considerations

If .env management is needed in the future:

- Users should use their preferred IDE/editor
- Application can focus on project management features
- Could provide documentation on recommended .env setup
- Could add validation that required env vars exist (without editing)

### Related Features

Check if these features depend on Environment editing (they shouldn't based on architecture):

- Project creation
- Worktree management
- Agent execution
- Task management

None of these should be affected by removing environment editing.
