---
name: projects-list-redesign
description: Redesign projects list with centered card layout and Sage branding
depends_on: null
---

## Feature Description

Redesign the projects list page to feature a centered, scrollable card layout with a clean, focused user experience. The page will have a muted background (`bg-muted`) to make the project list card stand out visually. A "Sage" logo/branding element will be displayed above the card for brand identity.

The design improvements include:

- Centered card container with scrollable content area (h-100 default height)
- Muted background to create visual hierarchy
- Sage branding/logo above the project card
- Use of Geist Mono font (already installed) as the primary monospace font
- Enhanced visual contrast between background and content

This creates a more polished, professional landing experience that guides users to their projects while maintaining the application's clean aesthetic.

## Implementation Plan

### Frontend Components

**Pages:**

- Modify `resources/js/pages/projects/index.tsx` - Redesign layout with centered card and muted background

**Components:**

- Create `resources/js/components/branding/sage-logo.tsx` - Sage logo/branding component
- Potentially create `resources/js/components/layout/centered-card-layout.tsx` - Reusable centered card layout wrapper (optional)

**Shadcn Components:**

- Use existing `Card`, `ScrollArea`, and other UI components
- May need to install `scroll-area` if not already present: `pnpm dlx shadcn@latest add scroll-area`

**Styling:**

- Update `resources/css/app.css` - Ensure Geist Mono is set as primary font-family for body/html
- Current CSS already imports and configures Geist Mono, but may need adjustment to make it default

### Typography Configuration

**Current State:**

- Geist Mono is already installed via `@fontsource-variable/geist-mono`
- Already imported in `resources/css/app.css`
- Configured in `@theme inline` as `--font-mono`
- Currently applied via `@apply font-mono` in base layer

**Changes Needed:**

- Verify Geist Mono is the default application font
- If needed, update the `@layer base` section to use `font-mono` consistently

### Layout Structure

**New Projects List Layout:**

```tsx
<div className='min-h-screen bg-muted flex flex-col items-center justify-center p-4'>
    {/* Sage Logo/Branding */}
    <div className='mb-8'>
        <SageLogo />
    </div>

    {/* Centered Card */}
    <Card className='w-full max-w-4xl'>
        <CardHeader>
            <CardTitle>Projects</CardTitle>
            <CardDescription>Select a project to continue</CardDescription>
        </CardHeader>
        <CardContent>
            {/* Scrollable area with h-100 */}
            <ScrollArea className='h-[400px]'>{/* Project list items */}</ScrollArea>
        </CardContent>
    </Card>
</div>
```

### Sage Logo Design

**Options for Sage Logo:**

1. **Simple Text Logo**: "Sage" in large, stylized text using Geist Mono
2. **Icon + Text**: Simple geometric icon (e.g., leaf, sage herb) with "Sage" text
3. **Monogram**: Stylized "S" lettermark

**Recommendation**: Start with a simple text-based logo using Geist Mono font with optional subtle accent (e.g., gradient or colored dot after "Sage")

Example:

```tsx
<div className='flex items-center gap-2'>
    <h1 className='text-4xl font-bold tracking-tight'>Sage</h1>
    <div className='h-2 w-2 rounded-full bg-primary' />
</div>
```

### Responsive Design

- **Mobile (< 640px)**: Full width card with reduced padding, logo smaller
- **Tablet (640px - 1024px)**: Card max-width adjusts, comfortable spacing
- **Desktop (> 1024px)**: Card centered with max-w-4xl, optimal viewing

### Scroll Area Configuration

- Default height: `h-[400px]` (equivalent to h-100 in pixels)
- Scrollbar styling: Use Shadcn ScrollArea component for consistent styling
- Smooth scrolling enabled
- Overflow behavior: Auto scroll when content exceeds container height

## Acceptance Criteria

- [ ] Projects list page displays with `bg-muted` background
- [ ] Project list is contained in a centered card with max-w-4xl
- [ ] Sage logo/branding is displayed above the card
- [ ] Scroll area has h-[400px] (h-100 equivalent) default height
- [ ] Scroll area is functional and scrolls when content overflows
- [ ] Geist Mono font is used as the primary font throughout the application
- [ ] "New Project" button is accessible and properly styled
- [ ] Empty state (no projects) is displayed correctly within the new layout
- [ ] Layout is responsive on mobile, tablet, and desktop
- [ ] Dark mode is fully supported with proper contrast
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location:** `tests/Feature/Projects/ProjectsIndexTest.php`

**Key test cases:**

- Test projects index page returns successful response
- Test projects index page includes all projects data
- Test projects index page renders when no projects exist
- Test projects index with multiple projects displays correctly

### Browser Tests

**Test file location:** `tests/Browser/Projects/ProjectsListLayoutTest.php`

**Key test cases:**

- Test Sage logo is visible above projects card
- Test projects list is displayed in a centered card
- Test background has muted appearance (bg-muted class applied)
- Test scroll area is functional when many projects exist
- Test clicking project navigates to correct project page
- Test "New Project" button is visible and clickable
- Test empty state displays when no projects exist
- Test layout is responsive on mobile viewport (375px width)
- Test layout is responsive on tablet viewport (768px width)
- Test layout is responsive on desktop viewport (1280px width)
- Test dark mode renders correctly with proper contrast
- Test Geist Mono font is applied to page elements

## Code Formatting

Format all code using: **Prettier** and **oxfmt** (for JavaScript/TypeScript), **Pint** (for PHP)

Commands to run:

- `pnpm run format` - Format JavaScript/TypeScript files
- `vendor/bin/pint --dirty` - Format PHP files

## Additional Notes

### Geist Mono Font Implementation

The font is already installed and configured. Current CSS configuration:

```css
@import '@fontsource-variable/geist-mono';

@theme inline {
    --font-mono: 'Geist Mono Variable', monospace;
}

@layer base {
    html,
    body {
        @apply font-mono; /* Geist Mono already applied */
    }
}
```

**No additional font installation needed** - just ensure consistent usage throughout the redesigned page.

### Sage Logo Branding Considerations

**Design Principles:**

- Keep it simple and clean
- Use monospace font (Geist Mono) for consistency
- Subtle accent color (primary or muted-foreground)
- Should work well in both light and dark modes
- Scalable for different viewport sizes

**Potential Logo Variations:**

```tsx
// Option 1: Simple text
<h1 className="text-4xl font-bold tracking-tight">Sage</h1>

// Option 2: Text with accent dot
<div className="flex items-center gap-2">
  <h1 className="text-4xl font-bold">Sage</h1>
  <div className="h-2 w-2 rounded-full bg-primary animate-pulse" />
</div>

// Option 3: Text with subtitle
<div className="text-center">
  <h1 className="text-4xl font-bold tracking-tight">Sage</h1>
  <p className="text-sm text-muted-foreground mt-1">AI Agent Orchestrator</p>
</div>
```

### Color Palette for Muted Background

The `bg-muted` utility uses the CSS variable `--muted`:

- Light mode: `oklch(0.967 0.001 286.375)` (very light gray)
- Dark mode: `oklch(0.274 0.006 286.033)` (dark gray)

This provides excellent contrast with the card background (`bg-card`/`bg-white`).

### Scroll Area Height Options

While the spec mentions "h-100", Tailwind uses different height units:

- `h-96` = 384px (24rem)
- `h-[400px]` = 400px (custom value)
- `h-screen` = 100vh (full viewport height)

**Recommendation**: Use `h-[400px]` for the scroll area to provide a comfortable viewing area without overwhelming the page.

### Card Shadow and Elevation

Consider adding subtle shadow to the card for depth:

```tsx
<Card className='shadow-xl border-2'>{/* content */}</Card>
```

### Accessibility Considerations

- Ensure logo has proper `aria-label` if it's an image
- Scroll area should be keyboard navigable
- Focus states should be visible on interactive elements
- Color contrast should meet WCAG AA standards
- Screen readers should announce project count

### Performance Considerations

- Geist Mono Variable font is already loaded, no additional bundle size
- Scroll area virtualizes large lists for performance (if using Shadcn ScrollArea)
- Card layout is simpler than grid, reducing DOM complexity
- Background color uses CSS variables for instant theme switching

### Future Enhancements (Out of Scope)

- Animated Sage logo (e.g., fade in, scale)
- Project search/filter within the scroll area
- Project grouping by status or date
- Drag-to-reorder projects
- Recently accessed projects indicator
- Quick actions menu on each project card (kebab menu)
