---
name: fix-project-list-links
description: Fix broken project links to point to dashboard instead of non-existent show route
depends_on: null
---

## Feature Description

The project list page at `/projects` currently has broken links. When users click on a project card, the link points to `/projects/{project.id}` which doesn't exist. The `projects.show` route was intentionally excluded from the resource routes, and the corresponding page component was deleted.

Projects should link to the project dashboard at `/projects/{project}/dashboard` instead, which is the correct destination for viewing project details.

## Implementation Plan

### Backend Components

No backend changes required - routes are already configured correctly.

### Frontend Components

- **Pages**: `resources/js/pages/projects/index.tsx` - Update the Link href on line 124 to use Wayfinder
- **Components**: None - only import and prop change needed
- **Routing**: Already correct - `projects.dashboard` route exists and works
- **Wayfinder**: Import the dashboard route function for type-safe navigation

### Changes Required

1. Add Wayfinder import at the top of the file:

    ```tsx
    import { show as projectDashboard } from '@/actions/App/Http/Controllers/DashboardController';
    ```

    OR use the named route approach:

    ```tsx
    import { dashboard } from '@/routes/projects';
    ```

2. Update the `<Link>` component's `href` prop from string template to Wayfinder function:

    ```tsx
    // Before:
    <Link key={project.id} href={`/projects/${project.id}`} className='block group'>

    // After (using controller import):
    <Link key={project.id} href={projectDashboard.url(project.id)} className='block group'>

    // OR (using named route):
    <Link key={project.id} href={dashboard.url(project.id)} className='block group'>
    ```

3. Verify Wayfinder has generated the types by running:
    ```bash
    php artisan wayfinder:generate
    ```

## Acceptance Criteria

- [ ] Wayfinder route helper is imported from the correct location
- [ ] Link uses `.url()` method from Wayfinder for type safety
- [ ] TypeScript has no errors (types are properly generated)
- [ ] Clicking on a project card from `/projects` navigates to `/projects/{id}/dashboard`
- [ ] The link shows the correct URL on hover (browser status bar)
- [ ] Navigation preserves project state and data
- [ ] No 404 errors when clicking project cards
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Browser Tests

**Test file location**: `tests/Browser/Projects/ProjectListNavigationTest.php`

**Key test cases**:

1. Test clicking a project card navigates to the correct dashboard URL
2. Test hovering over a project card shows the correct href in the link
3. Test that project data is properly displayed on the dashboard after navigation
4. Test navigation from search results also works correctly

**Example test**:

```php
it('navigates to project dashboard when clicking project card', function () {
    $project = Project::factory()->create([
        'name' => 'Test Project',
    ]);

    visit('/projects')
        ->assertSee('Test Project')
        ->click('@project-card-' . $project->id) // or use text selector
        ->assertUrlIs("/projects/{$project->id}/dashboard")
        ->assertSee('Test Project') // Verify dashboard loads
        ->assertNoJavascriptErrors();
});

it('shows correct dashboard URL on hover', function () {
    $project = Project::factory()->create(['name' => 'Hover Test']);

    $page = visit('/projects');

    // Find the link element and check href attribute
    $href = $page->attribute('a:contains("Hover Test")', 'href');
    expect($href)->toContain("/projects/{$project->id}/dashboard");
});
```

### Feature Tests

**Test file location**: `tests/Feature/Projects/ProjectNavigationTest.php`

**Key test cases**:

1. Verify the projects.dashboard route accepts the correct parameters
2. Ensure the route returns a successful response

**Example test**:

```php
it('can access project dashboard route', function () {
    $project = Project::factory()->create();

    $response = $this->get(route('projects.dashboard', $project));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) =>
        $page->component('projects/dashboard')
            ->has('project')
    );
});
```

## Code Formatting

Format all code using: **Prettier** and **Laravel Pint**

Commands to run:

- Frontend: `pnpm run format` (if configured) or rely on automatic formatting
- Backend: `./vendor/bin/pint --dirty`

## Additional Notes

### Root Cause

The `projects.show` route was intentionally removed from the routes file using `->except(['show'])` because the application uses a dedicated dashboard view instead. The frontend link was not updated when this architectural decision was made.

### Why Wayfinder?

Following the project's Wayfinder guidelines from CLAUDE.md:

- Provides type safety between backend routes and frontend code
- Automatic synchronization - if route changes, TypeScript will catch errors
- Tree-shakable named imports improve bundle size
- Prevents hardcoded URL strings that can become stale

### Wayfinder Import Options

1. **Controller-based import** (preferred for tree-shaking):

    ```tsx
    import { show as projectDashboard } from '@/actions/App/Http/Controllers/DashboardController';
    projectDashboard.url(project.id); // Returns: "/projects/1/dashboard"
    ```

2. **Named route import** (alternative):
    ```tsx
    import { dashboard } from '@/routes/projects';
    dashboard.url(project.id); // Returns: "/projects/1/dashboard"
    ```

Both approaches are valid. Controller-based imports are preferred per CLAUDE.md guidelines for better tree-shaking.

### Alternative Approaches Considered

1. **Add back the show route**: Not recommended - the dashboard is the intended single view for projects
2. **Hardcoded URL string**: Not recommended - violates Wayfinder best practices, loses type safety
3. **Redirect show to dashboard**: Unnecessary complexity - better to fix the link directly

### Related Files

- `routes/web.php:19` - Shows `->except(['show'])` on resource route
- `routes/web.php:17` - Defines the correct `projects.dashboard` route
- `app/Http/Controllers/DashboardController.php` - Handles the dashboard view
- Git status shows `resources/js/pages/projects/show.tsx` was deleted (marked with `D`)

### Link Location

Line 124 in `resources/js/pages/projects/index.tsx`:

**Current (broken)**:

```tsx
<Link key={project.id} href={`/projects/${project.id}`} className='block group'>
```

**Should become (using Wayfinder)**:

```tsx
// After adding import: import { show as projectDashboard } from '@/actions/App/Http/Controllers/DashboardController'
<Link key={project.id} href={projectDashboard.url(project.id)} className='block group'>
```

**Benefits of this approach**:

- Type-safe: TypeScript will error if route parameters don't match
- Auto-sync: Running `php artisan wayfinder:generate` keeps frontend routes in sync
- No magic strings: Route changes in backend automatically update frontend types
- Tree-shakable: Only imports what's needed
