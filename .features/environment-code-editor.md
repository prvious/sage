---
name: environment-code-editor
description: Replace individual input fields with a code editor for .env file editing
depends_on: null
---

## Feature Description

Replace the current form-based environment variable editor with a single code editor interface that provides a native file editing experience. Instead of individual input fields for each variable, users will edit the raw .env file content directly in an editor with syntax highlighting and validation.

This change provides:
- More natural editing experience for developers familiar with .env files
- Ability to preserve comments and formatting
- Faster editing with keyboard shortcuts and multi-cursor support
- Better handling of complex values with line breaks
- Familiar VS Code-like editing experience

## Implementation Plan

### Package Selection

**Recommended: Monaco Editor** (`@monaco-editor/react`)
- Same editor that powers VS Code
- Excellent TypeScript support
- Built-in .env syntax highlighting
- Auto-completion, multi-cursor, find/replace
- Lightweight React wrapper available
- Command: `pnpm add @monaco-editor/react monaco-editor`

**Alternative: CodeMirror 6** (`@uiw/react-codemirror`)
- Lighter weight than Monaco
- Good performance
- Simpler API
- Would need custom .env language support

### Backend Components

**Update Controller**:
- `app/Http/Controllers/EnvironmentController.php`
  - Modify `index()` to return raw .env content instead of parsed variables
  - Keep backup/restore functionality unchanged
  - Update validation to work with raw content

**Update Actions**:
- `app/Actions/ReadEnvFile.php`
  - Add method to return raw content: `handleRaw(string $path): string`
  - Keep existing `handle()` for backward compatibility if needed elsewhere

- `app/Actions/WriteEnvFile.php`
  - Add method to write raw content: `handleRaw(string $path, string $content): void`
  - Validate content is valid .env format before writing
  - Keep existing `handle()` for backward compatibility

**Update Form Request**:
- `app/Http/Requests/UpdateEnvironmentRequest.php`
  - Change validation from array of variables to single content string
  - Add validation to ensure valid .env format
  - Check for required variables if needed

### Frontend Components

**Remove Files**:
- `resources/js/components/env-variable-form.tsx` - Delete old form component

**Create New Component**:
- `resources/js/components/env-editor.tsx`
  - Monaco Editor integration
  - .env language/syntax highlighting
  - Auto-save or manual save with Cmd/Ctrl+S
  - Loading state while fetching content
  - Error boundary for editor failures
  - Dark mode support matching app theme

**Update Page**:
- `resources/js/pages/projects/environment.tsx`
  - Replace `<EnvVariableForm>` with `<EnvEditor>`
  - Update props to pass raw content instead of grouped variables
  - Keep backup/restore UI
  - Keep alerts for errors and missing variables

### Monaco Editor Configuration

**Editor Options**:
```typescript
{
  language: 'ini', // .env files use INI-like syntax
  theme: isDark ? 'vs-dark' : 'vs-light',
  automaticLayout: true,
  minimap: { enabled: false },
  fontSize: 14,
  lineNumbers: 'on',
  scrollBeyondLastLine: false,
  wordWrap: 'on',
  tabSize: 2,
}
```

**Custom .env Language** (optional enhancement):
- Define custom language mode for better .env syntax
- Highlight variable names differently from values
- Mark sensitive variables (API keys, passwords)
- Validation for common .env patterns

### Data Flow

**Current Flow**:
1. Backend parses .env → grouped variables object
2. Frontend renders individual inputs per variable
3. User edits individual fields
4. Frontend sends variables array to backend
5. Backend reconstructs .env file

**New Flow**:
1. Backend reads .env → raw string content
2. Frontend displays in Monaco Editor
3. User edits raw content
4. Frontend sends raw content to backend
5. Backend writes content directly to .env file

### Validation Strategy

**Frontend Validation**:
- Warn on invalid .env syntax (optional, non-blocking)
- Check for empty variable names
- Highlight potential issues

**Backend Validation**:
- Validate .env format before writing
- Ensure required variables are present
- Prevent writing if validation fails
- Return specific error messages

### Backup/Restore Functionality

**Keep Existing Behavior**:
- Auto-backup before each save
- Restore from backup list
- Show backup timestamps
- No changes needed to BackupEnvFile action

### Testing Strategy

**Update Existing Tests**:
- `tests/Feature/Http/Controllers/EnvironmentControllerTest.php`
  - Update to test raw content endpoints
  - Test validation for malformed .env content
  - Test backup creation still works
  - Test restore functionality

**Browser Tests**:
- `tests/Browser/Environment/EnvEditorTest.php`
  - Test editor loads with current .env content
  - Test editing and saving content
  - Test validation errors display
  - Test dark/light mode theme switching
  - Test keyboard shortcuts (Cmd/Ctrl+S to save)
  - Test large .env files load properly

**Key Test Cases**:
- Loading existing .env content
- Editing and saving valid content
- Handling invalid .env syntax
- Preserving comments and blank lines
- Backup creation on save
- Restore from backup
- Error handling when .env is missing

## Component Example

```typescript
import Editor from '@monaco-editor/react';
import { useForm } from '@inertiajs/react';
import { useTheme } from '@/hooks/use-theme';

interface EnvEditorProps {
    content: string;
    envPath: string;
    projectId: number;
}

export function EnvEditor({ content, envPath, projectId }: EnvEditorProps) {
    const { theme } = useTheme();
    const { data, setData, put, processing, errors } = useForm({
        content: content,
    });

    const handleSave = () => {
        put(route('projects.environment.update', projectId));
    };

    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between">
                    <CardTitle>Environment File</CardTitle>
                    <Button onClick={handleSave} disabled={processing}>
                        {processing ? 'Saving...' : 'Save Changes'}
                    </Button>
                </div>
            </CardHeader>
            <CardContent>
                <Editor
                    height="600px"
                    language="ini"
                    theme={theme === 'dark' ? 'vs-dark' : 'vs-light'}
                    value={data.content}
                    onChange={(value) => setData('content', value || '')}
                    options={{
                        automaticLayout: true,
                        minimap: { enabled: false },
                        fontSize: 14,
                        wordWrap: 'on',
                    }}
                />
                {errors.content && (
                    <p className="text-sm text-destructive mt-2">{errors.content}</p>
                )}
            </CardContent>
        </Card>
    );
}
```

## Acceptance Criteria

- [ ] Monaco Editor package is installed and configured
- [ ] EnvVariableForm component is deleted
- [ ] New EnvEditor component is created with Monaco integration
- [ ] Editor supports .env syntax highlighting
- [ ] Editor theme matches app dark/light mode
- [ ] Backend returns raw .env content in index()
- [ ] Backend accepts raw content in update()
- [ ] Validation prevents saving invalid .env format
- [ ] Comments and blank lines are preserved
- [ ] Backup functionality still works
- [ ] Restore functionality still works
- [ ] Keyboard shortcut Cmd/Ctrl+S saves (optional)
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Code Formatting

Format all code using:
- **Backend (PHP)**: Laravel Pint
  - Command: `vendor/bin/pint --dirty`
- **Frontend (TypeScript/React)**: Prettier
  - Command: `pnpm run format`

## Additional Notes

### Monaco Editor Benefits
- Industry-standard editor (VS Code)
- Excellent accessibility support
- Built-in keyboard shortcuts developers expect
- Multi-cursor editing
- Find and replace functionality
- Undo/redo with full history

### Sensitive Variables
- Consider adding visual indicators for sensitive variables (API keys, passwords)
- Could highlight lines containing common sensitive patterns
- Optional: Add warning when sensitive values are visible

### Auto-Save
- Consider implementing auto-save with debouncing
- Could save on blur or after 2 seconds of inactivity
- Show "Saving..." indicator during auto-save

### Mobile Considerations
- Monaco Editor works on mobile but might not be ideal
- Consider showing a warning or simpler textarea on small screens
- Or keep mobile editing functional but inform users desktop is better

### Performance
- Monaco lazy loads language support
- First load might take a moment - show loading state
- Consider preloading Monaco on page navigation

### Future Enhancements
- .env file templates for common setups
- Variable suggestions based on Laravel standards
- Duplicate variable detection
- Validation for specific required variables per project type
- Diff view when restoring from backup
