---
name: environment-manager
description: View and edit .env files across main project and all worktrees
depends_on: git-worktree-management
---

## Detailed Description

This feature provides a centralized interface for managing environment variables across the main project and all worktrees. It allows developers to view, edit, and sync .env files without manually navigating to each directory.

### Key Capabilities
- List all .env files (main project + worktrees)
- View .env file contents in organized sections
- Edit environment variables with validation
- Add/remove variables
- Copy variables between worktrees
- Sync main .env to all worktrees (with selective override)
- Detect missing required variables
- Hide sensitive values by default (password masking)
- Export/import .env files
- Compare .env files between worktrees
- Validate .env format and syntax

### User Stories
1. As a developer, I want to see all .env files in one place
2. As a developer, I want to update APP_URL for a worktree without leaving the dashboard
3. As a developer, I want to sync common variables from main to all worktrees
4. As a developer, I want to ensure all worktrees have required variables set
5. As a developer, I want to compare .env differences between worktrees

### Security Considerations
- Mask sensitive values (passwords, API keys) by default
- Option to reveal values when needed
- Never log .env contents
- Validate file permissions before writing
- Backup .env before modifications

## Detailed Implementation Plan

### Step 1: Create Environment Manager Service
```bash
php artisan make:class Services/EnvironmentManager --no-interaction
```

**Methods:**
```php
public function read(string $path): array
{
    // Read .env file
    // Parse into key-value array
    // Return structured data
}

public function write(string $path, array $variables): bool
{
    // Backup current .env
    // Write new variables to .env
    // Validate file was written correctly
    // Return success
}

public function backup(string $path): string
{
    // Copy .env to .env.backup.{timestamp}
    // Return backup path
}

public function validate(array $variables): array
{
    // Check for required variables (APP_KEY, etc.)
    // Validate format (no spaces in keys, etc.)
    // Return validation errors
}

public function compare(string $path1, string $path2): array
{
    // Read both files
    // Find differences
    // Return diff structure
}
```

### Step 2: Create Environment Parser
```bash
php artisan make:class Support/EnvParser --no-interaction
```

**Methods:**
```php
public static function parse(string $content): array
{
    // Parse .env content line by line
    // Handle comments, empty lines, multiline values
    // Return structured array with metadata
    return [
        'APP_NAME' => [
            'value' => 'Sage',
            'comment' => 'Application name',
            'is_sensitive' => false,
        ],
        'DB_PASSWORD' => [
            'value' => 'secret123',
            'comment' => null,
            'is_sensitive' => true,
        ],
    ];
}

public static function stringify(array $variables): string
{
    // Convert array back to .env format
    // Preserve comments and structure
    // Return formatted string
}

public static function isSensitive(string $key): bool
{
    // Check if key contains sensitive data
    $sensitiveKeys = ['PASSWORD', 'SECRET', 'KEY', 'TOKEN', 'API_KEY'];
    foreach ($sensitiveKeys as $pattern) {
        if (str_contains($key, $pattern)) {
            return true;
        }
    }
    return false;
}
```

### Step 3: Create Environment Controller
```bash
php artisan make:controller EnvironmentController --no-interaction
```

**Methods:**
- `index()` - List all .env files (project + worktrees)
- `show()` - Display specific .env file
- `update()` - Update .env file
- `sync()` - Sync main .env to worktrees
- `compare()` - Compare two .env files

### Step 4: Create Environment Manager Page
```typescript
// resources/js/Pages/Environment/Index.tsx
```

**Layout:**
- Sidebar: List of .env files (main + all worktrees)
- Main area: Selected .env file editor
- Grouped by sections (App, Database, Cache, etc.)
- Actions: Save, Sync to All, Compare, Export

### Step 5: Create Environment Variable Form
```typescript
// resources/js/Components/EnvVariableForm.tsx
```

**UI:**
- Key-value pairs in a structured form
- Group variables by section (auto-detect from prefixes)
- Input types based on value (text, password, number, boolean)
- Mask sensitive values with toggle to reveal
- Add new variable button
- Delete variable button (with confirmation)
- Validation feedback

Example structure:
```typescript
interface EnvVariable {
    key: string
    value: string
    comment?: string
    isSensitive: boolean
    section: string // APP, DB, CACHE, etc.
}
```

### Step 6: Implement Variable Grouping

Group variables by common prefixes:
```typescript
const groupVariables = (variables: EnvVariable[]) => {
    return {
        'Application': variables.filter(v => v.key.startsWith('APP_')),
        'Database': variables.filter(v => v.key.startsWith('DB_')),
        'Cache': variables.filter(v => v.key.startsWith('CACHE_')),
        'Queue': variables.filter(v => v.key.startsWith('QUEUE_')),
        'Mail': variables.filter(v => v.key.startsWith('MAIL_')),
        'Other': variables.filter(v => !hasCommonPrefix(v.key)),
    }
}
```

Display as collapsible sections.

### Step 7: Implement Sync Functionality

Create sync dialog:
```typescript
// resources/js/Components/SyncEnvDialog.tsx
```

**Options:**
- Select which variables to sync
- Select target worktrees (or all)
- Overwrite existing values (checkbox)
- Preview changes before applying

API endpoint:
```php
POST /environment/sync
{
    "source_id": 1, // Project ID or Worktree ID
    "targets": [2, 3, 4], // Worktree IDs
    "variables": ["APP_NAME", "APP_DEBUG"],
    "overwrite": true
}
```

### Step 8: Implement Comparison View
```typescript
// resources/js/Components/EnvComparison.tsx
```

Use diff viewer:
```bash
npm install react-diff-viewer-continued
```

**Display:**
- Side-by-side comparison
- Highlight differences (added, removed, changed)
- Copy values between files
- Merge changes

### Step 9: Implement Password Masking

Mask sensitive values:
```typescript
const MaskedValue = ({ value, isSensitive }: Props) => {
    const [revealed, setRevealed] = useState(false)

    if (!isSensitive) {
        return <span>{value}</span>
    }

    return (
        <div className="flex items-center gap-2">
            <span>{revealed ? value : '••••••••'}</span>
            <Button onClick={() => setRevealed(!revealed)}>
                {revealed ? <EyeOff /> : <Eye />}
            </Button>
        </div>
    )
}
```

### Step 10: Add Validation

Validate on the backend:
```bash
php artisan make:request UpdateEnvironmentRequest --no-interaction
```

**Validation Rules:**
- Keys must be uppercase with underscores
- Keys cannot have spaces
- Values must be properly quoted if containing spaces
- Required variables must be present (APP_KEY, etc.)

Frontend validation:
- Real-time validation as user types
- Show errors inline
- Prevent saving invalid .env

### Step 11: Implement Required Variables Check

Define required variables:
```php
// config/sage.php
'required_env_variables' => [
    'APP_NAME',
    'APP_ENV',
    'APP_KEY',
    'APP_URL',
    'DB_CONNECTION',
],
```

Check on page load:
```php
public function checkRequired(array $variables): array
{
    $required = config('sage.required_env_variables');
    $missing = [];

    foreach ($required as $key) {
        if (!isset($variables[$key]) || empty($variables[$key])) {
            $missing[] = $key;
        }
    }

    return $missing;
}
```

Show warning banner if variables are missing.

### Step 12: Add Export/Import

**Export:**
- Download .env file
- Export as JSON (for backup with metadata)

**Import:**
- Upload .env file
- Parse and validate
- Preview before applying
- Merge or replace options

### Step 13: Implement Backup/Restore

Before any modification:
- Create timestamped backup: `.env.backup.{timestamp}`
- Store backups in `storage/backups/env/`
- List available backups
- Restore from backup with confirmation

### Step 14: Add Search and Filter

Search bar to filter variables:
- Search by key name
- Search by value
- Filter by section
- Filter sensitive only

### Step 15: Create Feature Tests

Test coverage:
- `it('can read .env file')`
- `it('can parse .env file correctly')`
- `it('can update .env variables')`
- `it('detects sensitive variables')`
- `it('validates variable format')`
- `it('creates backup before modification')`
- `it('can sync variables to worktrees')`
- `it('can compare two .env files')`
- `it('checks for required variables')`

### Step 16: Create Browser Tests

E2E test coverage:
- `it('can view .env file')`
- `it('can edit variable value')`
- `it('masks sensitive values by default')`
- `it('can reveal masked values')`
- `it('can add new variable')`
- `it('can delete variable')`
- `it('can sync to worktrees')`
- `it('shows validation errors')`

### Step 17: Add Routes

Define routes:
- `GET /environment` - Environment manager page
- `GET /environment/project/{project}` - Show project .env
- `GET /environment/worktree/{worktree}` - Show worktree .env
- `POST /environment/update` - Update .env file
- `POST /environment/sync` - Sync variables
- `GET /environment/compare` - Compare .env files
- `POST /environment/restore` - Restore from backup

### Step 18: Handle File Permissions

Check and fix permissions:
```php
public function ensureWritable(string $path): bool
{
    if (!is_writable($path)) {
        chmod($path, 0644);
    }
    return is_writable($path);
}
```

Show error if file is not writable.

### Step 19: Add Activity Log

Log all .env modifications:
- Who changed what (for future auth)
- Timestamp
- Previous and new values
- Worktree/project affected

Store in database for audit trail.

### Step 20: Format Code
```bash
vendor/bin/pint --dirty
npm run format
```
