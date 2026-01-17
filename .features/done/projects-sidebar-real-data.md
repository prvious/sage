---
name: projects-sidebar-real-data
description: Display real project data in the small Projects sidebar using Inertia shared data
depends_on: null
---

## Feature Description

Currently, the Projects sidebar in `app-sidebar.tsx` renders an empty array (`<ProjectSidebar projects={[]} />`), showing no actual projects to the user. This feature will populate the sidebar with real project data from the database by leveraging Inertia's shared data mechanism through the `HandleInertiaRequests` middleware.

Instead of making API requests, we'll use Inertia's `usePage()` hook to access globally shared data that's automatically available on every page. This approach is more efficient and follows Inertia best practices for sharing common data across all pages.

## Implementation Plan

### Backend Components

**Middleware Updates**:

- Modify `app/Http/Middleware/HandleInertiaRequests.php`
- Add `projects` to the shared data array in the `share()` method
- Query all projects with only necessary fields (id, name, path) for performance
- Consider eager loading relationships if needed in the future

**Database Queries**:

- Use `Project::query()->select(['id', 'name', 'path'])->get()` to fetch minimal data
- No new migrations or schema changes needed

### Frontend Components

**Component Updates**:

- Update `resources/js/components/layout/app-sidebar.tsx`
- Remove hardcoded empty array from `<ProjectSidebar projects={[]} />`
- Access projects from Inertia shared data using `usePage().props`
- Pass the projects data to the `ProjectSidebar` component

**Type Definitions**:

- Define proper TypeScript interface for the shared page props
- Extend or create a `SharedProps` interface that includes `projects`
- Ensure type safety when accessing `usePage().props.projects`

**Component Structure**:

- No changes needed to `ProjectSidebar` component itself (already accepts projects prop)
- Update `AppSidebar` to retrieve and pass data from shared props

### Implementation Steps

1. **Backend**: Update `HandleInertiaRequests` middleware to share projects data
2. **Frontend**: Define TypeScript types for shared props
3. **Frontend**: Update `AppSidebar` to read projects from `usePage().props`
4. **Frontend**: Pass projects data to `ProjectSidebar` component

## Acceptance Criteria

- [ ] Projects are fetched from the database via Inertia middleware shared data
- [ ] The Projects sidebar displays all projects from the database
- [ ] Each project shows its avatar (first letter) and name
- [ ] No API requests are made to fetch projects (uses Inertia shared data)
- [ ] TypeScript types are properly defined for shared props
- [ ] Active project highlighting works correctly based on current URL
- [ ] No console errors or warnings in the browser
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location**: `tests/Feature/Middleware/HandleInertiaRequestsTest.php`

**Key test cases**:

- Test that shared data includes 'projects' key
- Test that projects array contains expected project data (id, name, path)
- Test that projects are properly serialized as JSON
- Test shared data structure matches expected format

### Browser Tests

**Test file location**: `tests/Browser/ProjectSidebarTest.php`

**Key test cases**:

- Test that projects are visible in the sidebar
- Test that project names are displayed correctly
- Test that project avatars show the correct first letter
- Test that hovering over project icons shows tooltip with project name
- Test active state highlighting when on a project-specific page
- Test that sidebar shows placeholder or message when no projects exist
- Test multiple projects are rendered in the correct order

## Code Formatting

Format all code using:

- **Backend (PHP)**: Laravel Pint
    - Command: `vendor/bin/pint --dirty`
- **Frontend (TypeScript/React)**: Prettier
    - Command: `pnpm run format` (or `pnpm prettier --write resources/js`)

## Additional Notes

### Performance Considerations

- Only select necessary columns (id, name, path) to minimize data transfer
- Consider adding caching if the projects list grows very large
- The query runs on every request, but is lightweight with proper column selection

### Future Enhancements

- Add project count badge to show number of active worktrees per project
- Add search/filter functionality for projects when the list grows
- Add "Add New Project" button to the sidebar
- Consider lazy loading if project count exceeds a threshold

### Inertia Best Practices

- Using `usePage().props` is the recommended way to access shared data in Inertia v2
- Shared data is available globally without prop drilling
- This approach is more efficient than making separate API requests
- Data is automatically serialized and type-safe with proper TypeScript definitions
