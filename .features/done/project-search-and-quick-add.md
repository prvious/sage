---
name: project-search-and-quick-add
description: Add search input with backend filtering and Plus button to projects list
depends_on: projects-list-redesign
---

## Feature Description

Add a search input field and a "+" (Plus) icon button to the CardHeader of the projects list page. The search functionality will perform server-side filtering instead of client-side filtering, providing real-time search results as the user types. The Plus button provides quick access to create a new project.

Key features:

- **Search Input**: Full-width search field in CardHeader for filtering projects
- **Backend Search**: AJAX requests to server for filtered results (not client-side filtering)
- **Real-time Results**: Debounced search updates as user types
- **Search Scope**: Searches project name, path, and base_url fields
- **Plus Button**: Icon button on the right side of CardHeader for creating new projects
- **Clean UI**: Remove redundant title/description, focus on search and action button

This replaces the static "Projects" title and description with an interactive search interface, making it easier to find projects in a growing list while providing quick access to project creation.

## Implementation Plan

### Backend Components

**Controllers:**

- Modify `app/Http/Controllers/ProjectController.php` - Add search parameter handling to index method

**Routes:**

- Modify existing `GET /projects` route to accept optional `search` query parameter

**Query Logic:**

```php
// In ProjectController@index
$query = Project::query()
    ->withCount(['worktrees', 'tasks']);

if ($search = $request->query('search')) {
    $query->where(function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
          ->orWhere('path', 'like', "%{$search}%")
          ->orWhere('base_url', 'like', "%{$search}%");
    });
}

$projects = $query->latest()->get();
```

**No Database Changes:**

- No migrations needed
- Using existing projects table columns
- May add index on `name` column for performance if needed

### Frontend Components

**Pages:**

- Modify `resources/js/pages/projects/index.tsx` - Update CardHeader with search input and Plus button

**Components:**

- May create `resources/js/components/project-search.tsx` - Reusable search input component (optional)

**Shadcn Components:**

- Use existing `Input` component for search field
- Use existing `Button` component for Plus icon button
- May need to install `command` component for enhanced search UI: `pnpm dlx shadcn@latest add command` (optional)

**Icons from Lucide:**

- `Search` - Search icon to display in input
- `Plus` - Plus icon for create project button
- `X` - Clear search icon (optional)

**State Management:**

- Search query (string)
- Debounce timer for search requests
- Loading state (boolean)
- Search results (projects array)

### Search Input Component Structure

**Layout in CardHeader:**

```tsx
<Card className='w-full max-w-4xl'>
    <CardHeader>
        <div className='flex items-center gap-4'>
            {/* Search Input - Takes up most space */}
            <div className='flex-1 relative'>
                <Search className='absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground' />
                <Input
                    type='search'
                    placeholder='Search projects by name, path, or URL...'
                    value={searchQuery}
                    onChange={handleSearchChange}
                    className='pl-10 pr-10'
                />
                {searchQuery && (
                    <button onClick={clearSearch} className='absolute right-3 top-1/2 -translate-y-1/2'>
                        <X className='h-4 w-4 text-muted-foreground hover:text-foreground' />
                    </button>
                )}
            </div>

            {/* Plus Button - Fixed width */}
            <Button size='icon' variant='default' onClick={() => router.visit('/projects/create')} title='Create New Project'>
                <Plus className='h-5 w-5' />
            </Button>
        </div>
    </CardHeader>
    <CardContent>
        <ScrollArea className='h-[400px]'>{/* Project list */}</ScrollArea>
    </CardContent>
</Card>
```

### Search Functionality Implementation

**Debounced Search:**

```tsx
import { router } from '@inertiajs/react';
import { useState, useEffect } from 'react';

export default function Index({ projects, search = '' }) {
    const [searchQuery, setSearchQuery] = useState(search);

    // Debounce search requests
    useEffect(() => {
        const timer = setTimeout(() => {
            router.get(
                '/projects',
                { search: searchQuery },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['projects'], // Only reload projects data
                },
            );
        }, 300); // 300ms debounce

        return () => clearTimeout(timer);
    }, [searchQuery]);

    const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSearchQuery(e.target.value);
    };

    const clearSearch = () => {
        setSearchQuery('');
    };

    return (
        <div className='min-h-screen bg-muted flex flex-col items-center justify-center p-4'>
            {/* Sage Logo */}
            <div className='mb-8'>
                <SageLogo />
            </div>

            {/* Projects Card */}
            <Card className='w-full max-w-4xl'>
                <CardHeader>
                    {/* Search + Plus Button */}
                    <div className='flex items-center gap-4'>
                        {/* Search Input */}
                        <div className='flex-1 relative'>
                            <Search className='absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground' />
                            <Input type='search' placeholder='Search projects...' value={searchQuery} onChange={handleSearchChange} className='pl-10 pr-10' />
                            {searchQuery && (
                                <button onClick={clearSearch} className='absolute right-3 top-1/2 -translate-y-1/2'>
                                    <X className='h-4 w-4 text-muted-foreground hover:text-foreground' />
                                </button>
                            )}
                        </div>

                        {/* Plus Button */}
                        <Button size='icon' onClick={() => router.visit('/projects/create')}>
                            <Plus className='h-5 w-5' />
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <ScrollArea className='h-[400px]'>
                        {projects.length === 0 ? (
                            <div className='text-center py-8 text-muted-foreground'>
                                {searchQuery ? (
                                    <>
                                        <p>No projects found matching "{searchQuery}"</p>
                                        <Button variant='link' onClick={clearSearch} className='mt-2'>
                                            Clear search
                                        </Button>
                                    </>
                                ) : (
                                    <p>No projects yet. Click + to create one.</p>
                                )}
                            </div>
                        ) : (
                            <div className='space-y-3'>
                                {projects.map((project) => (
                                    <ProjectCard key={project.id} project={project} />
                                ))}
                            </div>
                        )}
                    </ScrollArea>
                </CardContent>
            </Card>
        </div>
    );
}
```

### Backend Controller Update

```php
// app/Http/Controllers/ProjectController.php

public function index(Request $request): Response
{
    $query = Project::query()
        ->withCount(['worktrees', 'tasks']);

    // Apply search filter if present
    if ($search = $request->query('search')) {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('path', 'like', "%{$search}%")
              ->orWhere('base_url', 'like', "%{$search}%");
        });
    }

    $projects = $query->latest()->get();

    return Inertia::render('projects/index', [
        'projects' => $projects,
        'search' => $search ?? '',
    ]);
}
```

### Alternative: Use Inertia useForm

For better integration with Inertia:

```tsx
import { router } from '@inertiajs/react';
import { useDebouncedCallback } from 'use-debounce';

export default function Index({ projects, search = '' }) {
    const [searchQuery, setSearchQuery] = useState(search);

    const debouncedSearch = useDebouncedCallback((value: string) => {
        router.get(
            '/projects',
            { search: value },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['projects'],
            },
        );
    }, 300);

    const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        setSearchQuery(value);
        debouncedSearch(value);
    };

    // ...
}
```

**Note:** May need to install `use-debounce` package:

```bash
pnpm add use-debounce
```

## Acceptance Criteria

- [ ] Search input displays in CardHeader of projects list
- [ ] Search input has search icon on the left side
- [ ] Search input has placeholder text "Search projects..."
- [ ] Plus button displays on the right side of CardHeader
- [ ] Plus button has tooltip "Create New Project"
- [ ] Typing in search input triggers backend search with 300ms debounce
- [ ] Backend filters projects by name, path, and base_url
- [ ] Search results update in real-time as user types
- [ ] Clear button (X) appears when search has text
- [ ] Clicking clear button resets search and shows all projects
- [ ] Empty state shows different message when searching vs no projects
- [ ] Clicking Plus button navigates to /projects/create
- [ ] Search preserves scroll position during updates
- [ ] Search query persists in URL query parameter
- [ ] Browser back/forward works with search history
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location:** `tests/Feature/Projects/ProjectSearchTest.php`

**Key test cases:**

- Test projects index returns all projects without search
- Test projects index filters by name
- Test projects index filters by path
- Test projects index filters by base_url
- Test projects index search is case-insensitive
- Test projects index search handles special characters
- Test projects index search returns empty array when no matches
- Test projects index search parameter is optional
- Test projects index returns search parameter in response

### Browser Tests

**Test file location:** `tests/Browser/Projects/ProjectSearchTest.php`

**Key test cases:**

- Test search input is visible on projects list
- Test Plus button is visible on projects list
- Test typing in search input filters projects
- Test search results update in real-time (debounced)
- Test clear button appears when search has text
- Test clicking clear button resets search
- Test empty state shows when search has no results
- Test empty state shows different message for no projects vs no results
- Test clicking Plus button navigates to create project page
- Test search query persists in URL
- Test browser back button restores previous search
- Test search works with keyboard navigation

## Code Formatting

Format all code using: **Prettier** and **oxfmt** (for JavaScript/TypeScript), **Pint** (for PHP)

Commands to run:

- `pnpm run format` - Format JavaScript/TypeScript files
- `vendor/bin/pint --dirty` - Format PHP files

## Additional Notes

### Search Performance Optimization

**Database Indexing:**
If the projects table grows large, add an index on the `name` column:

```php
// In a new migration
Schema::table('projects', function (Blueprint $table) {
    $table->index('name');
});
```

**Full-Text Search (Advanced):**
For better search performance with large datasets, consider MySQL full-text search:

```php
// In migration
Schema::table('projects', function (Blueprint $table) {
    $table->fullText(['name', 'path', 'base_url']);
});

// In controller
$query->whereFullText(['name', 'path', 'base_url'], $search);
```

### UX Enhancements

**Search Highlighting:**
Highlight matching text in search results:

```tsx
function highlightMatch(text: string, search: string) {
    if (!search) return text;
    const regex = new RegExp(`(${search})`, 'gi');
    return text.replace(regex, '<mark>$1</mark>');
}
```

**Keyboard Shortcuts:**
Add keyboard shortcut to focus search (e.g., Cmd/Ctrl + K):

```tsx
useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            searchInputRef.current?.focus();
        }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
}, []);
```

**Search Analytics:**
Track popular search queries for insights:

```php
// Log search queries
Log::channel('analytics')->info('Project search', [
    'query' => $search,
    'results_count' => $projects->count(),
]);
```

### Debounce Configuration

**Adjustable Debounce Delay:**
Make debounce delay configurable:

```tsx
const SEARCH_DEBOUNCE_MS = 300; // Can be adjusted per user preference
```

**Instant Search for Short Queries:**
Different debounce for different query lengths:

```tsx
const getDebounceDelay = (query: string) => {
    if (query.length <= 2) return 500; // Longer delay for short queries
    return 300; // Normal delay for longer queries
};
```

### Alternative UI: Command Palette

For a more advanced search experience, use Shadcn Command component:

```bash
pnpm dlx shadcn@latest add command dialog
```

```tsx
<Command>
    <CommandInput placeholder='Search projects...' />
    <CommandList>
        <CommandEmpty>No projects found.</CommandEmpty>
        <CommandGroup heading='Projects'>
            {projects.map((project) => (
                <CommandItem key={project.id} onSelect={() => router.visit(`/projects/${project.id}`)}>
                    {project.name}
                </CommandItem>
            ))}
        </CommandGroup>
    </CommandList>
</Command>
```

This provides a more polished search experience with keyboard navigation.

### Loading States

**Show Loading Indicator:**
Display loading state while search is in progress:

```tsx
const [isSearching, setIsSearching] = useState(false);

const debouncedSearch = useDebouncedCallback((value: string) => {
    setIsSearching(true);
    router.get(
        '/projects',
        { search: value },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['projects'],
            onFinish: () => setIsSearching(false),
        },
    );
}, 300);
```

Display loading spinner in search input:

```tsx
<div className='flex-1 relative'>
    <Search className='absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground' />
    <Input type='search' placeholder='Search projects...' value={searchQuery} onChange={handleSearchChange} className='pl-10 pr-10' />
    {isSearching ? (
        <Loader2 className='absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 animate-spin text-muted-foreground' />
    ) : searchQuery ? (
        <button onClick={clearSearch} className='absolute right-3 top-1/2 -translate-y-1/2'>
            <X className='h-4 w-4 text-muted-foreground hover:text-foreground' />
        </button>
    ) : null}
</div>
```

### Plus Button Variants

**Different Plus Button Styles:**

Option 1: Icon-only button (current design):

```tsx
<Button size='icon' variant='default'>
    <Plus className='h-5 w-5' />
</Button>
```

Option 2: Button with text (more explicit):

```tsx
<Button variant='default' className='gap-2'>
    <Plus className='h-4 w-4' />
    <span className='hidden sm:inline'>New Project</span>
</Button>
```

Option 3: Ghost button for subtle UI:

```tsx
<Button size='icon' variant='ghost'>
    <Plus className='h-5 w-5' />
</Button>
```

### Accessibility

**ARIA Labels:**

```tsx
<Input
  type="search"
  placeholder="Search projects..."
  value={searchQuery}
  onChange={handleSearchChange}
  aria-label="Search projects by name, path, or URL"
  className="pl-10 pr-10"
/>

<Button
  size="icon"
  onClick={() => router.visit('/projects/create')}
  aria-label="Create new project"
>
  <Plus className="h-5 w-5" />
</Button>
```

**Keyboard Navigation:**

- Tab to focus search input
- Type to search
- Esc to clear search
- Tab to Plus button
- Enter/Space to activate Plus button

### Dependencies

This feature depends on `projects-list-redesign` because:

- Uses the same centered card layout
- Modifies the CardHeader of the projects list card
- Builds on the existing projects list structure

**Implementation order:**

1. Complete `projects-list-redesign`
2. Then implement `project-search-and-quick-add`
