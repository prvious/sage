---
name: brainstorm-core
description: Foundation for brainstorming feature - models, basic controller, and simple form
depends_on: project-context-sidebar
---

## Feature Description

Create the foundational components for the brainstorming feature. This includes the database model, basic controller, and a simple form where users can submit brainstorm requests. Ideas will be stored but not generated yet (that comes in the next feature).

This provides the structure and UI for brainstorming without AI generation or real-time updates.

## Implementation Plan

### Backend Components

**Model**:

- Create `app/Models/Brainstorm.php`
    - Fields:
        - `id` (primary key)
        - `project_id` (foreign key)
        - `user_id` (foreign key, nullable)
        - `user_context` (text, nullable) - User-provided context
        - `ideas` (json, nullable) - Array of generated ideas
        - `status` (enum: 'pending', 'processing', 'completed', 'failed')
        - `error_message` (text, nullable)
        - `completed_at` (timestamp, nullable)
        - `created_at`, `updated_at`
    - Relationships:
        - `belongsTo(Project::class)`
        - `belongsTo(User::class)`
    - Casts:
        - `ideas` => 'array'
    - Scopes:
        - `scopeForProject($query, $projectId)`
        - `scopeCompleted($query)`

**Migration**:

```php
Schema::create('brainstorms', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->text('user_context')->nullable();
    $table->json('ideas')->nullable();
    $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
    $table->text('error_message')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();

    $table->index(['project_id', 'status', 'created_at']);
});
```

**Controller**:

- Create `app/Http/Controllers/BrainstormController.php`
- Methods:
    - `index(Project $project)` - Display brainstorm page with previous sessions
    - `store(Request $request, Project $project)` - Create brainstorm record (no job yet)
    - `show(Project $project, Brainstorm $brainstorm)` - Display specific brainstorm session

**Form Request**:

- Create `app/Http/Requests/StoreBrainstormRequest.php`
    - Validate `user_context` (optional, string, max 5000 chars)

**Routes**:

```php
Route::prefix('projects/{project}')->group(function () {
    Route::get('/brainstorm', [BrainstormController::class, 'index'])->name('projects.brainstorm.index');
    Route::post('/brainstorm', [BrainstormController::class, 'store'])->name('projects.brainstorm.store');
    Route::get('/brainstorm/{brainstorm}', [BrainstormController::class, 'show'])->name('projects.brainstorm.show');
});
```

### Frontend Components

**Pages**:

- Create `resources/js/pages/projects/brainstorm.tsx` - Main brainstorm page

**Components**:

- Create `resources/js/components/brainstorm/context-input-form.tsx` - Form with textarea
- Create `resources/js/components/brainstorm/brainstorm-list.tsx` - List of previous sessions
- Create `resources/js/components/brainstorm/brainstorm-card.tsx` - Individual brainstorm session card

**UI/UX Flow**:

1. **Initial State**:
    - Show textarea for optional context
    - Show "Create Brainstorm" button
    - Display previous brainstorm sessions (if any)

2. **Submitting**:
    - Submit form via Inertia
    - Create brainstorm record with status "pending"
    - Redirect back to index with success message

3. **Display**:
    - Show all brainstorms for this project
    - Display status badge (pending, processing, completed, failed)
    - Click to view details

### Styling

**Shadcn Components**:

- Use `Textarea` for context input
- Use `Button` for form submit
- Use `Card` for brainstorm cards
- Use `Badge` for status
- Use `Skeleton` for loading states
- Use `Alert` for messages

## Acceptance Criteria

- [ ] Brainstorm page displays at `/projects/{project}/brainstorm`
- [ ] Textarea allows users to input optional context (max 5000 chars)
- [ ] "Create Brainstorm" button submits form
- [ ] Form submission creates Brainstorm record with status "pending"
- [ ] Previous brainstorm sessions display in list
- [ ] Users can click on session to view details
- [ ] Status badge shows current state (pending, completed, failed)
- [ ] Validation prevents context over 5000 chars
- [ ] No console errors or warnings
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location**: `tests/Feature/Brainstorm/BrainstormControllerTest.php`

**Key test cases**:

- Test brainstorm index page renders
- Test submitting brainstorm creates record
- Test brainstorm record is created with correct status
- Test user context is saved to database
- Test validation prevents context over 5000 chars
- Test show page displays brainstorm details
- Test only project brainstorms are displayed
- Test brainstorms ordered by created_at desc

### Browser Tests

**Test file location**: `tests/Browser/Brainstorm/BrainstormPageTest.php`

**Key test cases**:

- Test navigating to brainstorm page
- Test entering context in textarea
- Test submitting form creates brainstorm
- Test previous sessions display
- Test clicking session navigates to show page
- Test status badge displays correctly

## Code Formatting

Format all code using:

- **Backend (PHP)**: Laravel Pint
    - Command: `vendor/bin/pint --dirty`
- **Frontend (TypeScript/React)**: Prettier
    - Command: `pnpm run format`

## Additional Notes

### Sidebar Integration

Update `resources/js/components/layout/app-sidebar.tsx` to include Brainstorm link:

```typescript
{
    label: 'Brainstorm',
    icon: Lightbulb, // or Sparkles
    href: BrainstormController.index(selectedProject.id),
},
```

### Mock Data for Testing

For now, you can manually create brainstorms with mock ideas data to test the UI:

```php
Brainstorm::create([
    'project_id' => $project->id,
    'status' => 'completed',
    'ideas' => [
        [
            'title' => 'API Rate Limiting',
            'description' => 'Add rate limiting to all API endpoints',
            'priority' => 'high',
            'category' => 'feature',
        ],
    ],
]);
```

### Next Steps

After this feature is complete, the next feature (`brainstorm-ai-generation`) will:

- Add the queue job to generate ideas
- Integrate with AI agent
- Update status to "completed" when done
