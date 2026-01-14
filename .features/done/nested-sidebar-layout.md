---
name: nested-sidebar-layout
description: Collapsible nested sidebars with project list and kanban board
depends_on: null
---

## Feature Description

Create a main application layout using Shadcn's sidebar-09 pattern featuring collapsible nested sidebars. The layout will have:

1. **Mini Left Sidebar (Collapsed State)**: A slim sidebar showing a list of projects from the database
2. **Main Sidebar**: A full-width sidebar with navigation and dummy data
3. **Main Content Area**: Contains the Kanban board with dummy data

This provides the foundation for the Sage dashboard's navigation structure, allowing users to quickly switch between projects while maintaining access to primary navigation.

## Implementation Plan

### Frontend Components

**Pages:**

- `resources/js/pages/dashboard/index.tsx` - Main dashboard page with sidebar layout

**Components:**

- Install Shadcn sidebar-09 component via `pnpm dlx shadcn@latest add sidebar-09`
- `resources/js/components/layout/app-layout.tsx` - Wrapper component for the nested sidebar layout
- `resources/js/components/layout/project-sidebar.tsx` - Mini left sidebar showing project list
- `resources/js/components/layout/main-sidebar.tsx` - Main navigation sidebar
- `resources/js/components/kanban/board.tsx` - Kanban board component (dummy data)
- `resources/js/components/kanban/column.tsx` - Kanban column component
- `resources/js/components/kanban/card.tsx` - Kanban card component

**Additional Shadcn Components to Install:**

- `pnpm dlx shadcn@latest add avatar` - For project icons
- `pnpm dlx shadcn@latest add tooltip` - For collapsed sidebar tooltips
- `pnpm dlx shadcn@latest add dropdown-menu` - For project actions menu

### Backend Components

**Controllers:**

- Modify `app/Http/Controllers/ProjectController.php` - Add index method to return projects for sidebar

**Routes:**

- Add `GET /dashboard` route to `routes/web.php` that renders the dashboard page with sidebar

**Data Structure:**

- Use existing `projects` table for the mini sidebar
- No new migrations needed - using existing schema

### Inertia Integration

- Dashboard page will receive projects list as Inertia props
- Use Inertia `<Link>` for navigation between projects
- Shared layout will be applied via `createInertiaApp` setup

### Styling

- Use Tailwind CSS 4 classes following project conventions
- Implement dark mode support using `dark:` variants
- Use `gap` utilities for spacing instead of margins
- Ensure responsive design for mobile/tablet viewports

### State Management

- Sidebar collapse state managed via React `useState`
- Selected project state managed via URL params
- Kanban dummy data stored in local component state (for now)

## Acceptance Criteria

- [ ] Shadcn sidebar-09 component is successfully installed
- [ ] Mini left sidebar displays all projects from the database
- [ ] Mini left sidebar can collapse to icon-only view with tooltips
- [ ] Main sidebar displays navigation items (dummy data)
- [ ] Main sidebar can collapse/expand independently
- [ ] Kanban board displays in main content area with dummy columns (Todo, In Progress, Done)
- [ ] Kanban board has at least 3 dummy cards distributed across columns
- [ ] Layout is responsive and works on mobile/tablet/desktop
- [ ] Dark mode is fully supported across all components
- [ ] Navigation between projects works using Inertia routing
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location:** `tests/Feature/Dashboard/DashboardTest.php`

**Key test cases:**

- Test dashboard route returns successful response with projects data
- Test dashboard page can be rendered with empty projects list
- Test dashboard page includes all projects from database
- Test projects are ordered correctly (by name or created_at)

### Browser Tests

**Test file location:** `tests/Browser/Dashboard/DashboardLayoutTest.php`

**Key test cases:**

- Test mini sidebar displays project list
- Test mini sidebar can toggle collapse/expand
- Test clicking project in sidebar navigates to correct URL
- Test main sidebar is visible and contains navigation items
- Test main sidebar can toggle collapse/expand
- Test kanban board displays with columns
- Test kanban cards are visible in columns
- Test layout renders correctly in dark mode
- Test layout is responsive on mobile viewport (e.g., 375px width)
- Test tooltips appear on collapsed mini sidebar project icons

## Code Formatting

Format all code using: **Prettier** and **oxfmt** (for code formatting)

Commands to run:

- `pnpm run format` - Format JavaScript/TypeScript files
- `vendor/bin/pint --dirty` - Format PHP files

## Additional Notes

### Project Icon Handling

- Projects without custom icons should display a default icon or first letter of project name
- Use Lucide React icons for consistency with existing components

### Sidebar State Persistence

- Consider using localStorage to persist sidebar collapse state across page reloads (optional enhancement)

### Responsive Behavior

- On mobile viewports (< 768px), sidebars should be hidden by default with hamburger menu toggle
- Main sidebar should overlay content on mobile instead of pushing it

### Dummy Data Structure

**Main Sidebar Navigation (Dummy):**

```typescript
const navigationItems = [
    { label: 'Dashboard', icon: 'LayoutDashboard', href: '/dashboard' },
    { label: 'Tasks', icon: 'CheckSquare', href: '/tasks' },
    { label: 'Worktrees', icon: 'GitBranch', href: '/worktrees' },
    { label: 'Specs', icon: 'FileText', href: '/specs' },
    { label: 'Environment', icon: 'Settings', href: '/environment' },
];
```

**Kanban Dummy Data:**

```typescript
const dummyColumns = [
    {
        id: 'todo',
        title: 'To Do',
        cards: [
            { id: '1', title: 'Add user authentication', description: 'Implement login/logout' },
            { id: '2', title: 'Create API endpoints', description: 'RESTful API for tasks' },
        ],
    },
    {
        id: 'in-progress',
        title: 'In Progress',
        cards: [{ id: '3', title: 'Setup database migrations', description: 'Create initial schema' }],
    },
    {
        id: 'done',
        title: 'Done',
        cards: [{ id: '4', title: 'Initialize project', description: 'Laravel + React setup' }],
    },
];
```

### Performance Considerations

- Projects list should be eager-loaded with minimal fields (id, name, path)
- Avoid N+1 queries when fetching projects
- Consider adding pagination if project count exceeds 50

### Future Enhancements (Out of Scope)

- Drag-and-drop for kanban cards
- Real-time updates via Laravel Reverb
- Project search/filter in mini sidebar
- Customizable kanban columns
- Keyboard shortcuts for sidebar navigation
