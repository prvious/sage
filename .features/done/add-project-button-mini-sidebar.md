---
name: add-project-button-mini-sidebar
description: Add a create project button with plus icon at top of mini sidebar for quick project creation
depends_on: null
---

## Feature Description

Add a "Create Project" button at the top of the mini sidebar (icon-only sidebar) that allows users to quickly navigate to the project creation page. The button will display as a plus (+) icon and will be positioned above the list of project avatars, making it easily accessible for adding new projects without having to navigate through the main UI.

This improves the UX by providing a consistent, visible entry point for project creation that's always accessible regardless of which page the user is on.

## Implementation Plan

### Frontend Components

**File to modify**: `resources/js/components/layout/project-sidebar.tsx`

**Changes needed**:

1. **Add Plus icon import**:
    - Import `Plus` from 'lucide-react'

2. **Add create project button at top of sidebar**:
    - Add a new `SidebarMenuItem` before the projects list
    - Use `SidebarMenuButton` with Plus icon
    - Wrap with Link component to navigate to `/projects/create`
    - Style consistently with project avatar buttons (rounded, icon-sized)
    - Add tooltip explaining "Create New Project"

3. **Update ProjectSidebar component structure**:

```tsx
import { Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';

export function ProjectSidebar({ projects }: ProjectSidebarProps) {
    return (
        <SidebarGroup>
            <SidebarGroupContent className='px-0'>
                <SidebarMenu>
                    {/* Add Project Button */}
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            tooltip={{
                                children: 'Create New Project',
                                hidden: false,
                            }}
                            className='px-0 rounded-full'
                            render={
                                <Link href='/projects/create'>
                                    <div className='flex aspect-square size-10 items-center justify-center rounded-full border-2 border-dashed border-muted-foreground/50 hover:border-muted-foreground hover:bg-muted/50 transition-colors'>
                                        <Plus className='h-5 w-5 text-muted-foreground' />
                                    </div>
                                </Link>
                            }
                        />
                    </SidebarMenuItem>

                    {/* Existing project avatars */}
                    {projects.map((item) => (
                        <SidebarMenuItem key={item.id}>{/* existing project button code */}</SidebarMenuItem>
                    ))}
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    );
}
```

### Styling Considerations

- **Icon size**: Match the size of project avatars (size-10 or similar)
- **Border style**: Use dashed border to indicate "add new" action
- **Hover effect**: Subtle background and border color change on hover
- **Spacing**: Add appropriate margin/padding to separate from project list
- **Tooltip**: Clear, descriptive tooltip on hover
- **Accessibility**: Proper aria-label for screen readers

### Visual Design Options

**Option 1 (Recommended)**: Dashed border circle with plus icon

- Clearly indicates "add new" action
- Consistent with common UI patterns
- Differentiates from existing project avatars

**Option 2**: Solid background with plus icon

- More prominent
- Could be confused with a project avatar

**Option 3**: Plus icon only (no border)

- Minimal
- May be less discoverable

## Acceptance Criteria

- [ ] Plus icon button appears at the top of the mini sidebar
- [ ] Button is positioned above all project avatars
- [ ] Button size matches project avatar size
- [ ] Button has dashed border to indicate "add new" action
- [ ] Clicking button navigates to `/projects/create` page
- [ ] Tooltip displays "Create New Project" on hover
- [ ] Button has appropriate hover effect (color change, background)
- [ ] Button is visible regardless of number of projects (0 or many)
- [ ] Button styling is consistent with sidebar design system
- [ ] Keyboard navigation works (Tab to focus, Enter to activate)
- [ ] No layout shifts when button is added
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Browser Tests

**Test file location**: `tests/Browser/Layout/MiniSidebarCreateProjectButtonTest.php`

**Key test cases**:

- Test create project button appears in mini sidebar
- Test button is positioned at top of project list
- Test clicking button navigates to project creation page
- Test button has correct tooltip text
- Test button hover state changes appearance
- Test button is visible with zero projects
- Test button is visible with multiple projects
- Test keyboard navigation (Tab, Enter) works correctly
- Test button maintains proper sizing on different viewports
- Test button doesn't interfere with project avatar list

### Feature Tests

**Test file location**: `tests/Feature/Layout/ProjectSidebarTest.php`

**Key test cases**:

- Test ProjectSidebar component renders with create button
- Test create button link points to correct route
- Test component renders correctly with empty projects array
- Test component renders correctly with multiple projects

## Code Formatting

Format all code using:

- **Frontend (TypeScript/React)**: Prettier/oxfmt
- Command: `pnpm run format`

## Additional Notes

### UX Benefits

1. **Always accessible**: Users can create projects from any page
2. **Visual consistency**: Placement in mini sidebar keeps it prominent
3. **Familiar pattern**: Plus icon is universal for "add new"
4. **No navigation required**: Direct link saves clicks
5. **Discoverable**: Positioned at top makes it easy to find

### Design Considerations

- **Position**: Top of sidebar (before projects) makes it most visible
- **Dashed border**: Industry standard pattern for "add new" placeholders
- **Icon choice**: Plus (+) is universally recognized for creation actions
- **Size**: Matching project avatar size maintains visual consistency
- **Spacing**: Adequate spacing prevents accidental clicks

### Current Project Creation Flow

Currently, users can create projects via:

1. Main projects index page → "Create New Project" button
2. Empty state → "Add Your First Project" button

This feature adds a third, always-visible option in the sidebar for improved accessibility.

### Accessibility

- Add proper ARIA labels for screen readers
- Ensure keyboard navigation works (Tab to focus, Enter/Space to activate)
- Maintain sufficient color contrast for icon
- Provide clear tooltip for discoverability

### Alternative Approaches Considered

1. **Add button in main sidebar header**:
    - Pro: More space for text label
    - Con: Takes up valuable header space, less consistent with project switcher

2. **Add button at bottom of project list**:
    - Pro: Follows list pattern
    - Con: Less discoverable, requires scrolling with many projects

3. **Add floating action button (FAB)**:
    - Pro: Always visible, modern pattern
    - Con: Clutters UI, inconsistent with current design

### Future Enhancements

- Add keyboard shortcut (e.g., Cmd+N) to create new project
- Add project creation modal that opens without navigation
- Add project templates/cloning option in creation flow
- Add recent project creation count badge

### Implementation Notes

- Use existing Inertia Link component for navigation
- Reuse existing SidebarMenuButton component for consistency
- Follow existing tooltip patterns from project avatars
- Ensure proper TypeScript types for all props
- Test with both light and dark themes
- Verify hover states work correctly
