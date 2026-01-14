---
name: git-worktree-management
description: Create, manage, and delete Git worktrees with preview URLs
depends_on: project-management
---

## Detailed Description

This feature enables creating and managing Git worktrees for a Laravel project. Each worktree represents a separate working directory for a feature branch, complete with its own preview URL and environment configuration.

### Key Capabilities
- Create new worktree from branch name (or create branch if it doesn't exist)
- Automatically generate preview URL from branch name (e.g., `feature/auth` → `feature-auth.myapp.local`)
- Copy and patch `.env` file for each worktree with correct `APP_URL`
- Handle database isolation (separate SQLite file or prefix option)
- List all worktrees with status indicators
- Delete worktrees and clean up associated resources
- Visual status: creating, active, error, cleaning_up
- Run composer/npm install for new worktrees
- Detect and prevent conflicts (duplicate branch names)

### User Stories
1. As a developer, I want to create a worktree for a feature branch so I can work on it independently
2. As a developer, I want each worktree to have its own preview URL so I can test in isolation
3. As a developer, I want worktrees to have separate databases to avoid conflicts
4. As a developer, I want to delete worktrees when I'm done with them

### Technical Challenges
- Ensuring each worktree has correct `APP_URL` in `.env`
- Database isolation (separate SQLite per worktree or prefix)
- Running setup commands (composer install, npm install, migrations) asynchronously
- Safe cleanup when deleting worktrees

## Detailed Implementation Plan

### Step 1: Create Worktree Service
```bash
php artisan make:class Services/WorktreeService --no-interaction
```

**Methods to Implement:**
- `create(Project $project, string $branchName): Worktree`
- `delete(Worktree $worktree): void`
- `generatePreviewUrl(string $branchName, string $baseUrl): string`
- `setupEnvironment(Worktree $worktree): void`
- `runSetupCommands(Worktree $worktree): void`

### Step 2: Create Git Service
```bash
php artisan make:class Services/GitService --no-interaction
```

**Methods to Implement:**
- `createWorktree(string $projectPath, string $branchName, string $worktreePath): bool`
- `removeWorktree(string $worktreePath): bool`
- `listWorktrees(string $projectPath): array`
- `branchExists(string $projectPath, string $branchName): bool`
- `createBranch(string $projectPath, string $branchName): bool`

Use `Symfony\Component\Process\Process` for Git commands.

### Step 3: Create Preview URL Helper
```bash
php artisan make:class Support/PreviewUrl --no-interaction
```

**Logic:**
- Sanitize branch name: `feature/auth-system` → `feature-auth-system`
- Combine with base URL: `feature-auth-system.myapp.local`
- Validate URL format
- Handle edge cases (very long branch names, special characters)

### Step 4: Create Environment Patcher
```bash
php artisan make:class Services/EnvironmentPatcher --no-interaction
```

**Responsibilities:**
- Copy `.env` from main project to worktree
- Update `APP_URL` to preview URL
- Handle database isolation:
  - Option 1: Change `DB_DATABASE` to separate SQLite file
  - Option 2: Add table prefix
  - Make this configurable per project
- Update `APP_NAME` to include branch name for clarity

### Step 5: Create Worktree Job for Setup
```bash
php artisan make:job SetupWorktreeJob --no-interaction
```

**Job Tasks:**
1. Update worktree status to `creating`
2. Create Git worktree using `GitService`
3. Setup environment using `EnvironmentPatcher`
4. Run `composer install --no-interaction --quiet`
5. Run `npm install` (if package.json exists)
6. Run `php artisan migrate --force` (if separate DB)
7. Run `php artisan key:generate --force` (if needed)
8. Update worktree status to `active`
9. If any step fails, update status to `error` and log details

Implement `ShouldQueue` interface for async execution.

### Step 6: Create Controller and Routes
```bash
php artisan make:controller WorktreeController --no-interaction
```

Define routes:
- `GET /projects/{project}/worktrees` - List worktrees
- `POST /projects/{project}/worktrees` - Create worktree
- `DELETE /worktrees/{worktree}` - Delete worktree
- `GET /worktrees/{worktree}` - Show worktree details

### Step 7: Create Form Request Validation
```bash
php artisan make:request StoreWorktreeRequest --no-interaction
```

**Validation Rules:**
- `branch_name` - required, string, valid Git branch name format
- `create_branch` - boolean, whether to create branch if it doesn't exist
- `database_isolation` - required, in:separate,prefix,shared

### Step 8: Create Inertia Pages

**Pages:**
- `resources/js/Pages/Worktrees/Index.tsx` - Worktree list/grid
- `resources/js/Pages/Worktrees/Create.tsx` - Create worktree form
- `resources/js/Pages/Worktrees/Show.tsx` - Worktree details

**UI Components:**
- Worktree card with status badge (creating/active/error)
- Preview URL with "Open in Browser" button
- Create form with branch name input and options
- Progress indicator during worktree creation
- Delete confirmation dialog

### Step 9: Add Real-time Status Updates

Use Laravel Reverb to broadcast worktree status changes:
```php
broadcast(new WorktreeStatusUpdated($worktree));
```

Listen on frontend and update UI in real-time as worktree is being created.

### Step 10: Implement Controller Actions

**Store Method:**
- Validate request
- Check if branch already has a worktree
- Dispatch `SetupWorktreeJob`
- Return worktree record (status: creating)
- Job will update status to active when done

**Destroy Method:**
- Update status to `cleaning_up`
- Remove Git worktree using `GitService`
- Delete worktree record from database
- Clean up server config (done by observer/event)

### Step 11: Add Model Observers
```bash
php artisan make:observer WorktreeObserver --model=Worktree --no-interaction
```

**Observer Hooks:**
- `created`: Trigger server config update (handled by server driver)
- `deleted`: Clean up server config, delete worktree directory if still exists

### Step 12: Create Feature Tests

Test coverage:
- `it('can create a worktree for existing branch')`
- `it('can create branch and worktree together')`
- `it('generates correct preview URL from branch name')`
- `it('patches .env file with correct APP_URL')`
- `it('prevents duplicate worktrees for same branch')`
- `it('can delete a worktree')`
- `it('handles database isolation correctly')`
- `it('runs setup commands asynchronously')`

### Step 13: Create Browser Tests

E2E test coverage:
- `it('can create a worktree through UI')`
- `it('shows real-time status updates during creation')`
- `it('can open preview URL in browser')`
- `it('can delete worktree with confirmation')`

### Step 14: Format Code
```bash
vendor/bin/pint --dirty
```
