---
name: inertia-filesystem-browser
description: Refactor filesystem browser to use Inertia routing with query strings instead of fetch
depends_on: null
---

## Feature Description

Refactor the project creation page's filesystem browser to use Inertia routing with query string parameters instead of fetch API calls. This change aligns the filesystem browsing experience with the rest of the application's Inertia-based navigation, providing better URL state management, browser history support, and a more consistent user experience.

Key changes:

- **Query String Navigation**: Use `?path=/some/directory` instead of POST requests
- **Inertia Router**: Use `router.visit()` and `router.reload()` for navigation
- **Preserves State**: Leverages Inertia's `preserveState` and `preserveScroll`
- **Browser History**: Users can use back/forward buttons to navigate directories
- **Shared State**: Directory data comes from server-side Inertia props
- **Lazy Loading**: Directories load on-demand as users navigate

This eliminates the need for manual fetch calls, CSRF token management, and loading state, while providing a better UX with proper browser history integration.

## Implementation Plan

### Backend Components

**Controllers to Modify:**

- `app/Http/Controllers/FileSystemController.php` - Convert to Inertia responses
    - Remove `browse()` method (merge into create page controller)
    - Remove `home()` method (merge into create page controller)

**Controllers to Update:**

- `app/Http/Controllers/ProjectController.php` - Update `create()` method
    - Accept optional `path` query parameter
    - Return directory listing data as Inertia props
    - Merge home path detection into this method

**Routes to Modify:**

- Remove `POST /api/filesystem/browse`
- Remove `GET /api/filesystem/home`
- Update `GET /projects/create` to accept `?path=` query parameter

**Actions (No Changes):**

- `app/Actions/ListDirectory.php` - Keep as-is
- `app/Actions/ExpandHomePath.php` - Keep as-is

### Frontend Components

**Pages to Modify:**

- `resources/js/pages/projects/create.tsx` - Receive filesystem data from props
    - Add `DirectoryData` to page props
    - Remove FolderBrowser's internal state management
    - Pass props down to FolderBrowser component

**Components to Modify:**

- `resources/js/components/file-explorer/folder-browser.tsx` - Refactor to use Inertia
    - Remove all `fetch()` calls
    - Remove internal loading state
    - Use `router.visit()` for navigation
    - Accept data from parent props instead of fetching

**Inertia Hooks to Use:**

- `router.visit()` - Navigate to new directory path
- `router.reload()` - Refresh current directory
- `preserveState: true` - Keep form data when browsing
- `preserveScroll: true` - Maintain scroll position
- `replace: true` - Replace URL instead of pushing to history (optional)
- `only: ['directories', 'breadcrumbs', 'currentPath']` - Partial reload optimization

**No New Components:**

- Reuse existing `BreadcrumbNav`, `DirectoryEntry`, `LoadingSkeleton`

### Updated Controller Implementation

```php
// app/Http/Controllers/ProjectController.php

public function create(Request $request): Response
{
    $path = $request->query('path');

    // Get home directory if no path specified
    if (!$path) {
        $home = getenv('HOME') ?: getenv('USERPROFILE');
        $path = $home !== false ? $home : '/';
    }

    // Security: Prevent directory traversal
    if (str_contains($path, '..')) {
        $path = '/';
    }

    // List directory contents
    $directoryData = app(ListDirectory::class)->handle($path);

    return Inertia::render('projects/create', [
        'directories' => $directoryData['directories'],
        'breadcrumbs' => $directoryData['breadcrumbs'],
        'currentPath' => $path,
        'homePath' => getenv('HOME') ?: getenv('USERPROFILE') ?: '/',
    ]);
}
```

### Updated Page Component Implementation

```tsx
// resources/js/pages/projects/create.tsx

interface Directory {
    name: string;
    path: string;
    type: string;
}

interface Breadcrumb {
    name: string;
    path: string;
}

interface Props {
    directories: Directory[];
    breadcrumbs: Breadcrumb[];
    currentPath: string;
    homePath: string;
}

export default function Create({ directories, breadcrumbs, currentPath, homePath }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        path: '',
        server_driver: 'caddy' as 'caddy' | 'nginx' | 'artisan',
        base_url: '',
    });

    const [headerProps, setHeaderProps] = useState<FolderBrowserHeaderProps | null>(null);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post('/projects');
    };

    const handlePathSelect = (path: string) => {
        // Extract folder name from path
        const folderName = path.split('/').filter(Boolean).pop() || '';
        const projectName = folderName
            .replace(/[^a-zA-Z0-9-_]/g, '-')
            .replace(/-+/g, '-')
            .toLowerCase();

        setData({
            ...data,
            path,
            name: folderName,
            base_url: projectName ? `${projectName}.localhost` : '',
        });
    };

    return (
        <>
            <Head title='Add Project' />

            <CenteredCardLayout>
                <CardHeader className='border-b'>
                    <div>
                        {headerProps && (
                            <div className='flex items-center'>
                                <Button type='button' variant='outline' size='icon' onClick={headerProps.onHomeClick}>
                                    <HomeIcon />
                                </Button>
                                <Input
                                    value={headerProps.inputPath}
                                    onChange={(e) => headerProps.setInputPath(e.target.value)}
                                    onKeyDown={headerProps.onInputKeyDown}
                                    placeholder={headerProps.homePath || 'Enter path...'}
                                />
                            </div>
                        )}
                    </div>
                </CardHeader>
                <CardContent className='py-1'>
                    <div className='space-y-6'>
                        <FolderBrowser
                            directories={directories}
                            breadcrumbs={breadcrumbs}
                            currentPath={currentPath}
                            homePath={homePath}
                            onPathSelect={handlePathSelect}
                            onHeaderPropsChange={setHeaderProps}
                        />

                        {data.path && (
                            <>
                                <Separator />
                                {/* Form remains the same */}
                                <form onSubmit={handleSubmit} className='space-y-6'>
                                    {/* ... existing form fields ... */}
                                </form>
                            </>
                        )}
                    </div>
                </CardContent>
            </CenteredCardLayout>
        </>
    );
}
```

### Updated FolderBrowser Component

```tsx
// resources/js/components/file-explorer/folder-browser.tsx

import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { ScrollArea } from '@/components/ui/scroll-area';
import { BreadcrumbNav } from './breadcrumb-nav';
import { DirectoryEntry } from './directory-entry';

interface Directory {
    name: string;
    path: string;
    type: string;
}

interface Breadcrumb {
    name: string;
    path: string;
}

export interface FolderBrowserHeaderProps {
    inputPath: string;
    setInputPath: (path: string) => void;
    onInputKeyDown: (e: React.KeyboardEvent<HTMLInputElement>) => void;
    onHomeClick: () => void;
    homePath: string;
}

interface FolderBrowserProps {
    directories: Directory[];
    breadcrumbs: Breadcrumb[];
    currentPath: string;
    homePath: string;
    onPathSelect: (path: string) => void;
    onHeaderPropsChange?: (props: FolderBrowserHeaderProps) => void;
}

export function FolderBrowser({ directories, breadcrumbs, currentPath, homePath, onPathSelect, onHeaderPropsChange }: FolderBrowserProps) {
    const [inputPath, setInputPath] = useState<string>(currentPath);

    // Update inputPath when currentPath changes from server
    useEffect(() => {
        setInputPath(currentPath);
    }, [currentPath]);

    const navigateToPath = (path: string) => {
        router.visit(`/projects/create?path=${encodeURIComponent(path)}`, {
            preserveState: true,
            preserveScroll: true,
            only: ['directories', 'breadcrumbs', 'currentPath'],
        });
    };

    const handleDirectoryClick = (path: string) => {
        navigateToPath(path);
    };

    const handleBreadcrumbClick = (path: string) => {
        navigateToPath(path);
    };

    const handleSelectPath = () => {
        onPathSelect(currentPath);
    };

    const handleHomeClick = () => {
        navigateToPath(homePath);
    };

    const handleInputKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Enter' && inputPath.trim()) {
            navigateToPath(inputPath.trim());
        }
    };

    // Notify parent of header props for external header control
    useEffect(() => {
        if (onHeaderPropsChange) {
            onHeaderPropsChange({
                inputPath,
                setInputPath,
                onInputKeyDown: handleInputKeyDown,
                onHomeClick: handleHomeClick,
                homePath,
            });
        }
    }, [inputPath, homePath, onHeaderPropsChange]);

    return (
        <div className='space-y-4'>
            <BreadcrumbNav breadcrumbs={breadcrumbs} onBreadcrumbClick={handleBreadcrumbClick} />

            <ScrollArea className='h-64'>
                <div className='space-y-1'>
                    {directories.length === 0 ? (
                        <div className='text-center py-8 text-muted-foreground text-sm'>No subdirectories found</div>
                    ) : (
                        directories.map((dir) => <DirectoryEntry key={dir.path} directory={dir} onClick={() => handleDirectoryClick(dir.path)} />)
                    )}
                </div>
            </ScrollArea>

            <div className='flex items-center gap-2 pt-2 border-t'>
                <div className='flex-1 text-sm font-mono text-muted-foreground truncate'>{currentPath || 'No path selected'}</div>
                <button
                    type='button'
                    onClick={handleSelectPath}
                    disabled={!currentPath}
                    className='px-3 py-1.5 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed'
                >
                    Select
                </button>
            </div>
        </div>
    );
}
```

### Routes Update

```php
// routes/web.php

// Remove these routes:
// Route::prefix('api/filesystem')->name('api.filesystem.')->group(function () {
//     Route::post('/browse', [FileSystemController::class, 'browse'])->name('browse');
//     Route::get('/home', [FileSystemController::class, 'home'])->name('home');
// });

// ProjectController::create already exists, just needs updating:
Route::resource('projects', ProjectController::class);
```

### Cleanup FileSystemController

Since the filesystem browsing is now integrated into the `ProjectController::create()` method, the `FileSystemController` can be deleted entirely:

```bash
# Delete the controller
rm app/Http/Controllers/FileSystemController.php

# Update routes/web.php to remove the import
# Remove: use App\Http\Controllers\FileSystemController;
```

## Acceptance Criteria

- [ ] `ProjectController::create()` accepts `path` query parameter
- [ ] `ProjectController::create()` returns directory data as Inertia props
- [ ] `ProjectController::create()` uses `ListDirectory` action
- [ ] `ProjectController::create()` provides home path detection
- [ ] `FileSystemController` is deleted
- [ ] Filesystem API routes are removed from `routes/web.php`
- [ ] Create page props include `directories`, `breadcrumbs`, `currentPath`, `homePath`
- [ ] FolderBrowser receives data from props instead of fetching
- [ ] FolderBrowser uses `router.visit()` for navigation
- [ ] Clicking a directory navigates with query string (`?path=/foo/bar`)
- [ ] Clicking breadcrumb navigates to that path
- [ ] Home button navigates to home directory
- [ ] Manual path input works with Enter key
- [ ] Form data (name, base_url, server_driver) persists during browsing
- [ ] Scroll position is preserved during navigation
- [ ] No fetch calls in FolderBrowser component
- [ ] No manual CSRF token management
- [ ] No manual loading state management
- [ ] Browser back/forward buttons work correctly
- [ ] URL reflects current directory path
- [ ] Security: Directory traversal prevention still works
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location:** `tests/Feature/Projects/FilesystemBrowserTest.php`

**Key test cases:**

- Test project create page loads with default home directory
- Test project create page accepts path query parameter
- Test project create page returns directory listing
- Test project create page returns breadcrumbs
- Test project create page rejects directory traversal attempts
- Test project create page handles invalid paths
- Test project create page returns home path
- Test navigating to subdirectory updates props

### Browser Tests

**Test file location:** `tests/Browser/Projects/FilesystemBrowserTest.php`

**Key test cases:**

- Test filesystem browser displays home directory on initial load
- Test clicking a directory navigates with query string
- Test URL updates with new path when directory is clicked
- Test clicking breadcrumb navigates to that path
- Test clicking home button navigates to home directory
- Test typing path and pressing Enter navigates to that path
- Test selecting a path populates the form fields
- Test form data persists when browsing directories
- Test browser back button returns to previous directory
- Test browser forward button goes to next directory
- Test URL reflects current directory path
- Test invalid path shows error or empty state

## Code Formatting

Format all code using: **Prettier** and **oxfmt** (for JavaScript/TypeScript), **Pint** (for PHP)

Commands to run:

- `pnpm run format` - Format JavaScript/TypeScript files
- `vendor/bin/pint --dirty` - Format PHP files

## Additional Notes

### Benefits of Inertia Approach

**Better UX:**

- Browser history works (back/forward buttons)
- URLs are shareable (can bookmark specific directory)
- Page state persists in URL
- Standard browser navigation patterns

**Simpler Code:**

- No manual fetch calls
- No CSRF token management
- No loading state management
- No error handling for network requests
- Leverages Inertia's built-in features

**Consistency:**

- Matches rest of application's navigation pattern
- Uses same routing approach as other pages
- Familiar to developers working on the codebase

### Inertia Partial Reloads

Use `only` parameter to reload just the filesystem data:

```tsx
router.visit(`/projects/create?path=${path}`, {
    only: ['directories', 'breadcrumbs', 'currentPath'],
});
```

This prevents re-rendering the entire page and only updates the filesystem browser section.

### Query String Encoding

Always encode paths in URLs:

```tsx
const url = `/projects/create?path=${encodeURIComponent(path)}`;
router.visit(url, { ... });
```

This handles paths with special characters (spaces, ampersands, etc.).

### Preserving Form State

Use `preserveState: true` to keep form data during navigation:

```tsx
router.visit(url, {
    preserveState: true,
    preserveScroll: true,
});
```

This ensures the user's selected project name, base URL, and server driver don't reset when browsing directories.

### Alternative: Replace vs Push History

**Push to History (Default):**

```tsx
router.visit(url, { preserveState: true });
```

Every directory click adds to browser history.

**Replace History:**

```tsx
router.visit(url, { preserveState: true, replace: true });
```

Directory navigation replaces current history entry instead of adding new ones.

Consider using `replace: true` to prevent cluttering browser history with every directory navigation.

### Handling Loading States

Inertia provides global loading indicators via:

- Progress bar (configured in `app.tsx`)
- `router.on('start', ...)` event listeners
- `processing` prop from Inertia

No need for manual loading spinners in the component.

### Error Handling

Inertia handles errors automatically:

- 404: Directory not found → Inertia error page
- 403: Permission denied → Inertia error page
- 500: Server error → Inertia error page

You can customize error handling in `app.tsx` or add a custom error boundary.

### Security Considerations

**Directory Traversal Prevention:**
Still validate paths on the server:

```php
if (str_contains($path, '..')) {
    $path = '/';
}
```

**Path Validation:**
Consider additional validation:

```php
if (!is_dir($path) || !is_readable($path)) {
    $path = '/';
}
```

### Performance Optimization

**Partial Reloads:**
Only reload filesystem data:

```tsx
only: ['directories', 'breadcrumbs', 'currentPath'];
```

**Debouncing:**
For manual path input, consider debouncing:

```tsx
import { useDebouncedCallback } from 'use-debounce';

const debouncedNavigate = useDebouncedCallback((path: string) => {
    navigateToPath(path);
}, 300);
```

**Prefetching:**
Prefetch common directories on hover:

```tsx
<DirectoryEntry
    onMouseEnter={() => {
        router.reload({ data: { path: dir.path }, only: ['directories'] });
    }}
/>
```

### Migration Notes

**Breaking Changes:**

- `FileSystemController` will be deleted
- Filesystem API routes will be removed
- FolderBrowser component API changes (now requires props)

**Migration Steps:**

1. Update `ProjectController::create()` method
2. Update `projects/create.tsx` page props
3. Update `FolderBrowser` component to use Inertia
4. Remove filesystem API routes
5. Delete `FileSystemController`
6. Update Wayfinder imports (if using filesystem controller actions)

**Rollback Plan:**
Keep the old `FileSystemController` temporarily with a different route prefix during testing:

```php
// Temporary backward compatibility
Route::prefix('api/filesystem-legacy')->group(function () {
    Route::post('/browse', [FileSystemController::class, 'browse']);
    Route::get('/home', [FileSystemController::class, 'home']);
});
```

Remove after confirming the new approach works correctly.

### URL Structure Examples

**Initial Load (Home Directory):**

```
/projects/create
→ Loads home directory (/Users/username)
```

**Navigate to Subdirectory:**

```
/projects/create?path=%2FUsers%2Fusername%2FProjects
→ Shows /Users/username/Projects
```

**Manual Path Entry:**

```
/projects/create?path=%2Fvar%2Fwww
→ Shows /var/www
```

**Home Button Click:**

```
/projects/create?path=%2FUsers%2Fusername
→ Returns to home directory
```

### Dependencies

This feature modifies the existing filesystem browser implementation.

**Depends on:**

- Existing `folder-explorer-project-creation` feature (refactors it)

**Implementation order:**

1. This feature refactors the existing implementation
2. Can be implemented independently
3. No other features depend on the fetch-based approach
