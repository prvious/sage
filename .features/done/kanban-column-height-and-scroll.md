---
name: kanban-column-height-and-scroll
description: Make kanban columns fill available height with independent scrolling
depends_on: null
---

## Feature Description

Update the kanban board layout so that each column (Queued, In Progress, Waiting Review, Done) takes up all available vertical height on the page, and each column can be scrolled independently without scrolling the entire page. This improves the user experience when working with many tasks by keeping column headers visible and allowing users to focus on one column at a time.

Users will be able to:

- See all 4 columns fill the entire viewport height
- Scroll within any individual column to see more tasks
- Keep the page header (project name, Add Task button) and column headers visible at all times
- Work with many tasks in one column without affecting the view of other columns

## Implementation Plan

### Frontend Components

**Update Dashboard Page Layout**:

Modify `resources/js/pages/projects/dashboard.tsx`:

- Change the main container to use flexbox layout that fills the viewport
- Calculate available height by subtracting header height from viewport height
- Pass the calculated height to the KanbanBoard component

```typescript
export default function Dashboard({ project, tasks }: DashboardProps) {
    const [isDialogOpen, setIsDialogOpen] = useState(false);

    return (
        <>
            <Head title={`${project.data.name} - Dashboard`} />
            <AppLayout>
                <div className='flex flex-col h-screen'>
                    {/* Fixed header */}
                    <div className='flex-shrink-0 p-6 pb-0'>
                        <div className='flex items-center justify-between'>
                            <div className='flex items-center gap-3'>
                                <h1 className='text-3xl font-bold'>{project.data.name}</h1>
                                <Badge variant='secondary'>Dashboard</Badge>
                            </div>
                            <Button onClick={() => setIsDialogOpen(true)}>
                                <Plus className='h-4 w-4 mr-2' />
                                Add Task
                            </Button>
                        </div>
                    </div>

                    {/* Scrollable kanban board */}
                    <div className='flex-1 overflow-hidden p-6 pt-6'>
                        <KanbanBoard
                            tasks={{
                                queued: tasks.queued.data,
                                in_progress: tasks.in_progress.data,
                                waiting_review: tasks.waiting_review.data,
                                done: tasks.done.data,
                            }}
                            projectId={project.data.id}
                        />
                    </div>
                    <QuickAddTaskDialog open={isDialogOpen} onOpenChange={setIsDialogOpen} projectId={project.data.id} />
                </div>
            </AppLayout>
        </>
    );
}
```

**Update KanbanBoard Component**:

Modify `resources/js/components/kanban/board.tsx`:

- Make the board container fill the parent height
- Ensure the grid layout fills the height

```typescript
export function KanbanBoard({ tasks, projectId }: KanbanBoardProps) {
    return (
        <div className='h-full'>
            <div className='grid grid-cols-1 gap-4 md:grid-cols-4 h-full'>
                {COLUMNS.map((column) => (
                    <KanbanColumn
                        key={column.id}
                        id={column.id}
                        title={column.title}
                        cards={tasks[column.id as keyof typeof tasks]}
                    />
                ))}
            </div>
        </div>
    );
}
```

**Update KanbanColumn Component**:

Modify `resources/js/components/kanban/column.tsx`:

- Make the column fill available height
- Add fixed header and scrollable content area
- Add proper overflow handling

```typescript
export function KanbanColumn({ title, cards }: KanbanColumnProps) {
    return (
        <div className='flex flex-col h-full rounded-lg border bg-muted/50'>
            {/* Fixed header */}
            <div className='flex-shrink-0 p-3 border-b border-border'>
                <h2 className='font-semibold text-sm'>
                    {title} ({cards.length})
                </h2>
            </div>

            {/* Scrollable content */}
            <div className='flex-1 overflow-y-auto p-3'>
                <div className='flex flex-col gap-2'>
                    {cards.length > 0 ? (
                        cards.map((task) => <KanbanCard key={task.id} task={task} />)
                    ) : (
                        <p className='text-xs text-muted-foreground text-center py-4'>No tasks</p>
                    )}
                </div>
            </div>
        </div>
    );
}
```

### Styling Considerations

**Tailwind Classes Used**:

- `h-screen` - Make container full viewport height
- `flex flex-col` - Vertical flexbox layout
- `flex-shrink-0` - Prevent header from shrinking
- `flex-1` - Take up remaining space
- `overflow-hidden` - Hide overflow on parent
- `overflow-y-auto` - Enable vertical scrolling on column content
- `h-full` - Fill parent height

**Mobile Responsiveness**:

- On mobile (single column view), each column should still be scrollable
- Consider reducing overall height or using a different layout strategy for mobile
- Headers should remain sticky on mobile as well

### Edge Cases

1. **Very few tasks**: Columns should still fill the height even with only 1-2 tasks
2. **Very many tasks**: Scrollbar should appear smoothly without layout shift
3. **Empty columns**: Empty state message should be vertically centered
4. **Resizing window**: Layout should adapt dynamically to window size changes
5. **Nested scrolling**: Ensure only the column content scrolls, not the entire page

## Acceptance Criteria

- [ ] Kanban board fills all available viewport height below the header
- [ ] Each column fills the full height of the board
- [ ] Column headers (title and count) remain fixed at the top of each column
- [ ] Column content area is independently scrollable
- [ ] Scrolling inside a column does not scroll the page or other columns
- [ ] Page header (project name, Add Task button) remains visible when scrolling columns
- [ ] Empty columns display centered empty state message
- [ ] Layout works on desktop (4 columns) and mobile (1 column stacked)
- [ ] Scrollbar appears only when column content exceeds available height
- [ ] No horizontal scrolling appears unintentionally
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Browser Tests

**Test file location**: `tests/Browser/Dashboard/KanbanColumnScrollTest.php`

**Key test cases**:

- Test columns fill available height on page load
- Test scrolling within a column does not scroll the page
- Test column headers remain visible when scrolling column content
- Test page header remains visible when scrolling columns
- Test scrolling one column does not affect other columns
- Test layout adapts to window resize
- Test empty columns display correctly
- Test columns with many tasks show scrollbar

**Example browser test**:

```php
test('scrolling within a column does not scroll the page', function () {
    $project = Project::factory()->create();

    // Create many tasks in one column
    Task::factory()->count(20)->create([
        'project_id' => $project->id,
        'status' => TaskStatus::Queued,
    ]);

    $page = visit(route('projects.dashboard', $project));

    // Get initial scroll position
    $initialPageScroll = $page->evaluate('window.scrollY');

    // Scroll inside the queued column
    $page->evaluate('document.querySelector("[data-column=queued]").scrollTop = 200');

    // Page scroll should not have changed
    $finalPageScroll = $page->evaluate('window.scrollY');

    expect($finalPageScroll)->toBe($initialPageScroll);
});

test('column headers remain visible when scrolling', function () {
    $project = Project::factory()->create();

    Task::factory()->count(20)->create([
        'project_id' => $project->id,
        'status' => TaskStatus::Queued,
    ]);

    $page = visit(route('projects.dashboard', $project));

    // Scroll the column content
    $page->evaluate('document.querySelector("[data-column=queued]").scrollTop = 500');

    // Header should still be visible
    $page->assertSee('Queued (20)');
});
```

### Visual Regression Testing (Optional)

Consider adding visual regression tests to ensure the layout remains consistent:

- Screenshot of kanban board with varying amounts of tasks
- Screenshot of scrolled state
- Screenshot on different viewport sizes

## Code Formatting

Format all code using Prettier via oxfmt:

**Command**: `pnpm run format`

## Additional Notes

### CSS Considerations

**Scrollbar Styling**:

- Consider using custom scrollbar styling for better aesthetics
- Tailwind v4 supports scrollbar styling via utilities
- Example: `scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100`

**Smooth Scrolling**:

- Add `scroll-smooth` class for better UX if desired
- Consider using `scroll-behavior: smooth` for programmatic scrolling

### Performance Considerations

**Virtualization**:

- For columns with 100+ tasks, consider implementing virtualization
- Libraries like `react-window` or `react-virtual` can help
- This is a future enhancement, not required for initial implementation

**Scroll Position Persistence**:

- Consider saving scroll position in component state
- Restore scroll position when returning to the page
- This is a nice-to-have, not required for initial implementation

### Accessibility Considerations

**Keyboard Navigation**:

- Ensure users can tab through tasks within a column
- Column should be focusable and scrollable via keyboard
- Add `tabindex="0"` to scrollable container if needed

**Screen Readers**:

- Ensure column headers are properly announced
- Use appropriate ARIA labels for scrollable regions
- Example: `aria-label="Queued tasks, scrollable region"`

### Browser Compatibility

**Tested Browsers**:

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)

**Known Issues**:

- Some browsers may show scrollbars differently
- Safari might need `-webkit-overflow-scrolling: touch` for smooth mobile scrolling

### Future Enhancements

After this feature is complete, potential improvements:

1. **Drag-and-drop between columns** - Update to maintain scroll position during drag
2. **Column width resizing** - Allow users to adjust column widths
3. **Column reordering** - Allow users to reorder columns
4. **Collapsed columns** - Allow users to collapse columns to save space
5. **Virtual scrolling** - For very large task lists (100+ tasks)
