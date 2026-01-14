---
name: database-and-models
description: Core database schema and Eloquent models for Sage
depends_on: null
---

## Detailed Description

This feature establishes the foundational data layer for Sage, including all database tables, migrations, Eloquent models, relationships, and factories. This is the backbone that all other features depend on.

### Database Structure

**Projects Table**

- `id` (primary key)
- `name` (string) - Display name of the Laravel project
- `path` (string) - Absolute file path to project root
- `server_driver` (enum: caddy, nginx) - Which server driver to use
- `base_url` (string) - Base domain for preview URLs (e.g., myapp.local)
- `timestamps`

**Worktrees Table**

- `id` (primary key)
- `project_id` (foreign key → projects)
- `branch_name` (string) - Git branch name
- `path` (string) - Absolute path to worktree directory
- `preview_url` (string) - Full preview URL (e.g., feature-auth.myapp.local)
- `status` (enum: creating, active, error, cleaning_up, deleted)
- `env_overrides` (json) - Custom .env variables for this worktree
- `timestamps`

**Tasks Table**

- `id` (primary key)
- `project_id` (foreign key → projects)
- `worktree_id` (foreign key → worktrees, nullable)
- `title` (string) - Task name
- `description` (text) - Full task description/prompt
- `status` (enum: idea, in_progress, review, done, failed)
- `agent_type` (string, nullable) - Which agent is handling this (claude, opencode)
- `model` (string, nullable) - AI model used (claude-sonnet-4-20250514, etc.)
- `agent_output` (text, nullable) - Captured agent terminal output
- `started_at` (timestamp, nullable)
- `completed_at` (timestamp, nullable)
- `timestamps`

**Commits Table**

- `id` (primary key)
- `task_id` (foreign key → tasks)
- `sha` (string) - Git commit hash
- `message` (text) - Commit message
- `author` (string) - Commit author
- `created_at` (timestamp)

**Specs Table**

- `id` (primary key)
- `project_id` (foreign key → projects)
- `title` (string) - Feature specification title
- `content` (text) - Markdown content
- `generated_from_idea` (text, nullable) - Original idea/prompt that generated this spec
- `timestamps`

### Model Relationships

**Project**

- `hasMany(Worktree::class)`
- `hasMany(Task::class)`
- `hasMany(Spec::class)`

**Worktree**

- `belongsTo(Project::class)`
- `hasMany(Task::class)`

**Task**

- `belongsTo(Project::class)`
- `belongsTo(Worktree::class)`
- `hasMany(Commit::class)`

**Commit**

- `belongsTo(Task::class)`

**Spec**

- `belongsTo(Project::class)`

## Detailed Implementation Plan

### Step 1: Create Models and Migrations

```bash
php artisan make:model Project -mf --no-interaction
php artisan make:model Worktree -mf --no-interaction
php artisan make:model Task -mf --no-interaction
php artisan make:model Commit -mf --no-interaction
php artisan make:model Spec -mf --no-interaction
```

### Step 2: Define Migrations

- Create migration files with proper schema definitions
- Add foreign key constraints with cascading deletes where appropriate
- Use appropriate indexes (e.g., on `project_id`, `branch_name`, `status`)
- Add JSON columns with proper casting

### Step 3: Configure Models

- Define fillable/guarded properties
- Add relationship methods
- Configure casts (JSON fields, timestamps, enums)
- Add accessors/mutators as needed (e.g., preview URL generation)

### Step 4: Create Factories

- Generate realistic test data for all models
- Define relationships in factories using `Model::factory()`
- Create factory states for common scenarios:
    - Active/inactive worktrees
    - Tasks in different statuses
    - Projects with different server drivers

### Step 5: Run Migrations

```bash
php artisan migrate
```

### Step 6: Create Tests

- Unit tests for model relationships
- Unit tests for accessors/mutators
- Feature tests for cascade deletes
- Factory tests to ensure they work correctly

### Step 7: Validation

- Test all relationships work bidirectionally
- Verify factories can create complete object graphs
- Ensure enum constraints work properly
- Test JSON field casting
