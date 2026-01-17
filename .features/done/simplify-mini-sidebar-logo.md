---
name: simplify-mini-sidebar-logo
description: Display only 'S' in mini-sidebar logo instead of full 'Sage' text to prevent clipping
depends_on: null
---

## Feature Description

Currently, the mini-sidebar displays the full `SageLogo` component which renders "Sage" with `text-4xl` font size inside a small `size-8` container. This causes the logo to be clipped and look distorted because the text is too large for the constrained space.

This feature simplifies the mini-sidebar logo to display only the letter **'S'** in a clean, centered format that fits perfectly within the icon-only sidebar width.

**Current Issue**:

- Mini sidebar uses `<SageLogo />` which renders full "Sage" text (text-4xl)
- Container is only `size-8` (32px × 32px)
- Logo gets clipped and looks awkward
- Visual hierarchy is confusing with oversized text

**Solution**:

- Display just the letter 'S' in the mini-sidebar
- Use appropriate font size that fits the container
- Maintain link functionality to home page
- Keep the full logo for use in other locations (main sidebar, landing pages, etc.)

## Implementation Plan

### Frontend Components

#### 1. Update Mini Sidebar Logo Display

**File to modify**: `resources/js/components/layout/app-sidebar.tsx`

**Current code** (lines 98-100):

```tsx
<div className='bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-lg'>
    <SageLogo />
</div>
```

**New code**:

```tsx
<Link
    href='/'
    className='bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-lg hover:opacity-80 transition-opacity'
    aria-label='Sage - Go to home page'
>
    <span className='text-xl font-bold'>S</span>
</Link>
```

**Changes**:

1. Replace `<SageLogo />` component with simple text 'S'
2. Move `Link` wrapper from SageLogo to the container div
3. Use `text-xl` font size (fits comfortably in size-8 container)
4. Keep hover effect for better UX
5. Maintain accessibility with aria-label
6. Remove the entire outer div wrapper since Link can be the container itself

#### 2. Keep SageLogo Component Unchanged

**File**: `resources/js/components/branding/sage-logo.tsx`

**No changes needed** - This component can remain as-is for use in:

- Main sidebar header (future enhancement)
- Landing pages
- Authentication pages
- Marketing materials
- Full-width headers

The full "Sage" logo with animation is still valuable for contexts where space isn't constrained.

### Styling Considerations

**Font Size**:

- `text-xl` (20px) fits perfectly in size-8 (32px) container
- Provides adequate breathing room (6px padding on each side)
- Maintains legibility

**Colors**:

- Use `text-sidebar-primary-foreground` for contrast
- Background uses `bg-sidebar-primary`
- Maintains theme consistency

**Hover State**:

- Add `hover:opacity-80` for feedback
- Add `transition-opacity` for smooth effect
- Keeps interaction clear

**Accessibility**:

- Include `aria-label` describing the link purpose
- Maintains keyboard focus ring from parent SidebarMenuButton
- Screen readers announce "Sage - Go to home page"

## Acceptance Criteria

- [ ] Mini sidebar displays only 'S' instead of full 'Sage' text
- [ ] Letter 'S' is not clipped or cut off
- [ ] Letter 'S' is centered in the container
- [ ] Logo is clickable and navigates to home page (/)
- [ ] Hover effect provides visual feedback
- [ ] Font size is appropriate (not too large or too small)
- [ ] Logo works in both light and dark themes
- [ ] No layout shifts when switching themes
- [ ] Accessibility labels are present
- [ ] SageLogo component remains unchanged for other uses
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Browser Tests

**Test file location**: `tests/Browser/Layout/MiniSidebarLogoTest.php`

**Key test cases**:

- Test mini sidebar displays 'S' character
- Test mini sidebar does not display full 'Sage' text
- Test logo is clickable
- Test clicking logo navigates to home page
- Test logo is visible and not clipped
- Test logo has proper sizing
- Test logo hover state changes appearance
- Test logo works in light theme
- Test logo works in dark theme
- Test logo is accessible (aria-label present)

### Visual Testing

**Manual verification**:

1. Check mini sidebar on dashboard page
2. Verify 'S' is fully visible (not clipped)
3. Verify 'S' is centered in container
4. Hover over 'S' and verify opacity change
5. Click 'S' and verify navigation to home
6. Switch between light/dark themes
7. Check on different screen sizes (mobile, tablet, desktop)

## Code Formatting

Format all code using:

- **Frontend (TypeScript/React)**: Prettier/oxfmt
- Command: `pnpm run format`

## Additional Notes

### Why This Change?

**Visual Issues with Current Implementation**:

1. **Clipping**: Text-4xl (36px) doesn't fit in size-8 (32px) container
2. **Awkward appearance**: Oversized text looks unprofessional
3. **Confusing hierarchy**: Logo competes with project avatars for attention
4. **Space inefficiency**: Takes up more vertical space than needed

**Benefits of Single Letter**:

1. **Clean appearance**: 'S' fits perfectly in icon-sized container
2. **Professional look**: Matches design patterns of icon-only sidebars
3. **Better hierarchy**: Logo is present but doesn't dominate
4. **Improved UX**: Users can still access home via logo click
5. **Icon consistency**: Single letter matches the icon-only sidebar pattern

### Design Rationale

**Why 'S' and not a graphic icon?**

- 'S' is immediately recognizable as Sage branding
- Maintains text-based logo identity
- Simpler to implement than custom SVG icon
- Scales perfectly at any size
- Works in all themes without asset changes

**Font size selection**:

- `text-xl` (20px) leaves 6px margin on all sides in size-8 (32px) container
- Comfortable sizing that's not cramped
- Maintains readability even for users with vision impairments
- Consistent with typical icon sizing patterns

### Alternative Approaches Considered

1. **Resize full "Sage" text to fit**:
    - Pro: Keeps full branding
    - Con: Text would be too small to read (needs ~6px font)
    - Con: Still awkward with pulsing dot animation

2. **Use custom SVG icon**:
    - Pro: Could create unique branded icon
    - Con: More complex to implement
    - Con: Requires design work and asset management
    - Con: May not match text-based branding elsewhere

3. **Remove logo entirely from mini sidebar**:
    - Pro: Maximally simple
    - Con: Loses quick access to home
    - Con: Reduces brand presence

4. **Show 'S' with pulsing dot**:
    - Pro: Maintains animation from full logo
    - Con: Adds visual clutter in small space
    - Con: May distract from project avatars

**Decision**: Use simple 'S' text without additional decoration for clean, professional appearance.

### Responsive Behavior

The change only affects the mini-sidebar:

- **Desktop**: Mini sidebar visible, shows 'S'
- **Tablet**: Mini sidebar may collapse, shows 'S' when visible
- **Mobile**: Mini sidebar typically hidden, no impact

### Theme Compatibility

The implementation uses theme-aware classes:

- `bg-sidebar-primary` - Adapts to light/dark theme
- `text-sidebar-primary-foreground` - Ensures proper contrast
- No hard-coded colors or custom CSS needed

### Future Enhancements

Consider these improvements in future iterations:

- Add subtle animation on hover (scale, rotate, etc.)
- Add tooltip showing "Sage" on hover
- Consider adding pulsing dot indicator (like full logo)
- Animate between 'S' and full "Sage" on sidebar expand/collapse
- Add keyboard shortcut (Cmd+Home) to navigate via logo

### Impact on Other Components

**Components NOT affected by this change**:

- `SageLogo` component (remains unchanged)
- Main sidebar (not currently using logo)
- Landing page
- Authentication pages
- Marketing pages

Only the mini-sidebar in `app-sidebar.tsx` is modified.

### Accessibility Considerations

- Maintain `aria-label` for screen readers
- Ensure sufficient color contrast (WCAG AA)
- Keep keyboard focus ring visible
- Don't rely solely on color for interaction feedback
- Test with screen readers (VoiceOver, NVDA, JAWS)

### Browser Compatibility

This change uses standard CSS classes supported by all modern browsers:

- Flexbox (widely supported)
- Tailwind utility classes (no custom CSS)
- Hover states (standard CSS)
- Transitions (supported in all modern browsers)

No polyfills or fallbacks needed.

### Performance Considerations

**Improvements**:

- Removes nested component rendering (SageLogo)
- Simpler DOM structure (fewer elements)
- No additional assets to load
- Faster initial render

**No negative impact**:

- Text rendering is highly optimized
- Single character has minimal rendering cost
- Transitions are GPU-accelerated

### Migration Notes

This is a non-breaking visual change:

- No API changes
- No route changes
- No database changes
- No configuration changes

Users will see the updated logo immediately after deployment with no action required.

### Testing with Real Data

After implementation:

1. Test with 0 projects (empty sidebar)
2. Test with 1 project
3. Test with 10+ projects (scrolling sidebar)
4. Test on different displays (retina, non-retina)
5. Test with different zoom levels (50%, 100%, 200%)

### Rollback Plan

If issues arise, easy rollback:

1. Revert app-sidebar.tsx change
2. Restore `<SageLogo />` in mini sidebar
3. No other changes needed (SageLogo unchanged)

### Documentation Updates

After implementation, consider updating:

- Design system documentation (if exists)
- Component library (if exists)
- Brand guidelines (if exists)
- Developer onboarding docs
