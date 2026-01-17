---
name: remove-sidebar-search-input
description: Remove the search input from the main sidebar header to simplify the UI
depends_on: null
---

## Feature Description

Currently, the main sidebar header contains a search input field (`SidebarInput` with placeholder "Type to search...") below the project name. This search functionality is not currently implemented and takes up valuable vertical space in the sidebar header. Removing it will:

- Simplify the sidebar header UI
- Free up vertical space for navigation items
- Eliminate a non-functional UI element
- Create a cleaner, more focused header design

The search functionality can be added back in the future when it's fully implemented with actual search logic and backend support.

## Implementation Plan

### Frontend Components

**File to modify**: `resources/js/components/layout/app-sidebar.tsx`

**Current structure** (lines 120-125):

```tsx
<SidebarHeader className='gap-3.5 border-b p-4'>
    <div className='flex w-full items-center justify-between'>
        <div className='text-foreground text-base font-medium'>{selectedProject?.name || 'Select a project'}</div>
    </div>
    <SidebarInput placeholder='Type to search...' />
</SidebarHeader>
```

**Changes needed**:

1. **Remove SidebarInput component**:
    - Delete line 124: `<SidebarInput placeholder='Type to search...' />`
    - Keep the project name display

2. **Optional: Remove SidebarInput import** (if not used elsewhere):
    - Remove `SidebarInput` from the imports on line 9 if it's no longer used in the file

**After**:

```tsx
<SidebarHeader className='gap-3.5 border-b p-4'>
    <div className='flex w-full items-center justify-between'>
        <div className='text-foreground text-base font-medium'>{selectedProject?.name || 'Select a project'}</div>
    </div>
</SidebarHeader>
```

### Optional Styling Adjustments

You may want to adjust the header styling after removing the search input:

**Option 1**: Reduce header padding/gap since there's less content

```tsx
<SidebarHeader className='border-b p-4'>
```

**Option 2**: Center the project name vertically

```tsx
<SidebarHeader className='flex items-center border-b p-4'>
    <div className='text-foreground text-base font-medium'>{selectedProject?.name || 'Select a project'}</div>
</SidebarHeader>
```

**Option 3**: Keep current styling (minimal change)

- Just remove the SidebarInput line, keep everything else as-is

**Recommendation**: Option 3 (minimal change) to keep the header structure consistent, unless you prefer a more compact header.

## Acceptance Criteria

- [ ] Search input is removed from main sidebar header
- [ ] Project name display remains visible and functional
- [ ] Header maintains proper styling and spacing
- [ ] No layout shifts or visual glitches
- [ ] Border and padding remain consistent
- [ ] Header looks good with and without selected project
- [ ] Sidebar header scales properly on different viewport sizes
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Browser Tests

**Test file location**: `tests/Browser/Layout/SidebarHeaderTest.php`

**Key test cases**:

- Test sidebar header displays project name
- Test search input is not present in header
- Test header has proper styling (border, padding)
- Test header displays "Select a project" when no project selected
- Test header displays project name when project is selected
- Test header is responsive on different viewport sizes
- Test no console errors or warnings
- Test sidebar functionality remains intact

### Manual Testing Checklist

- Visual inspection: search input is gone
- Header looks clean and well-spaced
- Project name is clearly visible
- No empty space where search used to be (or space is intentional)
- Test with project selected
- Test with no project selected
- Test on desktop and mobile viewports
- Check in light and dark mode

## Code Formatting

Format all code using:

- **Frontend (TypeScript/React)**: Prettier/oxfmt
- Command: `pnpm run format`

## Additional Notes

### Design Rationale

**Why remove the search input?**

1. **Non-functional**: The search doesn't currently do anything
2. **Space optimization**: Header can be more compact
3. **Focus**: Removes distraction from main navigation
4. **Confusion**: Users may try to use it and get frustrated when it doesn't work

### Future Search Implementation

When search functionality is needed in the future, consider these alternatives:

**Option 1**: Command palette (Cmd+K)

- More powerful than inline search
- Doesn't take up permanent UI space
- Industry standard pattern (GitHub, VSCode, etc.)

**Option 2**: Search button that opens modal

- Keeps header clean
- Provides more space for search UI
- Can show search results in dedicated interface

**Option 3**: Search in specific contexts

- Add search to project list page
- Add search to agents page
- Add search to worktrees page
- Context-specific searches are often more useful

### Current Sidebar Structure

The main sidebar header currently has:

1. Project name display
2. Search input (to be removed)

After this change:

1. Project name display only

This makes the header simpler and more focused on showing the current context (which project you're in).

### Impact on User Experience

**Positive impacts**:

- Cleaner, less cluttered interface
- No confusion from non-functional UI element
- More vertical space for navigation items
- Faster visual scanning of navigation

**Potential concerns**:

- Users might miss having search available
- **Mitigation**: Add search back when it's fully implemented with real functionality

### Related Components

**SidebarHeader**: Container for sidebar header content

- Currently contains project name and search
- Will only contain project name after this change

**SidebarInput**: Search input component (from @/components/ui/sidebar)

- Currently used once in app-sidebar.tsx
- Can be removed from imports if not used elsewhere

### Alternative Approaches Considered

1. **Keep search but disable it**: Bad UX to show disabled input
2. **Replace with "Coming soon" text**: Takes up space for no benefit
3. **Hide with CSS**: Better to remove from DOM entirely
4. **Replace with project dropdown**: Out of scope for this feature

### Implementation Notes

- This is a simple removal, no complex logic changes
- No backend changes required
- No TypeScript type changes required
- No database changes required
- No test updates required (unless tests specifically check for search input)
- Verify SidebarInput isn't used elsewhere before removing import
- Consider adding a TODO comment for future search implementation

### Before and After

**Before**:

```
┌─────────────────────────────┐
│ My Project Name             │
│ [Type to search...]         │
└─────────────────────────────┘
```

**After**:

```
┌─────────────────────────────┐
│ My Project Name             │
└─────────────────────────────┘
```

The header becomes more compact and focused on showing the current project context.

### Accessibility Considerations

- No accessibility impact (removing non-functional element)
- Focus order remains logical (mini sidebar → main nav items)
- Screen readers won't announce a non-functional search field
- Keyboard navigation is unaffected

### Performance Considerations

- Minimal performance impact (one less component to render)
- Slightly faster sidebar rendering
- No event listeners to clean up
- Reduced component tree depth
