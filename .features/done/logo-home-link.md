---
name: logo-home-link
description: Make the Sage logo in centered-card-layout a clickable link to the home page
depends_on: null
---

## Feature Description

Currently, the Sage logo displayed in the `CenteredCardLayout` component is just static text with an animated dot. This feature will make the logo clickable, allowing users to return to the home page from any centered-card layout screen (like auth pages or isolated forms).

This is a common UX pattern where clicking the application logo returns users to the home/root page.

## Implementation Plan

### Frontend Components

**Components to modify:**

- `resources/js/components/branding/sage-logo.tsx` - Wrap logo in Inertia Link component
- `resources/js/components/layouts/centered-card-layout.tsx` - No changes needed (already uses SageLogo)

**Routing:**

- Use Inertia's `<Link>` component pointing to the `home` route
- Use Wayfinder to generate the route: `import { index } from '@/routes/home'` (or similar based on named route)

**Styling:**

- Maintain existing appearance
- Add hover state for better UX (e.g., slight opacity change or subtle underline)
- Ensure the link is accessible (proper aria-label already exists)

### Backend Components

No backend changes required - the `home` route already exists at `routes/web.php:14` (`Route::get('/', [HomeController::class, 'index'])->name('home')`).

## Acceptance Criteria

- [ ] Clicking the Sage logo navigates to the home page (`/`)
- [ ] The logo maintains its current visual design (text + animated dot)
- [ ] A subtle hover effect indicates the logo is clickable
- [ ] The link is keyboard accessible (can be focused and activated via Enter)
- [ ] Navigation works using Inertia's client-side routing (no full page reload)
- [ ] All tests pass
- [ ] Code is formatted according to project standards (Prettier)

## Testing Strategy

### Browser Tests

**Test file location:** `tests/Browser/Branding/SageLogoLinkTest.php`

**Key test cases:**

- Test that the Sage logo is rendered as a link element
- Test clicking the logo navigates to the home page
- Test hover state applies visual feedback
- Test keyboard navigation (focus state and Enter key activation)
- Test that navigation preserves Inertia SPA behavior (no full reload)

### Unit/Component Tests (Optional)

If component-level testing is set up:

- Verify Link component wraps the logo content
- Verify correct route is passed to Link component

## Code Formatting

Format all code using: **Prettier**

Command to run: `pnpm run format` (or `pnpm prettier --write`)

## Additional Notes

### Implementation Approach

1. Import Inertia's `Link` component in `sage-logo.tsx`
2. Check if Wayfinder generates a route helper for `home` route
    - If yes: Use `import { index } from '@/routes/home'` or similar
    - If no: Use hardcoded `href="/"` or `route('home')` equivalent
3. Wrap the logo content in the Link component
4. Add hover styles using Tailwind classes (e.g., `hover:opacity-80 transition-opacity`)
5. Ensure the existing `aria-label` remains for accessibility

### Edge Cases

- If user is already on home page, clicking logo should still work (Inertia handles this gracefully)
- Logo should not have underline decoration by default (use `no-underline` if needed)

### Accessibility Considerations

- The existing `aria-label='Sage'` should be preserved or moved to the Link element
- Consider adding `aria-current="page"` when on the home page (optional enhancement)
- Ensure sufficient color contrast for focus indicators
