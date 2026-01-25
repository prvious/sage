---
name: add-feature-workflow-button
description: Replace 'Add Task' button with 'Add Feature' workflow that generates spec from description and creates tasks
depends_on: null
---

## Feature Description

Replace the existing "Add Task" button on the kanban dashboard with an "Add Feature" button that implements a complete AI-powered workflow:

1. User clicks "Add Feature" button
2. Dialog opens with textarea for feature description
3. User types a feature description and submits
4. AI generates a detailed specification from the description
5. System creates tasks automatically from the generated spec
6. Tasks appear on the kanban board

This workflow leverages the existing `Spec` model, `SpecGeneratorService`, and the relationship between specs and tasks to provide a streamlined feature development experience.

## Current State

The kanban dashboard (`resources/js/pages/projects/dashboard.tsx`) currently has:

- "Add Task" button (line 45-48) that opens `QuickAddTaskDialog`
- `QuickAddTaskDialog` component for quick task creation

The application already has:

- `Spec` model with `project_id`, `title`, `content`, `generated_from_idea`
- `Task` model with `spec_id` foreign key relationship
- `SpecGeneratorService` with `generate(string $idea, string $type)` method
- Anthropic API integration for AI-powered spec generation

## Implementation Plan

### Backend Components

**Controllers**:

- Create `App\Http\Controllers\FeatureController` with:
    - `store(Request $request)` - Store a new feature workflow

**Actions** (following .ai/actions guidelines):

- Create `App\Actions\Feature\GenerateSpecFromDescription` - Generate spec from user description
    - Use `SpecGeneratorService` to generate spec content
    - Create `Spec` model with generated content
    - Return the created spec

- Create `App\Actions\Feature\CreateTasksFromSpec` - Parse spec and create tasks
    - Parse the generated spec markdown content
    - Extract task list from spec
    - Create `Task` models for each identified task
    - Link tasks to the spec via `spec_id`
    - Return array of created tasks

**Form Requests**:

- Create `App\Http\Requests\StoreFeatureRequest` with validation:
    - `project_id` - required, exists in projects table
    - `description` - required, string, min:10, max:2000

**Routes**:

- Add to `routes/web.php`:
    ```php
    Route::post('/projects/{project}/features', [FeatureController::class, 'store'])
        ->name('projects.features.store');
    ```

**Database changes**:

- No migrations needed - existing `specs` and `tasks` tables already support this workflow
- `specs` table has `generated_from_idea` boolean column to track AI-generated specs
- `tasks` table has `spec_id` foreign key for linking to specs

### Frontend Components

**New Components**:

- Create `resources/js/components/feature/add-feature-dialog.tsx`
    - Dialog with textarea for feature description
    - Character counter (10-2000 chars)
    - Submit button with loading state
    - Error handling and display
    - Success feedback with task count
    - Uses Inertia Form component for submission

**Modified Components**:

- Update `resources/js/pages/projects/dashboard.tsx`:
    - Replace "Add Task" button text with "Add Feature"
    - Replace `QuickAddTaskDialog` with new `AddFeatureDialog`
    - Update state management for new dialog

**Routing**:

- Use Wayfinder for type-safe route generation:
    ```tsx
    import { store } from '@/actions/App/Http/Controllers/FeatureController';
    ```

**Styling**:

- Use existing Shadcn components:
    - `Dialog` for modal
    - `Form` for Inertia form submission
    - `Textarea` for description input
    - `Button` for submit action
    - `Label` for form labels
- Follow Tailwind CSS conventions from existing components
- Ensure dark mode support with `dark:` classes

### Backend Workflow

1. **FeatureController@store**:
    - Validate request using `StoreFeatureRequest`
    - Call `GenerateSpecFromDescription` action
    - Call `CreateTasksFromSpec` action
    - Reload page data to show new tasks on kanban
    - Return success response

2. **GenerateSpecFromDescription Action**:
    - Use `SpecGeneratorService->generate($description, 'feature')`
    - Create `Spec` model:
        ```php
        Spec::create([
            'project_id' => $projectId,
            'title' => $extractedTitle, // Extract from first # heading in spec
            'content' => $generatedSpec,
            'generated_from_idea' => true,
        ]);
        ```

3. **CreateTasksFromSpec Action**:
    - Parse spec markdown content
    - Look for task lists (- [ ] items) or numbered steps
    - For each task, create `Task` model:
        ```php
        Task::create([
            'project_id' => $projectId,
            'spec_id' => $spec->id,
            'title' => $taskTitle,
            'description' => $taskDescription,
            'status' => TaskStatus::Queued,
        ]);
        ```
    - Return created tasks

### Frontend Workflow

1. User clicks "Add Feature" button
2. `AddFeatureDialog` opens
3. User types description (validated 10-2000 chars)
4. User submits form
5. Inertia Form submits POST to `/projects/{project}/features`
6. Loading state shows on button
7. On success:
    - Toast notification: "Feature created! X tasks added to board"
    - Dialog closes
    - Page reloads with new tasks visible
8. On error:
    - Display validation errors below textarea
    - Keep dialog open for corrections

## Acceptance Criteria

- [ ] "Add Feature" button replaces "Add Task" button in dashboard header
- [ ] Clicking button opens dialog with textarea for feature description
- [ ] Textarea validates min 10, max 2000 characters
- [ ] Submitting description generates spec via AI
- [ ] Generated spec is saved with `generated_from_idea = true`
- [ ] Tasks are automatically created from spec content
- [ ] Tasks link to spec via `spec_id` foreign key
- [ ] New tasks appear on kanban board after creation
- [ ] Success toast shows number of tasks created
- [ ] Error states display validation messages
- [ ] Dialog closes on successful submission
- [ ] Loading states prevent duplicate submissions
- [ ] All tests pass
- [ ] Code formatted with Pint and Prettier

## Testing Strategy

### Unit Tests

**Test file**: `tests/Unit/Actions/Feature/GenerateSpecFromDescriptionTest.php`

- Test spec generation with valid description
- Test spec model creation with correct attributes
- Test title extraction from generated spec
- Test `generated_from_idea` flag is set to true
- Test error handling for API failures

**Test file**: `tests/Unit/Actions/Feature/CreateTasksFromSpecTest.php`

- Test task creation from spec with task list
- Test task creation from spec with numbered steps
- Test tasks link to spec via `spec_id`
- Test tasks have correct status (Queued)
- Test empty spec returns empty task array
- Test multiple tasks created correctly

### Feature Tests

**Test file**: `tests/Feature/Feature/FeatureWorkflowTest.php`

- Test complete feature workflow from description to tasks
- Test POST to `/projects/{project}/features` creates spec
- Test POST creates tasks on kanban board
- Test validation fails with short description (<10 chars)
- Test validation fails with long description (>2000 chars)
- Test validation fails with missing project_id
- Test successful creation returns correct task count
- Test page reload shows new tasks

### Browser Tests

**Test file**: `tests/Browser/Feature/AddFeatureWorkflowTest.php`

- Test clicking "Add Feature" button opens dialog
- Test typing description and submitting form
- Test character counter updates as user types
- Test submit button disabled when description too short
- Test loading state during submission
- Test success toast appears with task count
- Test dialog closes after successful submission
- Test new tasks appear on kanban board
- Test error messages display for validation failures
- Test dark mode styling

## Code Formatting

**PHP**:

```bash
vendor/bin/pint
```

**TypeScript/React**:

```bash
pnpm run format
```

## Additional Notes

### Spec Parsing Strategy

The `CreateTasksFromSpec` action should be flexible in parsing tasks:

1. Look for markdown task lists: `- [ ] Task name`
2. Look for numbered lists: `1. Task name`
3. Look for heading-based sections with implementation steps
4. Extract task title and optional description

### Error Handling

- API failures from SpecGeneratorService should show user-friendly message
- Network errors should be caught and displayed
- Validation errors should be inline with form fields
- Use toast notifications for success feedback

### Future Enhancements

This feature sets foundation for:

- Viewing spec details before task creation
- Editing generated specs
- Regenerating specs with different prompts
- Linking multiple specs together
- Spec versioning and history

### Integration Points

- Uses existing `SpecGeneratorService` - no changes needed
- Uses existing `Spec` and `Task` models - relationships already defined
- Follows Action pattern as per `.ai/actions` guidelines
- Follows Service pattern as per `.ai/services` guidelines
- No database migrations needed - schema already supports workflow
