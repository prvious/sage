---
name: project-context-sidebar
description: Context-aware sidebar navigation that displays project-specific links
depends_on: projects-sidebar-real-data
---

## Feature Description

The main sidebar (second sidebar panel) currently shows static "Favorites" links that aren't functional. This feature will transform it into a dynamic, context-aware navigation system that displays project-specific links based on the currently selected project.

When a user selects a project from the left sidebar (Projects sidebar), the main sidebar will update to show navigation items relevant to that specific project, including Worktrees, Tasks (Kanban), Specs, Terminal, .env manager, Context editor, Agent management, and Settings.

## Implementation Plan

### Backend Components

**Middleware Updates**:

- Modify `app/Http/Middleware/HandleInertiaRequests.php`
- Add `selectedProject` to shared data when on project-specific routes
- Determine current project from route parameters or session

**Route Structure**:

- All project-specific routes should follow pattern: `/projects/{project}/*`
- Update existing routes to be project-scoped where applicable
- Create new route group for project-specific navigation

**Controllers** (to be created in subsequent features):

- Terminal controller (new)
- Context editor controller (new)
- Agent management controller (new)
- Settings controller (new)

### Frontend Components

**Component Updates**:

- Update `resources/js/components/layout/app-sidebar.tsx`
- Replace static `navigationItems` array with dynamic project-aware navigation
- Use `usePage().props.selectedProject` to get current project context
- Update navigation items to use Wayfinder-generated route helpers

**Navigation Items Structure**:

```typescript
interface NavigationItem {
    label: string;
    icon: LucideIcon;
    href: string | RouteObject;
    badge?: string | number; // For counts (e.g., number of worktrees)
}
```

**Project-specific navigation items**:

1. **Worktrees** - `/projects/{project}/worktrees` (existing)
2. **Tasks** - `/projects/{project}/tasks` (existing, shows Kanban)
3. **Specs** - `/projects/{project}/specs` (existing)
4. **Terminal** - `/projects/{project}/terminal` (new)
5. **.env** - `/projects/{project}/environment` (existing)
6. **Context** - `/projects/{project}/context` (new)
7. **Agent** - `/projects/{project}/agent` (new)
8. **Settings** - `/projects/{project}/settings` (new)

**Active State**:

- Highlight current navigation item based on current URL
- Use `usePage().url` to determine active item

**Empty State**:

- Show placeholder or welcome message when no project is selected
- Guide user to select a project from the left sidebar

### Implementation Steps

1. **Backend**: Update routes to be project-scoped
2. **Backend**: Share `selectedProject` in Inertia middleware
3. **Frontend**: Define TypeScript types for navigation structure
4. **Frontend**: Create dynamic navigation items based on selected project
5. **Frontend**: Implement active state highlighting
6. **Frontend**: Add empty state for when no project is selected
7. **Frontend**: Add badges/counts where applicable (e.g., worktree count)

## Acceptance Criteria

- [ ] Sidebar navigation updates when a project is selected
- [ ] All navigation items link to correct project-specific routes
- [ ] Active navigation item is highlighted based on current URL
- [ ] Navigation shows appropriate badges (counts) where applicable
- [ ] Empty state displays when no project is selected
- [ ] Navigation items use appropriate icons from lucide-react
- [ ] Clicking navigation items navigates using Inertia (no full page reload)
- [ ] TypeScript types are properly defined for navigation structure
- [ ] Works seamlessly with existing routes (Worktrees, Tasks, Specs, .env)
- [ ] No console errors or warnings
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location**: `tests/Feature/Middleware/HandleInertiaRequestsTest.php`

**Key test cases**:

- Test that `selectedProject` is shared when on project routes
- Test that `selectedProject` is null when not on project routes
- Test project is correctly identified from route parameters

**Test file location**: `tests/Feature/Projects/ProjectNavigationTest.php`

**Key test cases**:

- Test project-specific routes are accessible
- Test routes require valid project ID
- Test 404 for non-existent projects
- Test route parameter binding works correctly

### Browser Tests

**Test file location**: `tests/Browser/ProjectSidebarNavigationTest.php`

**Key test cases**:

- Test clicking a project updates the main sidebar navigation
- Test navigation items are visible when project is selected
- Test empty state displays when no project is selected
- Test clicking navigation items navigates to correct pages
- Test active state highlighting works correctly
- Test navigation persists across page navigations
- Test badges display correct counts (e.g., worktree count)
- Test different projects show same navigation structure
- Test responsive behavior on mobile viewports

## Code Formatting

Format all code using:

- **Backend (PHP)**: Laravel Pint
    - Command: `vendor/bin/pint --dirty`
- **Frontend (TypeScript/React)**: Prettier
    - Command: `pnpm run format`

## Additional Notes

### Project Context Detection

The sidebar should determine the current project from:

1. Route parameters (e.g., `/projects/{project}/worktrees`)
2. Fallback to last selected project in session/cookie
3. Default to null if no project context exists

### Navigation Item Icons

Use appropriate lucide-react icons:

- Worktrees: `GitBranch`
- Tasks: `CheckSquare` or `KanbanSquare`
- Specs: `FileText`
- Terminal: `Terminal`
- .env: `Settings` or `FileCode`
- Context: `FileEdit` or `BookOpen`
- Agent: `Bot` or `Cpu`
- Settings: `Settings`

### Badge Examples

- Worktrees: Show count of active worktrees
- Tasks: Show count of pending tasks
- .env: Show indicator if .env has unsaved changes

### Future Enhancements

- Add search functionality within navigation
- Add recently accessed items
- Add favorite/pin functionality for specific pages
- Add keyboard shortcuts for navigation (e.g., Cmd+K)
- Add breadcrumb trail showing project hierarchy

### Integration with Existing Features

This feature updates the navigation structure but relies on existing controllers:

- `WorktreeController` for Worktrees
- `TaskController` for Tasks/Kanban
- `SpecController` for Specs
- `EnvironmentController` for .env

New controllers will be created in subsequent features:

- `TerminalController` for Terminal
- `ContextController` for Context editor
- `AgentController` updates for Agent management
- `SettingsController` for Settings
