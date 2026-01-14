---
name: folder-explorer-project-creation
description: Interactive folder explorer for selecting project paths
depends_on: project-pages-centered-layout
---

## Feature Description

Replace the manual text input for project paths in the create project form with an interactive folder/file explorer that allows users to visually browse the server's file system. Users can click through directories, navigate with breadcrumbs, and select the desired project folder with a single click.

Key features:

- **Interactive Directory Browser**: Click folders to expand and explore contents
- **Breadcrumb Navigation**: Visual path display with clickable segments
- **Home Button**: Quick navigation to `~/` (user's home directory)
- **Real-time Loading**: Server-side directory listing via AJAX calls
- **Visual Indicators**: Icons for folders vs files, loading states, permissions
- **Select & Submit**: Click a folder to select it, then submit the form

This replaces the error-prone manual path entry with an intuitive GUI that prevents typos, shows available directories, and provides context about the file system structure.

## Implementation Plan

### Backend Components

**Controllers:**

- Create `app/Http/Controllers/FileSystemController.php` - Handle directory listing requests

**Actions:**

- Create `app/Actions/ListDirectory.php` - Reusable action to list directory contents with permissions check
- Create `app/Actions/ExpandHomePath.php` - Expand `~` to actual home directory path

**Routes:**

- Add `GET /api/filesystem/browse` - List directory contents
- Add `GET /api/filesystem/home` - Get user's home directory path

**API Response Format:**

```php
{
  "path": "/Users/username/Projects",
  "entries": [
    {
      "name": "my-laravel-app",
      "path": "/Users/username/Projects/my-laravel-app",
      "type": "directory", // or "file"
      "readable": true,
      "writable": true,
      "size": null, // for directories
      "modified_at": "2024-01-15T10:30:00Z"
    },
    // ...
  ],
  "parent": "/Users/username", // null if at root
  "breadcrumbs": [
    { "name": "Users", "path": "/Users" },
    { "name": "username", "path": "/Users/username" },
    { "name": "Projects", "path": "/Users/username/Projects" }
  ]
}
```

**Security:**

- Validate and sanitize all path inputs
- Prevent directory traversal attacks (`../` exploitation)
- Only allow listing directories, not reading file contents
- Implement path whitelist/blacklist if needed
- Check file permissions before listing

**Form Request:**

- Modify `app/Http/Requests/StoreProjectRequest.php` - Ensure path validation works with selected paths

### Frontend Components

**Components:**

- Create `resources/js/components/file-explorer/folder-browser.tsx` - Main folder browser component
- Create `resources/js/components/file-explorer/breadcrumb-nav.tsx` - Breadcrumb navigation component
- Create `resources/js/components/file-explorer/directory-entry.tsx` - Individual folder/file row component
- Create `resources/js/components/file-explorer/home-button.tsx` - Quick home navigation button
- Create `resources/js/components/file-explorer/loading-skeleton.tsx` - Loading state skeleton

**Shadcn Components to Install:**

- `pnpm dlx shadcn@latest add breadcrumb` - For breadcrumb navigation
- `pnpm dlx shadcn@latest add skeleton` - For loading states
- May need `table` component: `pnpm dlx shadcn@latest add table`

**Icons from Lucide:**

- `Folder` - Closed folder icon
- `FolderOpen` - Open folder icon
- `File` - Generic file icon
- `Home` - Home directory icon
- `ChevronRight` - Breadcrumb separator
- `Loader2` - Loading spinner
- `Lock` - Locked/no permission icon

**State Management:**

- Current directory path (string)
- Directory entries (array)
- Selected folder path (string)
- Loading state (boolean)
- Error state (string | null)
- Breadcrumb trail (array)

### Folder Browser Component Structure

```tsx
// resources/js/components/file-explorer/folder-browser.tsx
interface FolderBrowserProps {
    initialPath?: string; // Default to home directory
    onSelect: (path: string) => void; // Callback when folder selected
    value: string; // Currently selected path
}

export function FolderBrowser({ initialPath, onSelect, value }: FolderBrowserProps) {
    const [currentPath, setCurrentPath] = useState(initialPath || '~');
    const [entries, setEntries] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    // Fetch directory contents
    const fetchDirectory = async (path: string) => {
        setLoading(true);
        try {
            const response = await fetch(`/api/filesystem/browse?path=${encodeURIComponent(path)}`);
            const data = await response.json();
            setEntries(data.entries);
            setCurrentPath(data.path);
            setBreadcrumbs(data.breadcrumbs);
        } catch (err) {
            setError('Failed to load directory');
        } finally {
            setLoading(false);
        }
    };

    // Navigate to directory
    const navigateToDirectory = (path: string) => {
        fetchDirectory(path);
    };

    // Select folder
    const selectFolder = (path: string) => {
        onSelect(path);
    };

    return (
        <div className='space-y-4'>
            {/* Breadcrumb + Home Button */}
            <div className='flex items-center gap-2'>
                <HomeButton onClick={() => navigateToDirectory('~')} />
                <BreadcrumbNav breadcrumbs={breadcrumbs} onNavigate={navigateToDirectory} />
            </div>

            {/* Directory Listing */}
            <ScrollArea className='h-[400px] border rounded-md'>
                {loading ? (
                    <LoadingSkeleton />
                ) : error ? (
                    <div className='p-4 text-destructive'>{error}</div>
                ) : (
                    <div className='divide-y'>
                        {entries.map((entry) => (
                            <DirectoryEntry
                                key={entry.path}
                                entry={entry}
                                selected={value === entry.path}
                                onNavigate={navigateToDirectory}
                                onSelect={selectFolder}
                            />
                        ))}
                    </div>
                )}
            </ScrollArea>

            {/* Selected Path Display */}
            {value && (
                <div className='text-sm text-muted-foreground'>
                    Selected: <span className='font-mono'>{value}</span>
                </div>
            )}
        </div>
    );
}
```

### Directory Entry Component

```tsx
// resources/js/components/file-explorer/directory-entry.tsx
interface DirectoryEntryProps {
    entry: {
        name: string;
        path: string;
        type: 'directory' | 'file';
        readable: boolean;
        writable: boolean;
    };
    selected: boolean;
    onNavigate: (path: string) => void;
    onSelect: (path: string) => void;
}

export function DirectoryEntry({ entry, selected, onNavigate, onSelect }: DirectoryEntryProps) {
    const isDirectory = entry.type === 'directory';

    const handleClick = () => {
        if (isDirectory && entry.readable) {
            onNavigate(entry.path);
        }
    };

    const handleSelect = (e: React.MouseEvent) => {
        e.stopPropagation();
        if (isDirectory) {
            onSelect(entry.path);
        }
    };

    return (
        <div
            className={cn(
                'flex items-center gap-3 p-3 hover:bg-muted/50 cursor-pointer transition-colors',
                selected && 'bg-primary/10 border-l-2 border-primary',
            )}
            onClick={handleClick}
        >
            {/* Icon */}
            <div className='shrink-0'>
                {isDirectory ? (
                    entry.readable ? (
                        <Folder className='h-5 w-5 text-blue-500' />
                    ) : (
                        <Lock className='h-5 w-5 text-muted-foreground' />
                    )
                ) : (
                    <File className='h-5 w-5 text-muted-foreground' />
                )}
            </div>

            {/* Name */}
            <div className='flex-1 truncate'>
                <span className='text-sm font-medium'>{entry.name}</span>
            </div>

            {/* Select Button (for directories only) */}
            {isDirectory && entry.readable && (
                <Button variant='ghost' size='sm' onClick={handleSelect} className='shrink-0'>
                    Select
                </Button>
            )}
        </div>
    );
}
```

### Integration with Create Project Form

```tsx
// resources/js/pages/projects/create.tsx
export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        path: '',
        server_driver: 'caddy' as 'caddy' | 'nginx',
        base_url: '',
    });

    return (
        <CenteredCardLayout>
            <CardHeader>
                <CardTitle>Create Project</CardTitle>
                <CardDescription>Select a Laravel project directory</CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={handleSubmit} className='space-y-6'>
                    {/* Project Name */}
                    <div className='space-y-2'>
                        <Label htmlFor='name'>Project Name</Label>
                        <Input id='name' value={data.name} onChange={(e) => setData('name', e.target.value)} />
                        {errors.name && <p className='text-sm text-destructive'>{errors.name}</p>}
                    </div>

                    {/* Folder Browser */}
                    <div className='space-y-2'>
                        <Label>Project Path</Label>
                        <FolderBrowser value={data.path} onSelect={(path) => setData('path', path)} />
                        {errors.path && <p className='text-sm text-destructive'>{errors.path}</p>}
                    </div>

                    {/* Server Driver, Base URL, etc. */}
                    {/* ... */}

                    {/* Submit Button */}
                    <Button type='submit' disabled={processing || !data.path}>
                        {processing ? 'Creating...' : 'Create Project'}
                    </Button>
                </form>
            </CardContent>
        </CenteredCardLayout>
    );
}
```

### Backend Implementation

**FileSystemController.php:**

```php
<?php

namespace App\Http\Controllers;

use App\Actions\ExpandHomePath;
use App\Actions\ListDirectory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FileSystemController extends Controller
{
    public function browse(Request $request, ListDirectory $listDirectory, ExpandHomePath $expandHomePath): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $expandHomePath->handle($request->input('path'));

        // Security: Validate path
        if ($this->isPathForbidden($path)) {
            return response()->json([
                'error' => 'Access denied to this path',
            ], 403);
        }

        try {
            $result = $listDirectory->handle($path);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to read directory: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function home(ExpandHomePath $expandHomePath): JsonResponse
    {
        return response()->json([
            'path' => $expandHomePath->handle('~'),
        ]);
    }

    private function isPathForbidden(string $path): bool
    {
        // Implement path validation logic
        // Check for directory traversal attempts
        // Check against blacklist (e.g., /etc, /var, system directories)
        return false; // Placeholder
    }
}
```

**ListDirectory Action:**

```php
<?php

namespace App\Actions;

class ListDirectory
{
    public function handle(string $path): array
    {
        if (!is_dir($path) || !is_readable($path)) {
            throw new \Exception('Directory not found or not readable');
        }

        $entries = [];
        $items = scandir($path);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = rtrim($path, '/') . '/' . $item;
            $isDirectory = is_dir($fullPath);

            $entries[] = [
                'name' => $item,
                'path' => $fullPath,
                'type' => $isDirectory ? 'directory' : 'file',
                'readable' => is_readable($fullPath),
                'writable' => is_writable($fullPath),
                'size' => $isDirectory ? null : filesize($fullPath),
                'modified_at' => date('c', filemtime($fullPath)),
            ];
        }

        // Sort: directories first, then alphabetically
        usort($entries, function ($a, $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'directory' ? -1 : 1;
            }
            return strcasecmp($a['name'], $b['name']);
        });

        $breadcrumbs = $this->generateBreadcrumbs($path);
        $parent = dirname($path);

        return [
            'path' => $path,
            'entries' => $entries,
            'parent' => $parent !== $path ? $parent : null,
            'breadcrumbs' => $breadcrumbs,
        ];
    }

    private function generateBreadcrumbs(string $path): array
    {
        $parts = explode('/', trim($path, '/'));
        $breadcrumbs = [];
        $currentPath = '';

        foreach ($parts as $part) {
            if (empty($part)) {
                continue;
            }

            $currentPath .= '/' . $part;
            $breadcrumbs[] = [
                'name' => $part,
                'path' => $currentPath,
            ];
        }

        return $breadcrumbs;
    }
}
```

## Acceptance Criteria

- [ ] Folder browser displays on project create page
- [ ] Clicking a folder navigates into that directory
- [ ] Breadcrumb navigation displays current path
- [ ] Clicking breadcrumb segment navigates to that directory
- [ ] Home button navigates to `~/` user home directory
- [ ] Clicking "Select" button on a folder selects that path
- [ ] Selected path is displayed below the browser
- [ ] Loading state shows skeleton while fetching directory contents
- [ ] Error state displays helpful message if directory cannot be read
- [ ] Only directories are selectable (not files)
- [ ] Locked/inaccessible directories show lock icon
- [ ] Form cannot be submitted without selecting a path
- [ ] Backend validates and sanitizes all paths for security
- [ ] Backend prevents directory traversal attacks
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location:** `tests/Feature/FileSystem/FileSystemControllerTest.php`

**Key test cases:**

- Test browse endpoint returns directory contents
- Test browse endpoint returns breadcrumbs
- Test browse endpoint expands `~` to home directory
- Test browse endpoint rejects invalid paths
- Test browse endpoint rejects directory traversal attempts (`../`)
- Test browse endpoint returns 403 for forbidden paths
- Test browse endpoint returns 500 for unreadable directories
- Test home endpoint returns user home directory path

### Unit Tests

**Test file location:** `tests/Unit/Actions/ListDirectoryTest.php`

**Key test cases:**

- Test ListDirectory action lists directory contents correctly
- Test ListDirectory action sorts directories before files
- Test ListDirectory action sorts entries alphabetically
- Test ListDirectory action generates breadcrumbs correctly
- Test ListDirectory action handles root directory
- Test ListDirectory action throws exception for non-existent directory
- Test ListDirectory action respects file permissions
- Test ExpandHomePath action expands `~` to home directory

### Browser Tests

**Test file location:** `tests/Browser/Projects/FolderExplorerTest.php`

**Key test cases:**

- Test folder browser displays on create project page
- Test clicking folder navigates into directory
- Test breadcrumb navigation works
- Test home button navigates to home directory
- Test selecting a folder populates the path field
- Test loading state displays while fetching
- Test error state displays when directory cannot be read
- Test form validation prevents submission without path
- Test form submits successfully with selected path
- Test created project has correct path from explorer

## Code Formatting

Format all code using: **Prettier** and **oxfmt** (for JavaScript/TypeScript), **Pint** (for PHP)

Commands to run:

- `pnpm run format` - Format JavaScript/TypeScript files
- `vendor/bin/pint --dirty` - Format PHP files

## Additional Notes

### Security Considerations

**Critical Security Measures:**

1. **Path Validation:**
    - Sanitize all path inputs
    - Use `realpath()` to resolve symbolic links and `..` traversal
    - Reject paths containing `..` before validation

2. **Path Whitelist/Blacklist:**
    - Blacklist sensitive directories: `/etc`, `/var`, `/proc`, `/sys`
    - Optionally whitelist allowed base paths (e.g., `/home`, `/var/www`)

3. **Permission Checks:**
    - Verify `is_readable()` before listing directory
    - Only show directories user has permission to read
    - Never expose file contents, only metadata

4. **Rate Limiting:**
    - Apply rate limiting to browse endpoint
    - Prevent enumeration attacks

### Performance Considerations

**Optimization Strategies:**

1. **Limit Directory Size:**
    - Limit entries returned to 1000 items
    - Add pagination for large directories
    - Sort and filter on server-side

2. **Caching:**
    - Cache directory listings for 30 seconds
    - Invalidate cache on write operations
    - Use cache key based on path

3. **Lazy Loading:**
    - Only fetch directory contents when expanded
    - Don't preload entire file system tree
    - Debounce rapid navigation clicks

### UX Enhancements

**Nice-to-Have Features:**

1. **Search/Filter:**
    - Add search box to filter visible entries
    - Filter by file type (directories only, files only)
    - Fuzzy search on entry names

2. **Keyboard Navigation:**
    - Arrow keys to navigate entries
    - Enter to navigate into folder
    - Space to select folder
    - Escape to go up one level

3. **Recent Paths:**
    - Show recently browsed paths
    - Quick access to common directories

4. **Favorites:**
    - Allow bookmarking frequently used directories
    - Persist favorites in localStorage or user preferences

### Alternative Approach: Manual Input Toggle

Allow users to toggle between folder browser and manual input:

```tsx
<div className='space-y-2'>
    <div className='flex items-center justify-between'>
        <Label>Project Path</Label>
        <Button variant='ghost' size='sm' onClick={() => setShowExplorer(!showExplorer)}>
            {showExplorer ? 'Enter Manually' : 'Browse Folders'}
        </Button>
    </div>

    {showExplorer ? (
        <FolderBrowser value={data.path} onSelect={(path) => setData('path', path)} />
    ) : (
        <Input value={data.path} onChange={(e) => setData('path', e.target.value)} placeholder='/var/www/myproject' />
    )}
</div>
```

**Benefits:**

- Power users can still type paths quickly
- Fallback if folder browser has issues
- Easier to paste paths from elsewhere

### Cross-Platform Compatibility

**Path Handling:**

- Linux/macOS: Use `/` as separator
- Windows: Convert `\` to `/` for consistency
- Home directory: Expand `~` on all platforms

**File Icons:**

- Use generic folder/file icons (Lucide)
- Optionally add platform-specific icons (Mac, Linux, Windows folders)

### Dependencies

This feature depends on `project-pages-centered-layout` because:

- Uses the same card layout for consistent design
- Replaces the path input field in the create form
- Builds on the refactored create page structure

**Implementation order:**

1. Complete `projects-list-redesign`
2. Complete `project-pages-centered-layout`
3. Then implement `folder-explorer-project-creation`
