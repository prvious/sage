---
name: kanban-real-data-integration
description: Connect kanban board to real Task data with 4 status columns and quick-add dialog
depends_on: null
---

## Feature Description

Replace the dummy data in the kanban board with real Task data from the database. Update the Task model to support 4 distinct status columns: "queued", "in_progress", "waiting_review", and "done". Add a quick-add button in the top right that opens a dialog for creating new tasks with a simple textarea description.

Users will be able to:

- View their project tasks organized in 4 status columns
- Quickly create new tasks via a dialog without leaving the dashboard
- See tasks automatically appear in the "queued" column after creation

This feature focuses on display and basic CRUD - drag-and-drop functionality will be added in a future enhancement.

## Implementation Plan

### Backend Components

**Update Task Model**:

Modify `app/Models/Task.php`:

- Update status values to use: `queued`, `in_progress`, `waiting_review`, `done`
- Add scope for filtering by project: `scopeForProject($query, $projectId)`
- Add scope for grouping by status: `scopeGroupedByStatus($query)`

**Migration**:

Create migration to update task status enum:

```php
Schema::table('tasks', function (Blueprint $table) {
    // Update status column to use new enum values
    $table->enum('status', ['queued', 'in_progress', 'waiting_review', 'done'])
        ->default('queued')
        ->change();
});
```

**Update DashboardController**:

Modify `app/Http/Controllers/DashboardController.php`:

- Load tasks for the project grouped by status
- Pass tasks to Inertia view

```php
public function show(Project $project)
{
    $tasks = Task::where('project_id', $project->id)
        ->orderBy('created_at', 'desc')
        ->get()
        ->groupBy('status');

    return Inertia::render('projects/dashboard', [
        'project' => $project,
        'tasks' => [
            'queued' => $tasks->get('queued', collect()),
            'in_progress' => $tasks->get('in_progress', collect()),
            'waiting_review' => $tasks->get('waiting_review', collect()),
            'done' => $tasks->get('done', collect()),
        ],
    ]);
}
```

**TaskController**:

Update `app/Http/Controllers/TaskController.php`:

- Modify `store()` method to create tasks with 'queued' status by default
- Ensure tasks belong to the correct project

```php
public function store(StoreTaskRequest $request)
{
    $task = Task::create([
        'project_id' => $request->validated('project_id'),
        'title' => $request->validated('title'),
        'description' => $request->validated('description'),
        'status' => 'queued',
    ]);

    return redirect()
        ->back()
        ->with('success', 'Task created successfully');
}
```

**Form Request**:

Create `app/Http/Requests/StoreTaskRequest.php`:

```php
public function rules(): array
{
    return [
        'project_id' => 'required|exists:projects,id',
        'title' => 'required|string|max:255',
        'description' => 'nullable|string|max:5000',
    ];
}
```

### Frontend Components

**Update Dashboard Page**:

Modify `resources/js/pages/projects/dashboard.tsx`:

- Accept tasks prop from backend
- Pass tasks to KanbanBoard component
- Add quick-add dialog state management

```typescript
interface DashboardProps {
    project: Project;
    tasks: {
        queued: Task[];
        in_progress: Task[];
        waiting_review: Task[];
        done: Task[];
    };
}

export default function Dashboard({ project, tasks }: DashboardProps) {
    const [isDialogOpen, setIsDialogOpen] = useState(false);

    return (
        <>
            <Head title={`${project.name} - Dashboard`} />
            <AppLayout>
                <div className='p-6 space-y-6'>
                    <div className='flex items-center justify-between'>
                        <div className='flex items-center gap-3'>
                            <h1 className='text-3xl font-bold'>{project.name}</h1>
                            <Badge variant='secondary'>Dashboard</Badge>
                        </div>
                        <Button onClick={() => setIsDialogOpen(true)}>
                            <Plus className='h-4 w-4 mr-2' />
                            Add Task
                        </Button>
                    </div>
                    <KanbanBoard tasks={tasks} projectId={project.id} />
                    <QuickAddTaskDialog
                        open={isDialogOpen}
                        onOpenChange={setIsDialogOpen}
                        projectId={project.id}
                    />
                </div>
            </AppLayout>
        </>
    );
}
```

**Update KanbanBoard Component**:

Modify `resources/js/components/kanban/board.tsx`:

- Remove dummy data
- Accept tasks prop
- Map tasks to 4 columns: queued, in_progress, waiting_review, done
- Update column titles

```typescript
interface KanbanBoardProps {
    tasks: {
        queued: Task[];
        in_progress: Task[];
        waiting_review: Task[];
        done: Task[];
    };
    projectId: number;
}

const COLUMNS = [
    { id: 'queued', title: 'Queued' },
    { id: 'in_progress', title: 'In Progress' },
    { id: 'waiting_review', title: 'Waiting Review' },
    { id: 'done', title: 'Done' },
];

export function KanbanBoard({ tasks, projectId }: KanbanBoardProps) {
    return (
        <div className='h-full'>
            <div className='grid grid-cols-1 gap-4 md:grid-cols-4'>
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

**Create QuickAddTaskDialog Component**:

Create `resources/js/components/kanban/quick-add-dialog.tsx`:

```typescript
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';

interface QuickAddTaskDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    projectId: number;
}

export function QuickAddTaskDialog({ open, onOpenChange, projectId }: QuickAddTaskDialogProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        project_id: projectId,
        title: '',
        description: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/tasks', {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Add New Task</DialogTitle>
                        <DialogDescription>
                            Describe the task you want to create. You can refine details later.
                        </DialogDescription>
                    </DialogHeader>
                    <div className='py-4 space-y-4'>
                        <div className='space-y-2'>
                            <Label htmlFor='description'>Task Description</Label>
                            <Textarea
                                id='description'
                                placeholder='e.g., Add user authentication with email verification...'
                                value={data.description}
                                onChange={(e) => {
                                    setData('description', e.target.value);
                                    // Auto-generate title from first line if title is empty
                                    if (!data.title && e.target.value) {
                                        const firstLine = e.target.value.split('\n')[0];
                                        setData('title', firstLine.substring(0, 100));
                                    }
                                }}
                                rows={5}
                                className={errors.description ? 'border-destructive' : ''}
                            />
                            {errors.description && (
                                <p className='text-sm text-destructive'>{errors.description}</p>
                            )}
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            type='button'
                            variant='outline'
                            onClick={() => onOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button type='submit' disabled={processing || !data.description}>
                            {processing ? 'Creating...' : 'Create Task'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
```

**Update Task Card Component**:

Update `resources/js/components/kanban/card.tsx` to display real task data:

- Show task title
- Show task description (truncated)
- Show created date
- Add click handler to view/edit task (future)

**TypeScript Types**:

Add to `resources/js/types/index.d.ts`:

```typescript
export interface Task {
    id: number;
    project_id: number;
    worktree_id: number | null;
    title: string;
    description: string | null;
    status: 'queued' | 'in_progress' | 'waiting_review' | 'done';
    agent_type: string | null;
    model: string | null;
    agent_output: string | null;
    started_at: string | null;
    completed_at: string | null;
    created_at: string;
    updated_at: string;
}
```

### Styling

**Shadcn Components**:

- Use `Dialog` for quick-add task modal
- Use `Textarea` for task description
- Use `Button` for add task and form actions
- Use `Label` for form fields
- Use `Badge` for task metadata (future)

**Column Styling**:

- 4 equal-width columns on desktop
- Stack vertically on mobile
- Each column has a header with title and count
- Cards display in chronological order (newest first)

## Acceptance Criteria

- [ ] Task model status updated to use 4 values: queued, in_progress, waiting_review, done
- [ ] Migration successfully updates existing task statuses
- [ ] Dashboard loads tasks grouped by status for the current project
- [ ] Kanban board displays 4 columns: Queued, In Progress, Waiting Review, Done
- [ ] Tasks appear in correct columns based on their status
- [ ] "Add Task" button appears in top right of dashboard
- [ ] Clicking "Add Task" opens a dialog
- [ ] Dialog contains a textarea for task description
- [ ] Submitting dialog creates new task with status "queued"
- [ ] Title is auto-generated from first line of description
- [ ] New task immediately appears in Queued column (via Inertia reload)
- [ ] Dialog closes after successful creation
- [ ] Form validation prevents empty tasks
- [ ] Tasks display title, description (truncated), and metadata
- [ ] No console errors or warnings
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location**: `tests/Feature/Dashboard/KanbanDataTest.php`

**Key test cases**:

- Test dashboard loads tasks for specific project only
- Test tasks are grouped by status correctly
- Test creating task sets default status to 'queued'
- Test creating task requires description
- Test title is generated from description
- Test task belongs to correct project

### Unit Tests

**Test file location**: `tests/Unit/Models/TaskTest.php`

**Key test cases**:

- Test task status enum values are valid
- Test scopeForProject returns only project tasks
- Test task belongs to project relationship
- Test default status is 'queued'

### Browser Tests

**Test file location**: `tests/Browser/Dashboard/KanbanBoardTest.php`

**Key test cases**:

- Test dashboard displays all 4 columns
- Test tasks appear in correct status columns
- Test clicking "Add Task" opens dialog
- Test creating task via dialog
- Test new task appears in Queued column
- Test dialog closes after creation
- Test validation error shows for empty description
- Test tasks display correct data (title, description)

## Code Formatting

Format all code using:

- **Backend (PHP)**: Laravel Pint
    - Command: `vendor/bin/pint --dirty`
- **Frontend (TypeScript/React)**: Prettier (via oxfmt)
    - Command: `pnpm run format`

## Additional Notes

### Migration Strategy

For existing tasks with old status values, create a data migration:

```php
DB::table('tasks')->where('status', 'todo')->update(['status' => 'queued']);
DB::table('tasks')->where('status', 'pending')->update(['status' => 'queued']);
DB::table('tasks')->where('status', 'completed')->update(['status' => 'done']);
// Map any other legacy statuses
```

### Task Title Generation

Auto-generate task title from description:

- Use first line of description
- Truncate to 100 characters
- Trim whitespace
- If first line is empty, use "Untitled Task"

### Empty States

Display empty state in columns with no tasks:

- "No tasks in queue" for Queued
- "No tasks in progress" for In Progress
- etc.

### Task Count Badges

Show task count in column headers:

```
Queued (3)
In Progress (1)
Waiting Review (2)
Done (12)
```

### Future Enhancements

This feature sets the foundation for:

1. **Drag-and-drop status updates** (separate feature)
2. **Task detail modal** - Click card to view/edit full task
3. **Inline editing** - Edit title/description directly in card
4. **Filtering/search** - Filter tasks by agent, date, etc.
5. **Task assignment** - Assign tasks to worktrees
6. **Bulk actions** - Move multiple tasks at once

### Performance Considerations

For projects with many tasks:

- Consider pagination or infinite scroll
- Limit initial load to recent tasks
- Add "Load more" button for older tasks
- Cache task counts
