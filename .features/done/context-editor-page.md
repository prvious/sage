---
name: context-editor-page
description: Page to manage and edit .ai/ directory markdown files for custom agent rules
depends_on: project-context-sidebar
---

## Feature Description

Create a dedicated page for managing markdown files in the `.ai/` directory, which contains custom agent rules that get aggregated into `CLAUDE.md` or `AGENTS.md` when running `php artisan boost:install` or `boost:update`.

The `.ai/` directory is a Laravel-specific convention used by Laravel Boost to store custom agent instructions organized by topic (e.g., `.ai/tests.md`, `.ai/actions.md`). This editor will allow users to create, edit, and delete these rule files, and trigger the aggregation process to update the main `CLAUDE.md` or `AGENTS.md` file.

## Implementation Plan

### Backend Components

**Controller**:

- Create `app/Http/Controllers/ContextController.php`
- Methods:
    - `index(Project $project)` - List all .ai/ files
    - `show(Project $project, string $file)` - Display specific file content
    - `create(Project $project)` - Show form to create new .ai/ file
    - `store(Request $request, Project $project)` - Save new .ai/ file
    - `edit(Project $project, string $file)` - Show edit form
    - `update(Request $request, Project $project, string $file)` - Update file content
    - `destroy(Project $project, string $file)` - Delete .ai/ file
    - `aggregate(Project $project)` - Run boost:install or boost:update

**Actions**:

- Create `app/Actions/Context/ListContextFiles.php`
- Create `app/Actions/Context/ReadContextFile.php`
- Create `app/Actions/Context/WriteContextFile.php`
- Create `app/Actions/Context/DeleteContextFile.php`
- Create `app/Actions/Context/AggregateContextFiles.php` - Run artisan command

**Form Request**:

- Create `app/Http/Requests/StoreContextFileRequest.php`
    - Validate filename (must end with .md)
    - Validate content (required, must be valid markdown)
    - Validate filename is safe (no directory traversal)

**Routes**:

```php
Route::prefix('projects/{project}')->group(function () {
    Route::get('/context', [ContextController::class, 'index'])->name('projects.context.index');
    Route::get('/context/create', [ContextController::class, 'create'])->name('projects.context.create');
    Route::post('/context', [ContextController::class, 'store'])->name('projects.context.store');
    Route::get('/context/{file}', [ContextController::class, 'show'])->name('projects.context.show');
    Route::get('/context/{file}/edit', [ContextController::class, 'edit'])->name('projects.context.edit');
    Route::put('/context/{file}', [ContextController::class, 'update'])->name('projects.context.update');
    Route::delete('/context/{file}', [ContextController::class, 'destroy'])->name('projects.context.destroy');
    Route::post('/context/aggregate', [ContextController::class, 'aggregate'])->name('projects.context.aggregate');
});
```

**File Operations**:

- Read/write files from `{project_path}/.ai/` directory
- Validate file paths to prevent directory traversal
- Handle file system errors gracefully
- Use Laravel's `Storage` facade or `File` facade

**Artisan Command Integration**:

- Execute `php artisan boost:install` or `boost:update` via `Artisan::call()`
- Capture command output for user feedback
- Run in background queue for large projects
- Return success/error status to frontend

### Frontend Components

**Pages**:

- Create `resources/js/pages/projects/context/index.tsx` - List of .ai/ files
- Create `resources/js/pages/projects/context/create.tsx` - Create new file form
- Create `resources/js/pages/projects/context/edit.tsx` - Edit file form

**Components**:

- Create `resources/js/components/context/file-list.tsx` - List of context files with actions
- Create `resources/js/components/context/markdown-editor.tsx` - Markdown editor component
- Create `resources/js/components/context/markdown-preview.tsx` - Live markdown preview
- Create `resources/js/components/context/aggregate-button.tsx` - Trigger aggregation

**Markdown Editor**:

- Use a markdown editor library (e.g., CodeMirror, Monaco Editor, or react-markdown-editor)
- Features:
    - Syntax highlighting for markdown
    - Live preview pane
    - Toolbar with common markdown formatting
    - Line numbers
    - Search and replace

**File List UI**:

- Display all .ai/ files in a table or card grid
- Show file name, last modified date, file size
- Actions: View, Edit, Delete
- Create new file button
- "Aggregate Files" button to run boost command

**Create/Edit Forms**:

- Filename input (auto-append .md if not present)
- Markdown editor textarea
- Preview pane (split view)
- Save and Cancel buttons
- Validation feedback

**Aggregate Functionality**:

- Button to trigger `boost:install` or `boost:update`
- Loading state while command runs
- Success/error toast notification
- Display command output in modal or console

### Styling

**Shadcn Components**:

- Use `Table` or `Card` for file list
- Use `Button` for actions (Create, Edit, Delete, Aggregate)
- Use `Dialog` for delete confirmation
- Use `Form` components for create/edit forms
- Use `Textarea` or custom editor for markdown input
- Use `Alert` for success/error messages
- Use `Tabs` for editor/preview split view

**Layout**:

- Two-column layout: File list on left, editor/preview on right
- Or single page with breadcrumb navigation
- Responsive design for mobile (stack vertically)

## Acceptance Criteria

- [ ] Context page lists all .ai/ files in the project
- [ ] Users can create new .ai/ files with .md extension
- [ ] Users can edit existing .ai/ files
- [ ] Users can delete .ai/ files (with confirmation)
- [ ] Markdown editor has syntax highlighting
- [ ] Live preview shows rendered markdown
- [ ] "Aggregate Files" button triggers boost command
- [ ] Success/error messages display after file operations
- [ ] File validation prevents invalid filenames or paths
- [ ] Directory traversal attacks are prevented
- [ ] boost:install or boost:update output is shown to user
- [ ] Files are saved to correct project's .ai/ directory
- [ ] CLAUDE.md or AGENTS.md is updated after aggregation
- [ ] No console errors or warnings
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location**: `tests/Feature/Context/ContextControllerTest.php`

**Key test cases**:

- Test index page lists all .ai/ files
- Test create page renders form
- Test storing new file creates .md file in .ai/ directory
- Test edit page loads file content
- Test updating file saves changes
- Test deleting file removes .md file
- Test aggregate endpoint runs boost command
- Test validation prevents invalid filenames
- Test validation prevents directory traversal
- Test file operations work with different project paths
- Test unauthorized access is prevented

### Browser Tests

**Test file location**: `tests/Browser/ContextEditorTest.php`

**Key test cases**:

- Test navigating to context page shows file list
- Test clicking "Create" opens new file form
- Test entering filename and content saves new file
- Test new file appears in file list
- Test clicking "Edit" loads file in editor
- Test editing file content and saving updates file
- Test markdown preview updates in real-time
- Test delete button shows confirmation dialog
- Test confirming delete removes file from list
- Test "Aggregate Files" button triggers boost command
- Test command output displays to user
- Test success toast appears after file save
- Test validation errors display for invalid input

## Code Formatting

Format all code using:

- **Backend (PHP)**: Laravel Pint
    - Command: `vendor/bin/pint --dirty`
- **Frontend (TypeScript/React)**: Prettier
    - Command: `pnpm run format`

## Additional Notes

### .ai/ Directory Structure

The `.ai/` directory typically contains topic-specific markdown files:

```
.ai/
├── tests.md           # Testing guidelines
├── actions.md         # Actions pattern guidelines
├── shadcn.md          # Shadcn UI guidelines
├── foundation.md      # Foundational rules
└── custom-rules.md    # Project-specific rules
```

These files get aggregated into `CLAUDE.md` or `AGENTS.md` with headers:

```markdown
<laravel-boost-guidelines>
=== .ai/tests rules ===

[Content from tests.md]

=== .ai/actions rules ===

[Content from actions.md]
</laravel-boost-guidelines>
```

### Markdown Editor Library Options

Consider these libraries:

- **Monaco Editor** (VS Code editor) - Full-featured, heavy
- **CodeMirror 6** - Lightweight, extensible
- **react-md-editor** - Simple, with preview
- **react-markdown-editor-lite** - Lightweight with toolbar

### File Validation

**Filename Rules**:

- Must end with `.md`
- Only alphanumeric, dash, underscore allowed
- No directory separators (/, \)
- Maximum length (e.g., 255 characters)

**Content Validation**:

- Required, cannot be empty
- Optional: Validate markdown syntax
- Optional: Check for harmful content

### Security Considerations

**Path Traversal Prevention**:

```php
// Validate filename
if (str_contains($filename, '..') || str_contains($filename, '/')) {
    throw new InvalidArgumentException('Invalid filename');
}

// Ensure file is in .ai/ directory
$basePath = $project->path . '/.ai/';
$filePath = realpath($basePath . $filename);
if (!str_starts_with($filePath, $basePath)) {
    throw new SecurityException('Path traversal detected');
}
```

### Artisan Command Integration

**Running boost commands**:

```php
use Illuminate\Support\Facades\Artisan;

// Run boost:install
Artisan::call('boost:install', ['--project-path' => $project->path]);
$output = Artisan::output();

// Or run boost:update
Artisan::call('boost:update', ['--project-path' => $project->path]);
$output = Artisan::output();
```

### Future Enhancements

- **Templates** - Predefined templates for common rule types
- **File search** - Search across all .ai/ files
- **Version control** - Track file changes with Git integration
- **Diff viewer** - Show changes before/after aggregation
- **Import/Export** - Share .ai/ files between projects
- **Collaborative editing** - Real-time collaborative editing (future)
- **AI assistance** - Suggest improvements to agent rules
- **Syntax validation** - Validate markdown and rule syntax
- **Auto-save** - Automatically save changes
- **File upload** - Upload existing .md files to .ai/ directory

### Integration with Laravel Boost

This feature assumes Laravel Boost MCP server is available and provides:

- `boost:install` Artisan command
- `boost:update` Artisan command
- `.ai/` directory convention
- Aggregation into `CLAUDE.md` or `AGENTS.md`

If these commands don't exist, the feature should gracefully handle missing commands and show appropriate error messages.

### Error Handling

Handle common errors:

- File not found (404)
- Permission denied (403)
- Invalid markdown syntax (validation error)
- Artisan command failure (show command output)
- File system errors (disk full, etc.)
