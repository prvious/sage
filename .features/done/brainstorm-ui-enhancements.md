---
name: brainstorm-ui-enhancements
description: Polish brainstorm UI with filtering, sorting, actions, and export functionality
depends_on: brainstorm-realtime-notifications
---

## Feature Description

Enhance the brainstorming UI with advanced features like filtering by category/priority, sorting, idea actions (create spec, add to tasks), export functionality, and improved card designs. This is the final polish layer on top of the core brainstorming functionality.

## Implementation Plan

### Backend Components

**Update Controller**:

Add methods to `BrainstormController`:

- `export(Project $project, Brainstorm $brainstorm)` - Export ideas to markdown
- `createSpec(Request $request, Brainstorm $brainstorm, $ideaIndex)` - Create spec from idea

**Actions**:

- Create `app/Actions/Brainstorm/ExportIdeas.php`
    - Generate markdown file with all ideas
    - Include metadata (project name, date, etc.)
    - Return download response

- Create `app/Actions/Brainstorm/CreateSpecFromIdea.php`
    - Extract idea data
    - Create new Spec record
    - Pre-fill title and description
    - Return redirect to spec edit page

**Routes**:

```php
Route::prefix('projects/{project}/brainstorm')->group(function () {
    Route::get('/{brainstorm}/export', [BrainstormController::class, 'export'])->name('projects.brainstorm.export');
    Route::post('/{brainstorm}/ideas/{ideaIndex}/create-spec', [BrainstormController::class, 'createSpec'])->name('projects.brainstorm.create-spec');
});
```

### Frontend Components

**Enhanced Components**:

- Update `resources/js/components/brainstorm/ideas-list.tsx`:
    - Add filter controls (category, priority)
    - Add sort controls (title, priority, category)
    - Add search functionality
    - Pagination if needed

- Update `resources/js/components/brainstorm/idea-card.tsx`:
    - Improved visual design
    - Action buttons:
        - Create Spec
        - Copy to Clipboard
        - Expand/Collapse (for long descriptions)
    - Better priority badge styling
    - Category icon/badge

- Create `resources/js/components/brainstorm/idea-filters.tsx`:
    - Filter by category (All, Features, Enhancements, Infrastructure, Tooling)
    - Filter by priority (All, High, Medium, Low)
    - Search by keyword
    - Clear filters button

- Create `resources/js/components/brainstorm/idea-actions.tsx`:
    - Export all ideas button
    - Bulk actions (future)
    - Stats summary (X ideas, Y high priority, etc.)

**UI Features**:

1. **Filtering**:
    - Tabs for category filter
    - Dropdown for priority filter
    - Search input for keyword search
    - Active filter count badge

2. **Sorting**:
    - Sort by: Title (A-Z), Priority (High to Low), Category
    - Toggle ascending/descending

3. **Idea Actions**:
    - "Create Spec" - Creates new spec with idea title/description
    - "Copy" - Copy idea to clipboard
    - "Expand" - Show full description if truncated

4. **Export**:
    - Export to Markdown
    - Include project name, date, all ideas
    - Download as file

5. **Stats Display**:
    - Total ideas count
    - Breakdown by priority (X high, Y medium, Z low)
    - Breakdown by category

### Styling

**Shadcn Components**:

- Use `Tabs` for category filtering
- Use `Select` for priority filter and sorting
- Use `Input` for search
- Use `DropdownMenu` for idea card actions
- Use `Dialog` for expanded idea view
- Use `Progress` for stats visualization (optional)

**Card Design Improvements**:

```typescript
<Card className="hover:shadow-md transition-shadow">
    <CardHeader className="pb-3">
        <div className="flex items-start justify-between">
            <CardTitle className="text-lg">{idea.title}</CardTitle>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="sm">
                        <MoreVertical className="h-4 w-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent>
                    <DropdownMenuItem onClick={() => createSpec(idea)}>
                        Create Spec
                    </DropdownMenuItem>
                    <DropdownMenuItem onClick={() => copyToClipboard(idea)}>
                        Copy to Clipboard
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
        <div className="flex gap-2 mt-2">
            <Badge variant={getPriorityVariant(idea.priority)}>
                {idea.priority}
            </Badge>
            <Badge variant="outline">{idea.category}</Badge>
        </div>
    </CardHeader>
    <CardContent>
        <p className="text-sm text-muted-foreground line-clamp-3">
            {idea.description}
        </p>
    </CardContent>
</Card>
```

**Priority Badge Colors**:

- High: `destructive` (red)
- Medium: `warning` (yellow/orange)
- Low: `secondary` (gray)

## Acceptance Criteria

- [ ] Ideas can be filtered by category
- [ ] Ideas can be filtered by priority
- [ ] Ideas can be searched by keyword
- [ ] Filter state persists in URL query params
- [ ] Ideas can be sorted by title, priority, category
- [ ] Sort order can be toggled (asc/desc)
- [ ] Idea cards have improved visual design
- [ ] "Create Spec" action creates new spec with idea data
- [ ] "Copy" action copies idea to clipboard
- [ ] Export button downloads markdown file
- [ ] Markdown export includes all ideas and metadata
- [ ] Stats summary displays correctly
- [ ] Active filters show count badge
- [ ] Clear filters button works
- [ ] Long descriptions are truncated with expand option
- [ ] No console errors or warnings
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location**: `tests/Feature/Brainstorm/IdeaActionsTest.php`

**Key test cases**:

- Test export endpoint returns markdown file
- Test export includes all ideas
- Test export includes metadata
- Test create spec from idea creates new spec
- Test create spec pre-fills title and description
- Test create spec redirects to edit page

### Browser Tests

**Test file location**: `tests/Browser/Brainstorm/IdeaEnhancementsTest.php`

**Key test cases**:

- Test filtering ideas by category
- Test filtering ideas by priority
- Test searching ideas by keyword
- Test sorting ideas by title
- Test sorting ideas by priority
- Test clearing filters
- Test create spec button works
- Test copy button works
- Test export button downloads file
- Test stats display correctly
- Test expand/collapse long descriptions

## Code Formatting

Format all code using:

- **Backend (PHP)**: Laravel Pint
    - Command: `vendor/bin/pint --dirty`
- **Frontend (TypeScript/React)**: Prettier
    - Command: `pnpm run format`

## Additional Notes

### Export Markdown Format

Example export output:

```markdown
# Brainstorm Ideas - Project Name

**Date**: 2026-01-17
**Total Ideas**: 12
**High Priority**: 4
**Medium Priority**: 5
**Low Priority**: 3

---

## Features

### API Rate Limiting System (High Priority)

Implement comprehensive rate limiting for all API endpoints using Laravel's built-in throttle middleware. Include configurable limits per user role and endpoint.

### Real-time Collaboration Features (Medium Priority)

Add presence indicators and collaborative editing using Laravel Reverb. Allow multiple users to work on specs and tasks simultaneously.

---

## Infrastructure

### Enhanced Error Tracking (Medium Priority)

Integrate error tracking service to monitor exceptions across worktrees. Provide dashboard for error trends and alerts.

...
```

### Create Spec Integration

When creating a spec from an idea:

```php
public function createSpec(Request $request, Brainstorm $brainstorm, int $ideaIndex)
{
    $ideas = $brainstorm->ideas;

    if (!isset($ideas[$ideaIndex])) {
        abort(404);
    }

    $idea = $ideas[$ideaIndex];

    $spec = Spec::create([
        'project_id' => $brainstorm->project_id,
        'title' => $idea['title'],
        'description' => $idea['description'],
        'status' => 'draft',
    ]);

    return redirect()
        ->route('projects.specs.edit', [$brainstorm->project, $spec])
        ->with('success', 'Spec created from idea!');
}
```

### URL Query Parameters

Persist filter state in URL:

```typescript
const searchParams = new URLSearchParams(window.location.search);
const category = searchParams.get('category') || 'all';
const priority = searchParams.get('priority') || 'all';
const search = searchParams.get('search') || '';

// Update URL when filters change
const updateFilters = (newFilters) => {
    const params = new URLSearchParams();
    if (newFilters.category !== 'all') params.set('category', newFilters.category);
    if (newFilters.priority !== 'all') params.set('priority', newFilters.priority);
    if (newFilters.search) params.set('search', newFilters.search);

    router.visit(`${window.location.pathname}?${params.toString()}`, {
        preserveScroll: true,
        preserveState: true,
    });
};
```

### Copy to Clipboard

```typescript
const copyToClipboard = (idea: Idea) => {
    const text = `# ${idea.title}\n\n${idea.description}\n\nPriority: ${idea.priority}\nCategory: ${idea.category}`;

    navigator.clipboard.writeText(text).then(() => {
        toast.success('Idea copied to clipboard!');
    });
};
```

### Stats Calculation

```typescript
const stats = useMemo(() => {
    const total = ideas.length;
    const high = ideas.filter((i) => i.priority === 'high').length;
    const medium = ideas.filter((i) => i.priority === 'medium').length;
    const low = ideas.filter((i) => i.priority === 'low').length;

    const byCategory = ideas.reduce(
        (acc, idea) => {
            acc[idea.category] = (acc[idea.category] || 0) + 1;
            return acc;
        },
        {} as Record<string, number>,
    );

    return { total, high, medium, low, byCategory };
}, [ideas]);
```

### Future Enhancements

After this feature, potential future additions:

- **Collaborative Features**:
    - Multiple users can vote on ideas
    - Comments on ideas
    - Assign ideas to team members

- **AI Refinement**:
    - "Refine this idea" button for more details
    - Generate implementation plan
    - Estimate complexity/effort

- **Templates**:
    - Save idea templates
    - Industry-specific suggestions
    - Framework-specific patterns

- **Analytics**:
    - Track which ideas get implemented
    - Show implementation success rate
    - Idea popularity trends
