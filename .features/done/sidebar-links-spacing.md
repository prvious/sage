---
name: sidebar-links-spacing
description: Add vertical spacing between sidebar navigation links for better visual separation
depends_on: null
---

## Feature Description

Currently, the sidebar navigation links in the main sidebar are too close together, making it difficult to distinguish between individual items and reducing overall readability. This feature adds appropriate vertical spacing between navigation items using Tailwind's `gap-*` utility classes on the SidebarMenu component, which already has `flex-col` applied.

This improves the user experience by:

- Making it easier to scan navigation items
- Reducing visual clutter
- Improving clickability with more breathing room
- Creating a more polished, professional appearance

## Implementation Plan

### Frontend Components

**File to modify**: `resources/js/components/layout/app-sidebar.tsx`

**Changes needed**:

1. **Add gap utility to SidebarMenu**:
    - Locate the SidebarMenu component in the navigation section (around line 131)
    - Add `gap-1` or `gap-2` class to the SidebarMenu component
    - Since SidebarMenu already has `flex-col`, the gap will apply vertically between items

**Before**:

```tsx
<SidebarMenu>
    {navigationItems.map((item) => {
        // navigation items
    })}
</SidebarMenu>
```

**After**:

```tsx
<SidebarMenu className='gap-2'>
    {navigationItems.map((item) => {
        // navigation items
    })}
</SidebarMenu>
```

### Spacing Options

**Recommended spacing values**:

- `gap-1` (0.25rem / 4px) - Subtle spacing, keeps items compact
- `gap-2` (0.5rem / 8px) - Moderate spacing, good balance (recommended)
- `gap-3` (0.75rem / 12px) - More spacious, better for larger sidebars
- `gap-4` (1rem / 16px) - Maximum spacing, very airy feel

**Recommendation**: Start with `gap-2` for a balanced look. Adjust based on visual preference.

### Alternative Approach (If Needed)

If you want different spacing for different menu sections, you can apply gap to multiple SidebarMenu instances:

```tsx
{/* Projects navigation */}
<SidebarMenu className='gap-2'>
    {projectNavigationItems.map(...)}
</SidebarMenu>

{/* Settings or other sections */}
<SidebarMenu className='gap-2'>
    {settingsItems.map(...)}
</SidebarMenu>
```

## Acceptance Criteria

- [ ] Sidebar navigation links have visible vertical spacing between them
- [ ] Spacing uses Tailwind `gap-*` utility class on SidebarMenu
- [ ] Spacing is consistent across all navigation items
- [ ] Visual appearance is improved without making sidebar too tall
- [ ] Spacing works correctly when sidebar is collapsed and expanded
- [ ] No layout shifts or overflow issues
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Browser Tests

**Test file location**: `tests/Browser/Layout/SidebarNavigationSpacingTest.php`

**Key test cases**:

- Test sidebar navigation items have visible spacing between them
- Test spacing is consistent for all items
- Test sidebar doesn't overflow with added spacing
- Test spacing remains correct on different viewport sizes
- Test spacing works when sidebar is collapsed/expanded
- Test no visual regressions in sidebar appearance

### Manual Testing Checklist

- Visual inspection: links have adequate spacing
- Check with few navigation items (1-3)
- Check with many navigation items (8+)
- Verify sidebar scrolls correctly if items exceed viewport height
- Test on desktop and mobile viewports
- Check in light and dark mode

## Code Formatting

Format all code using:

- **Frontend (TypeScript/React)**: Prettier/oxfmt
- Command: `pnpm run format`

## Additional Notes

### Design Considerations

- **Balance**: Too little spacing makes items hard to distinguish, too much wastes vertical space
- **Consistency**: Same spacing should apply to all navigation sections for visual harmony
- **Responsive**: Spacing should scale appropriately on different screen sizes
- **Accessibility**: Adequate spacing improves clickability and reduces misclicks

### Current Sidebar Structure

The app-sidebar.tsx has two nested Sidebar components:

1. **Mini sidebar** (icon-only): Shows project avatars
2. **Main sidebar** (expandable): Shows navigation links, search, header

This feature targets the navigation links in the **main sidebar** (second Sidebar component).

### Why gap-\* is Better Than Margin

- **Consistency**: `gap` applies uniform spacing between all children automatically
- **Cleaner**: No need to add `mb-*` or `mt-*` to individual items
- **Responsive**: `gap` works seamlessly with flex direction changes
- **Maintainable**: Single class instead of managing spacing on each item

### Future Enhancements

- Add hover effect that slightly expands spacing on focused item
- Add animation when items appear/disappear
- Consider different spacing for grouped navigation sections
- Add user preference to customize spacing (compact/comfortable/spacious)

### Implementation Notes

- Use existing Tailwind utilities (no custom CSS needed)
- SidebarMenu component already has `flex flex-col` from the UI library
- No TypeScript changes required (styling only)
- Test with actual navigation content, not placeholder items
- Ensure spacing doesn't cause sidebar to require scrolling unnecessarily

### Visual Comparison

**Without spacing** (current):

```
Dashboard
Worktrees
Specs
Environment
Terminal
Context
Agent
Settings
```

**With gap-2** (proposed):

```
Dashboard

Worktrees

Specs

Environment

Terminal

Context

Agent

Settings
```

The visual separation makes it much easier to scan and select items.

### Accessibility Benefits

- Larger clickable areas reduce accidental clicks
- Visual separation helps users with cognitive disabilities
- Improved readability benefits users with visual impairments
- Better touch targets for mobile/tablet users

### Performance Considerations

- CSS `gap` property is highly performant (no JavaScript required)
- No additional DOM elements or wrappers needed
- No layout recalculations when sidebar state changes
- Minimal CSS payload increase (single utility class)
