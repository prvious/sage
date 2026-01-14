---
name: kanban-dashboard
description: Drag-and-drop Kanban board for managing tasks across stages
depends_on: agent-orchestration
---

## Detailed Description

This feature provides a visual Kanban board where developers can manage tasks through different stages: Idea → In Progress → Review → Done. Tasks can be dragged between columns, and each task card shows relevant information like assigned worktree, agent status, and more.

### Key Capabilities
- Four-column Kanban board: Idea, In Progress, Review, Done
- Drag-and-drop tasks between columns
- Create new tasks quickly from any column
- Task cards show: title, description, worktree, agent status, timestamp
- Filter tasks by worktree, agent type, or search term
- Quick actions on cards: view details, start agent, delete
- Real-time updates when tasks change (via WebSocket)
- Responsive design for different screen sizes

### User Stories
1. As a developer, I want to see all my tasks organized by stage
2. As a developer, I want to drag tasks to update their status
3. As a developer, I want to create new task ideas quickly
4. As a developer, I want to see which tasks are actively being worked on by agents
5. As a developer, I want to filter tasks by worktree

## Detailed Implementation Plan

### Step 1: Install shadcn/ui Components

Check existing components and install what's needed:
```bash
npx shadcn@latest add card button badge input dialog
```

### Step 2: Create Kanban Page Component
```typescript
// resources/js/Pages/Kanban/Index.tsx
```

**Layout:**
- 4 columns (Idea, In Progress, Review, Done)
- Each column has header with count and "Add Task" button
- Scrollable task list in each column
- Empty state when no tasks

### Step 3: Implement Drag-and-Drop

Use `@dnd-kit/core` library:
```bash
npm install @dnd-kit/core @dnd-kit/sortable
```

**Implementation:**
- Make task cards draggable
- Make columns droppable
- Handle drop event to update task status
- Optimistic UI updates (update UI immediately, sync with server)
- Revert on error

### Step 4: Create Task Card Component
```typescript
// resources/js/Components/TaskCard.tsx
```

**Display:**
- Task title (truncated if long)
- Description preview (2 lines max)
- Worktree badge (if assigned)
- Agent status indicator (running, completed, failed)
- Timestamp (created or last updated)
- Quick action buttons (view, start, delete)

**Styling:**
- Use shadcn/ui Card component
- Badge for status
- Hover effects
- Smooth animations

### Step 5: Create New Task Dialog
```typescript
// resources/js/Components/CreateTaskDialog.tsx
```

**Form Fields:**
- Title (required)
- Description (textarea)
- Initial status (default to "idea")
- Assign to worktree (optional, select dropdown)
- Agent type (optional, select: claude, opencode)
- Model (optional, select based on agent type)

Use Inertia's `useForm` helper for form handling.

### Step 6: Create Task Controller Methods

Update `TaskController`:
```bash
php artisan make:controller TaskController --no-interaction
```

**Methods:**
- `index()` - Return Kanban view with all tasks grouped by status
- `store()` - Create new task
- `update()` - Update task (including status change from drag-drop)
- `destroy()` - Delete task

### Step 7: Create Form Request Validation
```bash
php artisan make:request StoreTaskRequest --no-interaction
php artisan make:request UpdateTaskRequest --no-interaction
```

**Validation Rules:**
- `title` - required, string, max:255
- `description` - nullable, string
- `status` - required, in:idea,in_progress,review,done,failed
- `worktree_id` - nullable, exists:worktrees,id
- `agent_type` - nullable, in:claude,opencode
- `model` - nullable, string

### Step 8: Create API Resource for Tasks
```bash
php artisan make:resource TaskResource --no-interaction
```

Transform task data for frontend:
```php
return [
    'id' => $this->id,
    'title' => $this->title,
    'description' => $this->description,
    'status' => $this->status,
    'agent_type' => $this->agent_type,
    'model' => $this->model,
    'worktree' => new WorktreeResource($this->whenLoaded('worktree')),
    'created_at' => $this->created_at,
    'updated_at' => $this->updated_at,
    'is_running' => $this->isRunning(), // Add method to Task model
];
```

### Step 9: Add Task Status Tracking

Add to `Task` model:
```php
public function isRunning(): bool
{
    return $this->status === 'in_progress' &&
           $this->started_at !== null &&
           $this->completed_at === null;
}

public function scopeByStatus(Builder $query, string $status): Builder
{
    return $query->where('status', $status);
}
```

### Step 10: Implement Real-time Updates

Create event:
```bash
php artisan make:event TaskUpdated --no-interaction
```

Broadcast task changes:
```php
broadcast(new TaskUpdated($task));
```

Listen on frontend:
```typescript
Echo.channel('kanban')
    .listen('TaskUpdated', (e) => {
        updateTaskInKanban(e.task)
    })
```

### Step 11: Add Filtering and Search

Create filter bar component:
```typescript
// resources/js/Components/KanbanFilters.tsx
```

**Filters:**
- Search by title/description
- Filter by worktree
- Filter by agent type
- Filter by date range

Apply filters on frontend with reactive state.

### Step 12: Create Empty State Components

For each column when empty:
```typescript
// resources/js/Components/EmptyKanbanColumn.tsx
```

Show helpful message and "Add Task" CTA.

### Step 13: Add Keyboard Shortcuts

Implement shortcuts:
- `N` - Create new task
- `Escape` - Close dialogs
- `/` - Focus search

### Step 14: Responsive Design

Ensure Kanban works on mobile:
- Stack columns vertically on small screens
- Swipe to change column on mobile
- Bottom sheet for task creation on mobile

### Step 15: Create Feature Tests

Test coverage:
- `it('displays tasks grouped by status')`
- `it('can create new task from kanban')`
- `it('can update task status via API')`
- `it('can delete task from kanban')`
- `it('filters tasks correctly')`
- `it('searches tasks by title')`

### Step 16: Create Browser Tests

E2E test coverage:
- `it('can drag task between columns')`
- `it('can create task using dialog')`
- `it('displays real-time updates when task changes')`
- `it('can filter tasks by worktree')`
- `it('can search tasks')`

### Step 17: Add Animations

Use Tailwind CSS transitions:
- Fade in/out for task cards
- Smooth drag animations
- Column highlight on drag over

### Step 18: Format Code
```bash
vendor/bin/pint --dirty
npm run format # If you have prettier
```
