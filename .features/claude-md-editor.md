---
name: claude-md-editor
description: Edit CLAUDE.md files for project or per-worktree agent instructions
depends_on: git-worktree-management
---

## Detailed Description

This feature provides a dedicated editor for managing `CLAUDE.md` files, which contain custom instructions for AI agents. Users can maintain a project-level CLAUDE.md and override it per worktree for specific instructions.

### Key Capabilities

- Edit project-level CLAUDE.md (applies to all worktrees by default)
- Edit worktree-specific CLAUDE.md (overrides project-level)
- Markdown preview with syntax highlighting
- Template library for common instruction patterns
- Version history (track changes over time)
- Diff viewer to compare versions
- Export/import CLAUDE.md files
- Validate markdown syntax
- Search and replace within file

### User Stories

1. As a developer, I want to set project-wide agent instructions
2. As a developer, I want to override instructions for a specific feature branch
3. As a developer, I want to preview how my markdown will render
4. As a developer, I want to revert to a previous version if needed
5. As a developer, I want to use templates for common instruction patterns

### File Structure

- Project: `{project_path}/CLAUDE.md`
- Worktree: `{worktree_path}/CLAUDE.md`

If worktree-specific file exists, it takes precedence.

## Detailed Implementation Plan

### Step 1: Create Editor Controller

```bash
php artisan make:controller ClaudeEditorController --no-interaction
```

**Methods:**

- `show()` - Display editor for project or worktree
- `update()` - Save changes to CLAUDE.md
- `preview()` - Return rendered markdown
- `history()` - Get version history
- `restore()` - Restore previous version

### Step 2: Create Editor Service

```bash
php artisan make:class Services/ClaudeEditorService --no-interaction
```

**Methods:**

```php
public function read(Project $project, ?Worktree $worktree = null): string
{
    // Determine file path
    // Read file content
    // Return content
}

public function write(string $content, Project $project, ?Worktree $worktree = null): bool
{
    // Determine file path
    // Backup current version
    // Write new content
    // Return success
}

public function getFilePath(Project $project, ?Worktree $worktree = null): string
{
    if ($worktree) {
        return $worktree->path . '/CLAUDE.md';
    }
    return $project->path . '/CLAUDE.md';
}

public function fileExists(Project $project, ?Worktree $worktree = null): bool
{
    return file_exists($this->getFilePath($project, $worktree));
}
```

### Step 3: Create Version History Model

```bash
php artisan make:model ClaudeVersion -m --no-interaction
```

**Fields:**

- `id`
- `project_id` (foreign key)
- `worktree_id` (nullable, foreign key)
- `content` (text)
- `created_by` (nullable, for future auth)
- `created_at`

Store each save as a version for rollback capability.

### Step 4: Install Markdown Libraries

Frontend:

```bash
pnpm install react-markdown rehype-highlight remark-gfm
```

For syntax highlighting:

```bash
pnpm install highlight.js
```

### Step 5: Create Editor Page Component

```typescript
// resources/js/pages/claude-editor/index.tsx
```

**Layout:**

- Split view: Editor (left) | Preview (right)
- Toggle button to switch between split/full editor/full preview
- Toolbar with actions: Save, History, Templates, Export, Import
- Status indicator: Saved / Unsaved changes
- Breadcrumb: Project / Worktree name

### Step 6: Implement Monaco Editor

Use Monaco Editor (VS Code's editor):

```bash
pnpm install @monaco-editor/react
```

**Features:**

- Markdown syntax highlighting
- Auto-save draft to localStorage
- Keyboard shortcuts (Cmd+S to save)
- Line numbers
- Find and replace

```typescript
import Editor from '@monaco-editor/react'

<Editor
    height="100vh"
    defaultLanguage="markdown"
    value={content}
    onChange={handleChange}
    theme="vs-dark"
    options={{
        minimap: { enabled: false },
        fontSize: 14,
        wordWrap: 'on',
    }}
/>
```

### Step 7: Implement Markdown Preview

```typescript
// resources/js/components/markdown-preview.tsx
```

Use react-markdown:

```typescript
import ReactMarkdown from 'react-markdown'
import remarkGfm from 'remark-gfm'
import rehypeHighlight from 'rehype-highlight'

<ReactMarkdown
    remarkPlugins={[remarkGfm]}
    rehypePlugins={[rehypeHighlight]}
>
    {content}
</ReactMarkdown>
```

Style with Tailwind and GitHub markdown CSS.

### Step 8: Create Template Library

Create predefined templates:

```bash
php artisan make:class Support/ClaudeTemplates --no-interaction
```

**Templates:**

1. **Testing Focus** - Instructions emphasizing test coverage
2. **Minimal Changes** - Instructions for small, focused changes
3. **Documentation** - Instructions for adding docs
4. **API Development** - Instructions for building APIs
5. **Security Focus** - Instructions emphasizing security
6. **Performance** - Instructions for optimization

```php
public static function all(): array
{
    return [
        'testing' => [
            'name' => 'Testing Focus',
            'description' => 'Emphasize test coverage',
            'content' => file_get_contents(resource_path('templates/claude/testing.md')),
        ],
        // ... more templates
    ];
}
```

### Step 9: Create Template Modal Component

```typescript
// resources/js/components/template-modal.tsx
```

**UI:**

- Grid of template cards
- Preview template content
- Insert or replace current content
- Search templates

### Step 10: Implement Version History

Create history sidebar:

```typescript
// resources/js/components/version-history.tsx
```

**Display:**

- List of previous versions with timestamps
- Click to preview diff
- Restore button
- Delete old versions

### Step 11: Implement Diff Viewer

Use react-diff-viewer:

```bash
pnpm install react-diff-viewer-continued
```

Show side-by-side comparison of versions:

```typescript
import DiffViewer from 'react-diff-viewer-continued'

<DiffViewer
    oldValue={oldVersion}
    newValue={currentVersion}
    splitView={true}
/>
```

### Step 12: Add Auto-save

Implement auto-save to localStorage:

```typescript
useEffect(() => {
    const timer = setTimeout(() => {
        localStorage.setItem(`claude-draft-${id}`, content);
    }, 1000);

    return () => clearTimeout(timer);
}, [content]);
```

Recover draft on page load:

```typescript
useEffect(() => {
    const draft = localStorage.getItem(`claude-draft-${id}`);
    if (draft && draft !== savedContent) {
        // Show "Recover unsaved changes?" dialog
    }
}, []);
```

### Step 13: Add Export/Import

**Export:**

- Download CLAUDE.md file
- Button: "Download as .md"

**Import:**

- Upload .md file
- Replace or append to current content
- Confirm before replacing

### Step 14: Create Form Request Validation

```bash
php artisan make:request UpdateClaudeFileRequest --no-interaction
```

**Validation Rules:**

- `content` - required, string, max:1000000 (1MB)
- `worktree_id` - nullable, exists:worktrees,id

### Step 15: Add Routes

Define routes:

- `GET /projects/{project}/claude-editor` - Editor page for project
- `GET /worktrees/{worktree}/claude-editor` - Editor page for worktree
- `POST /claude-editor/save` - Save changes
- `GET /claude-editor/history` - Get version history
- `POST /claude-editor/restore/{version}` - Restore version

### Step 16: Implement Observer for CLAUDE.md Changes

Create observer to track when file changes:

```bash
php artisan make:observer ClaudeVersionObserver --model=ClaudeVersion --no-interaction
```

Create version snapshot on save.

### Step 17: Add Keyboard Shortcuts

Implement shortcuts:

- `Cmd/Ctrl + S` - Save
- `Cmd/Ctrl + P` - Toggle preview
- `Cmd/Ctrl + H` - Show history
- `Escape` - Close modals

### Step 18: Create Feature Tests

Test coverage:

- `it('can read project CLAUDE.md file')`
- `it('can read worktree CLAUDE.md file')`
- `it('worktree file takes precedence over project file')`
- `it('can save changes to CLAUDE.md')`
- `it('creates version on save')`
- `it('can restore previous version')`
- `it('can export CLAUDE.md file')`

### Step 19: Create Browser Tests

E2E test coverage:

- `it('can open editor')`
- `it('can edit and save CLAUDE.md')`
- `it('shows preview correctly')`
- `it('can insert template')`
- `it('can view version history')`
- `it('can restore previous version')`

### Step 20: Format Code

```bash
vendor/bin/pint --dirty
pnpm run format
```
