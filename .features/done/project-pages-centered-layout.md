---
name: project-pages-centered-layout
description: Apply centered card layout to project create, edit, and show pages
depends_on: projects-list-redesign
---

## Feature Description

Refactor the project create, edit, and show pages to use the same centered card layout design established in the `projects-list-redesign` feature. This creates visual consistency across all project-related pages by using:

- Muted background (`bg-muted`) for page background
- Centered card container with content
- Sage logo/branding above the card (matching projects list)
- Consistent spacing and typography using Geist Mono font
- Same visual hierarchy and design language

Each page will adapt the card content to its specific purpose:

- **Create**: Form fields for new project within the card
- **Edit**: Form fields for editing existing project within the card
- **Show**: Project details, worktrees, tasks, and specs within the card

This provides a cohesive user experience where all project pages feel like part of the same application with a unified design system.

## Implementation Plan

### Frontend Components

**Pages to Modify:**

- `resources/js/pages/projects/create.tsx` - Apply centered card layout with form inside
- `resources/js/pages/projects/edit.tsx` - Apply centered card layout with form inside
- `resources/js/pages/projects/show.tsx` - Apply centered card layout with project details inside

**Components:**

- Use existing `resources/js/components/branding/sage-logo.tsx` - Sage logo component (from projects-list-redesign)
- May create `resources/js/components/layout/centered-card-layout.tsx` - Optional wrapper component for reuse

**Shadcn Components:**

- Use existing `Card`, `CardHeader`, `CardTitle`, `CardDescription`, `CardContent`
- Use existing `ScrollArea` for long content if needed
- Use existing `Button`, `Input`, `Label` components
- May need `Separator` for dividing sections: `pnpm dlx shadcn@latest add separator`

**Styling:**

- Consistent `bg-muted` background across all pages
- Same max-width (`max-w-4xl`) for cards
- Same spacing and padding
- Use Geist Mono font (already configured)
- Dark mode support throughout

### Layout Structure

**Create/Edit Pages:**

```tsx
<div className='min-h-screen bg-muted flex flex-col items-center justify-center p-4'>
    {/* Sage Logo */}
    <div className='mb-8'>
        <SageLogo />
    </div>

    {/* Centered Form Card */}
    <Card className='w-full max-w-4xl'>
        <CardHeader>
            <CardTitle>Create Project / Edit Project</CardTitle>
            <CardDescription>Configure your Laravel project settings</CardDescription>
        </CardHeader>
        <CardContent>
            <form>{/* Form fields */}</form>
        </CardContent>
    </Card>
</div>
```

**Show Page:**

```tsx
<div className='min-h-screen bg-muted flex flex-col items-center justify-center p-4'>
    {/* Sage Logo */}
    <div className='mb-8'>
        <SageLogo />
    </div>

    {/* Centered Details Card */}
    <Card className='w-full max-w-4xl'>
        <CardHeader>
            <div className='flex items-center justify-between'>
                <div>
                    <CardTitle>{project.name}</CardTitle>
                    <CardDescription>{project.path}</CardDescription>
                </div>
                <div className='flex gap-2'>
                    <Button variant='outline'>Edit</Button>
                    <Button variant='destructive'>Delete</Button>
                </div>
            </div>
        </CardHeader>
        <CardContent>
            <ScrollArea className='h-[500px]'>{/* Project details, worktrees, tasks, specs */}</ScrollArea>
        </CardContent>
    </Card>
</div>
```

### Form Layout Improvements

**Create/Edit Forms:**

- Use Shadcn `Input` and `Label` components instead of raw HTML inputs
- Use Shadcn `Button` component instead of raw HTML buttons
- Group related fields with `Separator` components
- Add helpful descriptions under form fields
- Improve error message styling with consistent colors
- Add loading states with spinners/disabled states

**Example Form Field:**

```tsx
<div className='space-y-2'>
    <Label htmlFor='name'>Project Name</Label>
    <Input id='name' value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder='My Laravel App' />
    {errors.name && <p className='text-sm text-destructive'>{errors.name}</p>}
</div>
```

### Show Page Enhancements

**Content Organization:**

- **Project Details Section**: Display basic project info (path, server_driver, base_url)
- **Worktrees Section**: List with badges and status indicators
- **Tasks Section**: List with status chips
- **Specs Section**: List with preview snippets

**Use Tabs or Separators:**
Option 1: Use `Separator` between sections
Option 2: Use Shadcn `Tabs` component for switching between sections

### Navigation Updates

**Breadcrumb/Back Navigation:**

- Replace simple "← Back" links with Shadcn `Breadcrumb` component
- Keep links functional with Inertia `<Link>`
- Example: `Home > Projects > Project Name`

**Consistent Buttons:**

- Use Shadcn button variants:
    - Primary actions: `variant="default"`
    - Secondary actions: `variant="outline"`
    - Destructive actions: `variant="destructive"`
    - Cancel actions: `variant="ghost"`

## Acceptance Criteria

- [ ] Projects create page uses centered card layout with bg-muted background
- [ ] Projects edit page uses centered card layout with bg-muted background
- [ ] Projects show page uses centered card layout with bg-muted background
- [ ] Sage logo appears above the card on all three pages (create, edit, show)
- [ ] All pages use max-w-4xl for card width
- [ ] Forms use Shadcn Input, Label, and Button components
- [ ] Show page uses ScrollArea for long content
- [ ] Error messages are styled consistently across all forms
- [ ] Dark mode works correctly on all pages
- [ ] Layout is responsive on mobile, tablet, and desktop
- [ ] Navigation buttons/links use Shadcn Button component
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location:** `tests/Feature/Projects/ProjectPagesTest.php`

**Key test cases:**

- Test create page renders successfully
- Test edit page renders successfully with project data
- Test show page renders successfully with project data
- Test create form submits correctly
- Test edit form submits correctly
- Test delete confirmation works on show page
- Test validation errors display correctly on forms

### Browser Tests

**Test file location:** `tests/Browser/Projects/ProjectPagesLayoutTest.php`

**Key test cases:**

- Test create page displays Sage logo
- Test create page has centered card layout
- Test create page has bg-muted background
- Test create form fields are accessible and functional
- Test edit page displays Sage logo
- Test edit page has centered card layout
- Test edit form pre-fills with project data
- Test show page displays Sage logo
- Test show page has centered card layout
- Test show page displays project details correctly
- Test show page displays worktrees, tasks, and specs lists
- Test all pages render correctly in dark mode
- Test all pages are responsive on mobile (375px width)
- Test all pages are responsive on tablet (768px width)
- Test all pages are responsive on desktop (1280px width)

## Code Formatting

Format all code using: **Prettier** and **oxfmt** (for JavaScript/TypeScript), **Pint** (for PHP)

Commands to run:

- `pnpm run format` - Format JavaScript/TypeScript files
- `vendor/bin/pint --dirty` - Format PHP files

## Additional Notes

### Design Consistency Guidelines

**Visual Hierarchy:**

1. Sage logo (branding)
2. Card container (main content)
3. Card header (title + description)
4. Card content (form or details)
5. Action buttons (footer or header)

**Spacing:**

- Logo to card: `mb-8` (2rem)
- Card padding: Built into Shadcn Card component
- Form field spacing: `space-y-6` for major sections, `space-y-2` for field groups
- Button spacing: `gap-2` or `gap-3`

**Colors:**

- Background: `bg-muted`
- Card: `bg-card` (white in light mode, dark gray in dark mode)
- Primary text: `text-foreground`
- Secondary text: `text-muted-foreground`
- Errors: `text-destructive`
- Success: `text-green-600 dark:text-green-400`

### Reusable Layout Component (Optional)

Consider creating a wrapper component:

```tsx
// resources/js/components/layout/centered-card-layout.tsx
interface CenteredCardLayoutProps {
    children: React.ReactNode;
    className?: string;
}

export function CenteredCardLayout({ children, className }: CenteredCardLayoutProps) {
    return (
        <div className='min-h-screen bg-muted flex flex-col items-center justify-center p-4'>
            <div className='mb-8'>
                <SageLogo />
            </div>
            <Card className={cn('w-full max-w-4xl', className)}>{children}</Card>
        </div>
    );
}
```

**Usage:**

```tsx
<CenteredCardLayout>
    <CardHeader>
        <CardTitle>Create Project</CardTitle>
    </CardHeader>
    <CardContent>{/* form */}</CardContent>
</CenteredCardLayout>
```

### Migration from Old Design

**Before (Create Page):**

- Full-width layout with max-w-3xl container
- Gradient background
- Raw HTML form inputs
- Custom styled buttons

**After (Create Page):**

- Centered card layout with max-w-4xl
- Muted background
- Shadcn Input/Label components
- Shadcn Button components
- Sage logo branding

### Show Page Specific Enhancements

**Sections to Display:**

1. **Project Details:**
    - Name (as CardTitle)
    - Path (as CardDescription or detail row)
    - Server Driver (with badge)
    - Base URL (with link icon)
    - Created At (timestamp)

2. **Worktrees:**
    - List with branch names
    - Status badges (active, deleted, etc.)
    - Link to worktree details

3. **Tasks:**
    - List with titles
    - Status badges (idea, in_progress, review, done)
    - Link to task on kanban board

4. **Specs:**
    - List with titles
    - Preview snippets (first 100 chars)
    - Link to spec details

**Empty States:**
Each section should have a nice empty state:

```tsx
{
    worktrees.length === 0 ? (
        <div className='text-center py-8 text-muted-foreground'>
            <p>No worktrees yet. Create one to get started.</p>
        </div>
    ) : (
        <ul>...</ul>
    );
}
```

### Responsive Behavior

**Mobile (< 640px):**

- Card takes full width with minimal padding (p-2 instead of p-4)
- Sage logo smaller
- Form fields stack vertically
- Buttons stack vertically or wrap
- ScrollArea reduced height (h-[300px])

**Tablet (640px - 1024px):**

- Card max-w-2xl or max-w-3xl
- Comfortable spacing
- Form fields may use 2-column grid for some fields

**Desktop (> 1024px):**

- Card max-w-4xl
- Optimal spacing
- Form fields use grid layout where appropriate
- ScrollArea full height (h-[500px] or h-[600px])

### Accessibility Improvements

- All form labels use proper `htmlFor` attributes
- Error messages use `aria-invalid` and `aria-describedby`
- Buttons have proper `aria-label` for icon-only buttons
- Focus states are visible and consistent
- Color contrast meets WCAG AA standards
- Keyboard navigation works smoothly

### Dependencies

This feature depends on `projects-list-redesign` because:

- Uses the Sage logo component created there
- Follows the same design language and layout structure
- Shares the centered card on muted background pattern

**Implementation order:**

1. Complete `projects-list-redesign` first
2. Then implement `project-pages-centered-layout`
