---
name: refactor-worktrees-pages-to-new-layout
description: Refactor worktrees pages to use AppLayout and Shadcn UI components matching the new project design patterns
depends_on: null
---

## Feature Description

The worktrees pages (index, create, show) are currently using an old design pattern with:

- Custom div structures and manual styling
- Old Tailwind classes with explicit dark mode handling
- No layout wrapper component
- Raw HTML form elements instead of Shadcn components
- "Back to..." navigation links instead of relying on AppSidebar

This feature refactors all three worktrees pages to match the modern design patterns used in the projects pages:

- Use `AppLayout` for consistent project-scoped layout with AppSidebar navigation
- Use Shadcn UI components (Card, Button, Badge, Input, Select, etc.)
- Remove redundant "Back to..." links (AppSidebar provides navigation)
- Simplify and modernize the UI to match the rest of the application
- Ensure responsive design and proper dark mode support via Shadcn components

## Implementation Plan

### Frontend Components

**Files to modify:**

1. **`resources/js/pages/worktrees/index.tsx`** - Worktrees list page
    - Wrap content in `AppLayout` component
    - Replace custom div structure with Shadcn `Card` components
    - Use grid layout for worktree cards
    - Display: worktree name, directory path, and preview URL with external link icon
    - Use `Button` component for "Create Worktree" action
    - Use `Badge` component for status indicators
    - Add `ExternalLink` icon from lucide-react for preview URLs
    - Remove "Back to {project.name}" link
    - Use proper empty state with icon

2. **`resources/js/pages/worktrees/create.tsx`** - Create worktree form
    - Wrap content in `AppLayout` component
    - Replace custom form with Inertia `Form` component
    - Use Shadcn `Field` components for form fields (like in projects/create.tsx)
    - Replace raw HTML inputs with Shadcn `Input`, `Select`, `Checkbox` components
    - Use Shadcn `Button` for submit and cancel actions
    - Use `RadioGroup` for database isolation options (similar to server driver selection in project create)
    - Remove "Back to Worktrees" link
    - Add proper form validation error display

3. **`resources/js/pages/worktrees/show.tsx`** - Worktree details page
    - Wrap content in `AppLayout` component
    - Replace custom div structure with Shadcn `Card` components
    - Use `Badge` component for status display
    - Replace status color object with Shadcn badge variants
    - Use Shadcn `Button` for actions (Open Preview, Delete)
    - Use Shadcn `Alert` or `Card` with warning styling for status messages
    - Remove "Back to Worktrees" link
    - Use proper confirmation dialog for delete action

### Component Imports Needed

```typescript
// Common imports across all pages
import { AppLayout } from '@/components/layout/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ExternalLink, GitBranch, FolderIcon } from 'lucide-react';

// For create page
import { Form } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Field, FieldContent, FieldDescription, FieldGroup, FieldLabel, FieldSet, FieldTitle } from '@/components/ui/field';

// For show page
import { Alert, AlertDescription } from '@/components/ui/alert';
```

### Design Pattern Reference

**Follow these patterns from the projects pages:**

1. **Layout structure** (from projects/dashboard.tsx):

```tsx
<AppLayout>
    <div className='p-6 space-y-6'>
        <div className='flex items-center justify-between'>
            <h1 className='text-3xl font-bold'>Page Title</h1>
            <Button>Action</Button>
        </div>
        {/* Page content */}
    </div>
</AppLayout>
```

2. **Card grid layout** (from projects/index.tsx):

```tsx
<div className='grid gap-6 sm:grid-cols-2 lg:grid-cols-3'>
    {items.map((item) => (
        <Card key={item.id} className='transition-all hover:shadow-lg'>
            <CardHeader>
                <CardTitle>{item.name}</CardTitle>
                <CardDescription>{item.description}</CardDescription>
            </CardHeader>
            <CardContent>{/* Card content */}</CardContent>
        </Card>
    ))}
</div>
```

3. **Form structure** (from projects/create.tsx):

```tsx
<Form {...store.form()}>
    {({ errors, processing }) => (
        <FieldSet className='space-y-6'>
            <FieldGroup>
                <Field>
                    <FieldLabel htmlFor='name'>Field Label</FieldLabel>
                    <Input id='name' name='name' />
                    {errors.name && <p className='text-sm text-destructive'>{errors.name}</p>}
                </Field>
            </FieldGroup>
        </FieldSet>
    )}
</Form>
```

### Worktrees Index Page - Specific Requirements

**Card content structure:**

- **Header**: Branch name as title with status badge
- **Content**:
    - Directory path (font-mono, text-sm, muted)
    - Preview URL with external link icon
    - Database isolation badge
- **Hover**: Add hover effect like projects cards
- **Empty state**: Use proper empty state with icon and helpful message

**Example card structure:**

```tsx
<Card className='transition-all hover:shadow-lg hover:border-primary'>
    <CardHeader>
        <div className='flex items-center justify-between'>
            <CardTitle className='text-lg'>{worktree.branch_name}</CardTitle>
            <Badge variant={statusVariant}>{worktree.status}</Badge>
        </div>
    </CardHeader>
    <CardContent className='space-y-3'>
        <div className='flex items-center gap-2 text-sm text-muted-foreground font-mono'>
            <FolderIcon className='h-4 w-4' />
            <span className='truncate'>{worktree.path}</span>
        </div>
        <div className='flex items-center gap-2'>
            <a href={worktree.preview_url} target='_blank' className='text-sm text-primary hover:underline flex items-center gap-1'>
                {worktree.preview_url}
                <ExternalLink className='h-3 w-3' />
            </a>
        </div>
        <Badge variant='secondary' className='text-xs'>
            {worktree.database_isolation}
        </Badge>
    </CardContent>
</Card>
```

### Worktrees Create Page - Specific Requirements

**Form fields:**

1. Branch Name - Text input with validation
2. Create Branch - Checkbox
3. Database Isolation - Radio group with three options:
    - Separate Database (SQLite) - Recommended
    - Table Prefix
    - Shared Database

**Use RadioGroup pattern from projects/create.tsx** for database isolation selection with Field components showing descriptions.

### Worktrees Show Page - Specific Requirements

**Layout sections:**

1. **Page header**:
    - Title: Branch name
    - Status badge
    - Action buttons on the right

2. **Details card**:
    - Display all worktree information in a clean format
    - Use definition list pattern with proper spacing

3. **Action buttons**:
    - "Open Preview" button (only for active status)
    - "Delete Worktree" button with destructive variant

4. **Status alerts**:
    - Use Alert component for creating status
    - Use Alert with destructive variant for error messages

### Styling Conventions

**Replace these old patterns:**

- `bg-gray-50 dark:bg-gray-900` → Let AppLayout handle background
- `text-gray-900 dark:text-gray-100` → Use semantic color classes or let components handle it
- `rounded-md bg-blue-600 px-4 py-2` → Use Shadcn Button component
- Manual status color objects → Use Badge variants (default, secondary, destructive, outline)

**Use these new patterns:**

- `className='space-y-6'` for vertical spacing
- `className='flex items-center gap-3'` for horizontal layout
- `text-muted-foreground` for secondary text
- `text-primary` for accent text
- `text-destructive` for error text

### Badge Variants for Status

```typescript
const statusVariant = {
    creating: 'default', // or 'secondary'
    active: 'default', // green by default
    error: 'destructive',
    cleaning_up: 'secondary',
} as const;
```

## Acceptance Criteria

- [ ] Worktrees index page uses AppLayout wrapper
- [ ] Worktrees index page uses Shadcn Card components in grid layout
- [ ] Worktrees cards display name, directory, and preview URL with external link icon
- [ ] Worktrees index has proper empty state
- [ ] Create worktree page uses AppLayout wrapper
- [ ] Create worktree form uses Shadcn Form and Field components
- [ ] Database isolation uses RadioGroup with descriptions
- [ ] Show worktree page uses AppLayout wrapper
- [ ] Show worktree page uses Shadcn components for all UI elements
- [ ] All "Back to..." links are removed
- [ ] Status badges use proper Shadcn Badge variants
- [ ] All buttons use Shadcn Button component
- [ ] All form inputs use Shadcn Input/Select/Checkbox components
- [ ] Dark mode works properly (handled by Shadcn components)
- [ ] Responsive design works on mobile and desktop
- [ ] Hover effects work on interactive elements
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Browser Tests

**Test file location**: `tests/Browser/Worktrees/WorktreesLayoutTest.php`

**Key test cases:**

**Layout Tests:**

- Test worktrees pages use AppLayout (verify sidebar is present)
- Test AppSidebar navigation is available on all worktrees pages
- Test no "Back to..." links exist on any worktrees pages
- Test page header structure is correct

**Component Tests:**

- Test worktrees index displays cards in grid layout
- Test worktree cards show branch name, directory, and preview URL
- Test external link icon is present on preview URLs
- Test status badges display correctly
- Test empty state displays when no worktrees exist

**Create Page Tests:**

- Test create form uses Shadcn components
- Test all form fields are present and functional
- Test database isolation uses radio group
- Test form validation works
- Test submit button states (disabled when processing)

**Show Page Tests:**

- Test worktree details display in card
- Test status badge displays correctly
- Test "Open Preview" button is visible for active worktrees
- Test "Delete Worktree" button is visible
- Test creating status alert is visible for creating worktrees
- Test error alert is visible when error_message exists

**Interaction Tests:**

- Test clicking external link icon opens preview URL in new tab
- Test clicking "Create Worktree" button navigates to create page
- Test clicking worktree card navigates to show page
- Test clicking "Delete Worktree" shows confirmation dialog
- Test form submission works correctly

**Visual Tests:**

- Test responsive design on different viewport sizes
- Test dark mode styling works correctly
- Test hover effects work on cards and buttons
- Test all pages have consistent styling

**Navigation Tests:**

- Test AppSidebar "Worktrees" link is active on worktrees pages
- Test navigation between worktrees pages via sidebar
- Test no JavaScript errors on any page

### Manual Testing Checklist

**Worktrees Index:**

- [ ] Page loads with AppLayout and sidebar
- [ ] Worktree cards display in responsive grid
- [ ] Each card shows name, directory, preview URL, and status
- [ ] External link icon appears next to preview URL
- [ ] Clicking preview URL opens in new tab
- [ ] "Create Worktree" button is styled correctly
- [ ] Empty state displays when no worktrees
- [ ] Dark mode works correctly
- [ ] Responsive on mobile and desktop

**Worktrees Create:**

- [ ] Form loads with AppLayout and sidebar
- [ ] All form fields use Shadcn components
- [ ] Database isolation uses radio group with descriptions
- [ ] Form validation displays errors properly
- [ ] Submit button shows processing state
- [ ] Cancel button works correctly
- [ ] Dark mode works correctly

**Worktrees Show:**

- [ ] Details load with AppLayout and sidebar
- [ ] Status badge displays correctly
- [ ] All worktree information is visible
- [ ] "Open Preview" button appears for active worktrees
- [ ] "Delete Worktree" button works with confirmation
- [ ] Creating status alert displays for creating worktrees
- [ ] Error alert displays when error_message exists
- [ ] Dark mode works correctly

## Code Formatting

Format all code using:

- **Frontend (TypeScript/React)**: oxfmt
- Command: `pnpm run format`

## Additional Notes

### Migration Strategy

This is a pure UI refactoring - no backend changes needed:

- No database migrations required
- No controller changes required
- No route changes required
- Only frontend component updates

### Component Availability

All required Shadcn components should already be installed:

- Card, Button, Badge, Input, Label - already used in project pages
- Select, Checkbox, RadioGroup - check if installed, install if needed
- Alert - check if installed, install if needed
- Field components - already used in projects/create.tsx

If any component is missing, install with:

```bash
pnpm dlx shadcn@latest add [component-name]
```

### Backward Compatibility

No breaking changes - all pages maintain the same functionality:

- Same URLs
- Same data structure
- Same user flows
- Only UI/UX improvements

### Performance Considerations

- Shadcn components are lightweight and performant
- AppLayout is already used in dashboard, no performance impact
- Card grid layout may actually be more performant than old structure
- External link icons are SVG, very lightweight

### Accessibility Improvements

Shadcn components provide better accessibility:

- Proper ARIA labels
- Better keyboard navigation
- Improved focus management
- Semantic HTML structure

### Design Consistency

After this refactor, all pages will use consistent design patterns:

- Projects pages: CenteredCardLayout (standalone) + AppLayout (project-scoped)
- Worktrees pages: AppLayout (project-scoped)
- Dashboard: AppLayout
- Settings/Environment/Specs: Should follow AppLayout pattern

### Future Enhancements

Consider adding these features in future:

- Search/filter worktrees on index page
- Bulk actions (delete multiple worktrees)
- Status polling for creating worktrees
- Real-time status updates via Reverb
- Worktree analytics/stats

### Related Components

**Components that may need updates:**

- None - this refactoring is isolated to worktrees pages

**Components that interact with worktrees:**

- AppSidebar - already updated to use WorktreeController.index()
- Dashboard - may display worktrees count or list in future

### Example Components to Reference

When implementing, reference these files for patterns:

- Layout: `resources/js/pages/projects/dashboard.tsx`
- Cards: `resources/js/pages/projects/index.tsx`
- Forms: `resources/js/pages/projects/create.tsx`
- Buttons: Any page using Shadcn Button
- Badges: Any page using Shadcn Badge

### Error Handling

Maintain existing error handling:

- Form validation errors display under fields
- Network errors handled by Inertia
- Delete confirmation before worktree deletion
- Error messages display in alert components

### Icons Usage

Use lucide-react icons consistently:

- `ExternalLink` - For preview URL links
- `FolderIcon` - For directory paths
- `GitBranch` - For branch-related elements (already in sidebar)
- `AlertCircle` - For error alerts
- `Info` - For informational alerts

### Testing Edge Cases

Ensure tests cover:

- Empty worktrees list
- Single worktree
- Multiple worktrees
- Long branch names (truncation)
- Long directory paths (truncation)
- Long preview URLs (truncation/wrapping)
- Error messages display
- All worktree statuses (creating, active, error, cleaning_up)
- Missing preview_url
- Missing error_message

### Code Review Checklist

Before marking complete:

- [ ] All imports are organized and correct
- [ ] No unused imports or variables
- [ ] Consistent spacing and formatting
- [ ] No hardcoded colors (use semantic classes)
- [ ] All event handlers are properly typed
- [ ] TypeScript interfaces are accurate
- [ ] No console.log or debug code
- [ ] All Shadcn components used correctly
- [ ] Responsive classes are appropriate
- [ ] Accessibility attributes are present
- [ ] Dark mode works without explicit classes
- [ ] Code follows project conventions
