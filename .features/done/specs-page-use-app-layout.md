---
name: specs-page-use-app-layout
description: Update Specs pages to use AppLayout component with sidebars for consistent navigation
depends_on: null
---

## Feature Description

Currently, the Specs pages (`Specs/Index.tsx`, `Specs/Create.tsx`, `Specs/Show.tsx`, `Specs/Edit.tsx`) are missing the `AppLayout` component wrapper, which means they don't display the project sidebars (mini sidebar with project avatars and main sidebar with navigation links). This creates an inconsistent user experience where users lose navigation context when viewing specs.

All other project-specific pages (Dashboard, Worktrees, Settings, Environment) use `AppLayout` to provide:

- Mini sidebar with project avatars for quick project switching
- Main sidebar with navigation links (Dashboard, Worktrees, Specs, etc.)
- Consistent header and spacing
- Proper responsive behavior

**Current State**:

```tsx
// Specs/Index.tsx
export default function Index() {
    return <div>Specs Index</div>;
}
```

**Desired State**:

```tsx
// Specs/Index.tsx
export default function Index() {
    return (
        <AppLayout>
            <div className='p-6 space-y-6'>{/* Specs content */}</div>
        </AppLayout>
    );
}
```

## Implementation Plan

### Frontend Components

**Files to modify**:

1. `resources/js/pages/Specs/Index.tsx`
2. `resources/js/pages/Specs/Create.tsx`
3. `resources/js/pages/Specs/Show.tsx`
4. `resources/js/pages/Specs/Edit.tsx`

### Changes for Each File

#### 1. Specs/Index.tsx

**Current content**:

```tsx
export default function Index() {
    return <div>Specs Index</div>;
}
```

**Updated content**:

```tsx
import { AppLayout } from '@/components/layout/app-layout';
import { Head } from '@inertiajs/react';

export default function Index() {
    return (
        <>
            <Head title='Specs' />
            <AppLayout>
                <div className='p-6 space-y-6'>
                    <div>
                        <h1 className='text-3xl font-bold'>Feature Specifications</h1>
                        <p className='text-muted-foreground mt-2'>Manage and generate feature specifications for your project</p>
                    </div>
                    {/* TODO: Add specs list UI */}
                    <div className='text-muted-foreground'>Specs list coming soon...</div>
                </div>
            </AppLayout>
        </>
    );
}
```

#### 2. Specs/Create.tsx

Follow the same pattern:

```tsx
import { AppLayout } from '@/components/layout/app-layout';
import { Head } from '@inertiajs/react';

export default function Create() {
    return (
        <>
            <Head title='Create Spec' />
            <AppLayout>
                <div className='p-6 space-y-6'>
                    <div>
                        <h1 className='text-3xl font-bold'>Create Specification</h1>
                        <p className='text-muted-foreground mt-2'>Generate a new feature specification</p>
                    </div>
                    {/* TODO: Add spec creation form */}
                </div>
            </AppLayout>
        </>
    );
}
```

#### 3. Specs/Show.tsx

```tsx
import { AppLayout } from '@/components/layout/app-layout';
import { Head } from '@inertiajs/react';

interface ShowProps {
    spec: {
        id: number;
        title: string;
        content: string;
        // Add other spec properties
    };
}

export default function Show({ spec }: ShowProps) {
    return (
        <>
            <Head title={`${spec.title} - Spec`} />
            <AppLayout>
                <div className='p-6 space-y-6'>
                    <div>
                        <h1 className='text-3xl font-bold'>{spec.title}</h1>
                    </div>
                    {/* TODO: Add spec display UI */}
                    <div>
                        <pre className='whitespace-pre-wrap'>{spec.content}</pre>
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
```

#### 4. Specs/Edit.tsx

```tsx
import { AppLayout } from '@/components/layout/app-layout';
import { Head } from '@inertiajs/react';

interface EditProps {
    spec: {
        id: number;
        title: string;
        content: string;
        // Add other spec properties
    };
}

export default function Edit({ spec }: EditProps) {
    return (
        <>
            <Head title={`Edit ${spec.title}`} />
            <AppLayout>
                <div className='p-6 space-y-6'>
                    <div>
                        <h1 className='text-3xl font-bold'>Edit Specification</h1>
                        <p className='text-muted-foreground mt-2'>{spec.title}</p>
                    </div>
                    {/* TODO: Add spec edit form */}
                </div>
            </AppLayout>
        </>
    );
}
```

### Layout Pattern to Follow

Based on the dashboard page structure, all project-specific pages should follow this pattern:

```tsx
import { AppLayout } from '@/components/layout/app-layout';
import { Head } from '@inertiajs/react';

export default function PageName(
    {
        /* props */
    },
) {
    return (
        <>
            <Head title='Page Title' />
            <AppLayout>
                <div className='p-6 space-y-6'>
                    {/* Page header */}
                    <div>
                        <h1 className='text-3xl font-bold'>Page Title</h1>
                        <p className='text-muted-foreground mt-2'>Description</p>
                    </div>

                    {/* Page content */}
                    <div>{/* Content here */}</div>
                </div>
            </AppLayout>
        </>
    );
}
```

### Additional Considerations

**Check if Specs are project-specific**:

- If specs belong to a specific project, they should receive the project prop
- The sidebar "Specs" link should highlight when on specs pages
- Consider updating the sidebar navigation to properly highlight specs pages

**Backend verification**:

- Check `SpecController.php` to see what props are passed to each page
- Update TypeScript interfaces to match the actual data structure
- Ensure specs are associated with projects if they should be

## Acceptance Criteria

- [ ] Specs Index page displays with AppLayout wrapper
- [ ] Specs Create page displays with AppLayout wrapper
- [ ] Specs Show page displays with AppLayout wrapper
- [ ] Specs Edit page displays with AppLayout wrapper
- [ ] All specs pages show mini sidebar with project avatars
- [ ] All specs pages show main sidebar with navigation links
- [ ] "Specs" navigation link is highlighted when on specs pages
- [ ] Page title appears in browser tab (via Head component)
- [ ] Page layout matches other project pages (Dashboard, Worktrees, etc.)
- [ ] Proper spacing and padding applied (p-6 space-y-6)
- [ ] Responsive behavior works on mobile and desktop
- [ ] No console errors or warnings
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Browser Tests

**Test file location**: `tests/Browser/Specs/SpecsPagesLayoutTest.php`

**Key test cases**:

- Test specs index page displays with sidebars
- Test specs create page displays with sidebars
- Test specs show page displays with sidebars
- Test specs edit page displays with sidebars
- Test mini sidebar is visible on all specs pages
- Test main sidebar is visible on all specs pages
- Test "Specs" navigation link is highlighted on specs pages
- Test project avatars are clickable in mini sidebar
- Test navigation links work in main sidebar
- Test responsive behavior on mobile viewport
- Test no JavaScript errors in console

### Manual Testing Checklist

1. **Navigation**:
    - Click "Specs" link in sidebar
    - Verify page loads with full layout
    - Verify sidebars are visible

2. **Sidebar Functionality**:
    - Click project avatars to switch projects
    - Click navigation links to navigate
    - Verify "Specs" link is highlighted

3. **Visual Consistency**:
    - Compare specs page layout to dashboard
    - Verify spacing and padding match
    - Check header styling matches

4. **Responsive Testing**:
    - Test on desktop viewport
    - Test on mobile viewport
    - Verify sidebars collapse appropriately

## Code Formatting

Format all code using:

- **Frontend (TypeScript/React)**: Prettier/oxfmt
- Command: `pnpm run format`

## Additional Notes

### Why This Is Important

**User Experience Issues Without AppLayout**:

1. **Lost Navigation Context**: Users can't navigate to other sections without going back
2. **No Project Context**: Can't see which project they're working on
3. **Inconsistent UI**: Different pages have different layouts
4. **Reduced Productivity**: Extra clicks needed to navigate

**Benefits of Using AppLayout**:

1. **Consistent Navigation**: All pages have the same navigation structure
2. **Quick Project Switching**: Mini sidebar allows instant project changes
3. **Clear Context**: Main sidebar shows current location and project
4. **Professional Appearance**: Consistent layout throughout the app

### Current Specs Implementation

Based on the exploration, specs pages are very minimal (just placeholder content). This is an opportunity to:

1. Add proper layout structure
2. Plan the full specs UI implementation
3. Ensure consistent navigation from the start

### AppLayout Component

The `AppLayout` component (from `@/components/layout/app-layout`) provides:

- Nested sidebar structure (mini + main)
- Project context via Inertia shared data
- Responsive behavior
- Theme support
- Consistent spacing and styling

### Related Routes

Check `routes/web.php` for specs routes:

```php
Route::resource('specs', SpecController::class);
Route::post('/specs/generate', [SpecController::class, 'generate'])->name('specs.generate');
Route::post('/specs/{spec}/refine', [SpecController::class, 'refine'])->name('specs.refine');
```

Specs may need to be scoped to projects if they aren't already. Consider:

```php
Route::resource('projects.specs', SpecController::class);
```

### TypeScript Types

Update `resources/js/types/index.d.ts` if needed to include spec interfaces:

```typescript
export interface Spec {
    id: number;
    title: string;
    content: string;
    project_id: number | null;
    created_at: string;
    updated_at: string;
}

export interface SpecsIndexProps extends SharedProps {
    specs: Spec[];
}

export interface SpecsShowProps extends SharedProps {
    spec: Spec;
}
```

### Future Enhancements

After adding AppLayout:

- Implement full specs list UI with cards/table
- Add spec creation form with Shadcn components
- Add spec editing with markdown/WYSIWYG editor
- Add spec refinement interface
- Add spec generation interface
- Add filtering and search for specs
- Add spec status badges (draft, approved, implemented)
- Add spec versioning or history

### Sidebar Highlighting

To ensure the "Specs" link is highlighted when on specs pages, verify the `isActiveLink` function in `app-sidebar.tsx` handles specs routes correctly:

```typescript
const isActiveLink = (href: string | { url: string; method: string }) => {
    const linkUrl = typeof href === 'string' ? href : href.url;
    const currentUrl = typeof url === 'string' ? url : '';
    return currentUrl === linkUrl || (currentUrl.length > 0 && currentUrl.startsWith(linkUrl));
};
```

The current implementation should work since it uses `startsWith`, which will match `/specs`, `/specs/create`, `/specs/1`, etc.

### Implementation Order

1. Start with Specs/Index.tsx (most commonly used)
2. Then Specs/Create.tsx (needed for creating specs)
3. Then Specs/Show.tsx (for viewing individual specs)
4. Finally Specs/Edit.tsx (for editing existing specs)

### Testing with Real Data

After implementing the layout:

1. Create a test spec in the database
2. Navigate to specs index
3. Click on a spec to view
4. Click edit to modify
5. Verify navigation works throughout

### Accessibility Considerations

- Proper heading hierarchy (h1 for page title)
- Descriptive page titles in browser tab
- Proper focus management when navigating
- Keyboard navigation works in sidebars
- Screen reader compatible layout

### Performance Considerations

- AppLayout is already rendered once per page load
- No additional performance impact
- Inertia handles efficient page transitions
- Sidebars don't re-render unnecessarily
