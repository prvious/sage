---
name: fix-worktrees-page-case-mismatch
description: Fix case sensitivity mismatch between Inertia render path and actual worktrees page files by standardizing on lowercase convention
depends_on: null
---

## Feature Description

There is a case sensitivity mismatch between the Inertia page paths in the backend controller and the actual file locations in the frontend. The `WorktreeController` is rendering pages with capital letters (`Worktrees/Index`, `Worktrees/Create`, `Worktrees/Show`) but the actual files exist with lowercase names (`worktrees/index.tsx`, `worktrees/create.tsx`, `worktrees/show.tsx`).

This causes the error: **"Unhandled Promise Rejection: Error: Page not found: ./pages/Worktrees/Index.tsx"** when clicking the Worktrees link in the sidebar.

**Root Cause**:

- Backend: `Inertia::render('Worktrees/Index')` (capital W, capital I)
- Frontend: `resources/js/pages/worktrees/index.tsx` (lowercase w, lowercase i)

Inertia is case-sensitive and looks for exact path matches.

### Project-Wide Inconsistency

Investigation reveals the project uses **lowercase convention** for most pages:

- ✅ `projects/index.tsx` → `Inertia::render('projects/index')`
- ✅ `environment/index.tsx` → `Inertia::render('environment/index')`
- ✅ `projects/settings.tsx` → `Inertia::render('projects/settings')`
- ✅ `projects/dashboard.tsx` → `Inertia::render('projects/dashboard')`
- ❌ `worktrees/index.tsx` → `Inertia::render('Worktrees/Index')` **MISMATCH**
- ⚠️ `Specs/Index.tsx` → `Inertia::render('Specs/Index')` **Different convention**

**Decision**: Standardize on **lowercase paths** to match the majority of the project and modern JavaScript conventions.

## Implementation Plan

### Recommended Approach: Update Backend to Use Lowercase Paths

**Reasoning**: The project predominantly uses lowercase paths for pages. This matches modern JavaScript/TypeScript conventions and the existing codebase pattern.

**File to modify**: `app/Http/Controllers/WorktreeController.php`

**Changes needed**:

- Line 26: `Inertia::render('Worktrees/Index')` → `Inertia::render('worktrees/index')`
- Line 34: `Inertia::render('Worktrees/Create')` → `Inertia::render('worktrees/create')`
- Line 65: `Inertia::render('Worktrees/Show')` → `Inertia::render('worktrees/show')`

```php
public function index(Project $project): Response
{
    $worktrees = $project->worktrees()
        ->latest()
        ->get();

    return Inertia::render('worktrees/index', [ // Changed from 'Worktrees/Index'
        'project' => $project,
        'worktrees' => $worktrees,
    ]);
}

public function create(Project $project): Response
{
    return Inertia::render('worktrees/create', [ // Changed from 'Worktrees/Create'
        'project' => $project,
    ]);
}

public function show(Project $project, Worktree $worktree): Response
{
    return Inertia::render('worktrees/show', [ // Changed from 'Worktrees/Show'
        'project' => $project,
        'worktree' => $worktree,
    ]);
}
```

### Wayfinder Regeneration

After updating the controller paths, regenerate Wayfinder actions:

```bash
php artisan wayfinder:generate
```

This ensures TypeScript actions in `resources/js/actions/App/Http/Controllers/WorktreeController.ts` reflect the correct paths.

## Acceptance Criteria

- [ ] Clicking "Worktrees" link in sidebar successfully loads the worktrees page
- [ ] No "Page not found" errors in console
- [ ] Worktrees index page displays correctly
- [ ] Worktrees create page is accessible
- [ ] Worktrees show page is accessible
- [ ] All Inertia page paths match actual file locations
- [ ] File naming follows project conventions
- [ ] All existing tests pass
- [ ] No broken imports or references

## Testing Strategy

### Manual Testing Checklist

1. **Test Navigation**:
    - Click "Worktrees" link in sidebar
    - Verify page loads without errors
    - Check browser console for no errors

2. **Test Routes**:
    - Visit `/projects/{project}/worktrees` (index)
    - Visit `/projects/{project}/worktrees/create` (create)
    - Visit `/projects/{project}/worktrees/{worktree}` (show)
    - All should load correctly

3. **Test Wayfinder**:
    - Run `php artisan wayfinder:generate`
    - Verify generated TypeScript actions still work
    - Check `WorktreeController.index()` returns correct path

### Browser Tests

**Test file location**: `tests/Browser/Worktrees/WorktreesNavigationTest.php`

**Key test cases**:

- Test worktrees link in sidebar navigates to correct page
- Test worktrees index page loads successfully
- Test worktrees create page loads successfully
- Test worktrees show page loads successfully
- Test no JavaScript errors on page load

### Feature Tests

**Existing tests should still pass** without modification (since we're only renaming files, not changing functionality):

- Test worktrees index returns correct view
- Test worktrees create returns correct view
- Test worktrees show returns correct view

## Code Formatting

No code formatting needed - this is a file rename operation.

## Additional Notes

### Why This Happened

The mismatch likely occurred during initial development when:

1. Frontend files were created with lowercase convention (modern JS/TS standard)
2. Backend controller used Laravel/Inertia convention (StudlyCase)
3. The issue wasn't caught because tests may not have been run, or the pages weren't tested in a case-sensitive environment

### Project Consistency Check

After fixing this issue, verify other pages follow the same convention:

**Check these existing pages**:

- `resources/js/pages/projects/` - Should match `Projects/` in controllers
- `resources/js/pages/settings/` - Should match `Settings/` in controllers
- `resources/js/pages/dashboard/` - Should match `Dashboard/` in controllers

**Current project structure suggests**:

- Most pages use lowercase: `projects/index.tsx`, `projects/create.tsx`, `settings.tsx`, `dashboard.tsx`
- This indicates the project may be using lowercase for page directories

**Updated recommendation**: Check if other controllers also need updating to use lowercase paths, OR rename all pages to StudlyCase for consistency.

### Investigation Needed

Before implementing, check:

```bash
# List all Inertia render calls
grep -r "Inertia::render" app/Http/Controllers/

# List all page files
find resources/js/pages -type f -name "*.tsx"
```

Compare the two lists to identify all case mismatches, not just Worktrees.

### Broader Fix

If multiple controllers have this issue, consider creating a comprehensive fix that:

1. Establishes a convention (StudlyCase or lowercase)
2. Updates ALL controllers and pages to match
3. Adds linting/testing to prevent future mismatches

### Wayfinder Regeneration

After renaming files or updating paths:

```bash
php artisan wayfinder:generate
```

This ensures TypeScript actions reflect the correct paths.

### Platform Considerations

- **macOS/Windows**: Case-insensitive file systems may not catch this error locally
- **Linux/Production**: Case-sensitive file systems will fail
- **Solution**: Always test on case-sensitive systems or use case-sensitive disk images for development

### Future Prevention

1. **Add test**: Create a test that validates Inertia paths match actual files
2. **Linting**: Add ESLint rule to enforce page naming convention
3. **CI/CD**: Add check in CI pipeline to verify case matches
4. **Documentation**: Document page naming convention in CLAUDE.md or README.md

### Related Issues

This fix may also resolve similar issues with:

- Direct navigation to worktrees pages
- Inertia prefetching of worktrees pages
- Type generation for worktrees props

### Example Test to Prevent Future Issues

```php
// tests/Feature/InertiaPagePathsTest.php
it('ensures all inertia page paths match actual files', function () {
    $controllers = app()->make(\Illuminate\Routing\Router::class)
        ->getRoutes()
        ->get('GET');

    foreach ($controllers as $route) {
        $action = $route->getAction();
        // Extract Inertia::render calls and validate paths exist
        // This test would catch case mismatches automatically
    }
});
```
