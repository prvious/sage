---
name: clean-up-migrations
description: Remove database enums and consolidate migration changes into initial table creation migrations
depends_on: null
---

## Feature Description

Clean up the database migrations by removing all database enum usage (replacing with string columns) and consolidating all column modifications into the initial table creation migrations. This approach treats the migrations as a fresh start, eliminating the need for separate "alter table" migrations.

The main issues to fix:

1. Remove all database `enum()` column types - use `string()` instead
2. Delete migration files that only modify existing tables
3. Consolidate those modifications into the original table creation migrations
4. Run `php artisan migrate:fresh` to start with clean migrations

## Implementation Plan

### Migrations to Delete

**Complete removal** (modify existing tables or change data):

- `2026_01_17_054229_update_tasks_status_column.php` - Changes tasks status from enum to string
- `2026_01_16_045634_add_server_settings_to_projects_table.php` - Adds columns to projects
- `2026_01_18_031009_update_projects_remove_server_drivers.php` - Updates data in projects
- `2026_01_18_033337_remove_agent_api_keys_and_opencode.php` - Removes columns from agent_settings

### Migrations to Modify

**1. `2026_01_14_013401_create_projects_table.php`**

Current state:

```php
Schema::create('projects', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('path')->unique();
    $table->string('server_driver', 20)->default('caddy');
    $table->string('base_url');
    $table->timestamps();

    $table->index('name');
});
```

New state (incorporating changes from deleted migrations):

```php
Schema::create('projects', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('path')->unique();
    $table->string('server_driver', 20)->default('artisan'); // Changed default
    $table->string('base_url');
    $table->unsignedInteger('server_port')->nullable(); // Added
    $table->boolean('tls_enabled')->default(false); // Added
    $table->string('custom_domain')->nullable(); // Added
    $table->text('custom_directives')->nullable(); // Added
    $table->timestamps();

    $table->index('name');
});
```

**2. `2026_01_14_013402_create_tasks_table.php`**

Current state:

```php
Schema::create('tasks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->foreignId('worktree_id')->nullable()->constrained()->nullOnDelete();
    $table->string('title');
    $table->text('description');
    $table->enum('status', ['idea', 'in_progress', 'review', 'done', 'failed'])->default('idea'); // ENUM
    $table->string('agent_type')->nullable();
    $table->string('model')->nullable();
    $table->text('agent_output')->nullable();
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();

    $table->index('status');
});
```

New state (remove enum, use string):

```php
Schema::create('tasks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->foreignId('worktree_id')->nullable()->constrained()->nullOnDelete();
    $table->string('title');
    $table->text('description');
    $table->string('status')->default('queued'); // Changed from enum to string, new default
    $table->string('agent_type')->nullable();
    $table->string('model')->nullable();
    $table->text('agent_output')->nullable();
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();

    $table->index('status');
});
```

**3. `2026_01_14_013401_create_worktrees_table.php`**

Current state:

```php
Schema::create('worktrees', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->string('branch_name');
    $table->string('path')->unique();
    $table->string('preview_url');
    $table->enum('status', ['creating', 'active', 'error', 'cleaning_up', 'deleted'])->default('creating'); // ENUM
    $table->enum('database_isolation', ['separate', 'prefix', 'shared'])->default('separate'); // ENUM
    $table->text('error_message')->nullable();
    $table->json('env_overrides')->nullable();
    $table->timestamps();

    $table->index('branch_name');
    $table->index('status');
});
```

New state (remove enums, use strings):

```php
Schema::create('worktrees', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->string('branch_name');
    $table->string('path')->unique();
    $table->string('preview_url');
    $table->string('status')->default('creating'); // Changed from enum to string
    $table->string('database_isolation')->default('separate'); // Changed from enum to string
    $table->text('error_message')->nullable();
    $table->json('env_overrides')->nullable();
    $table->timestamps();

    $table->index('branch_name');
    $table->index('status');
});
```

**4. `2026_01_17_175758_create_brainstorms_table.php`**

Current state:

```php
Schema::create('brainstorms', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->text('user_context')->nullable();
    $table->json('ideas')->nullable();
    $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending'); // ENUM
    $table->text('error_message')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();

    $table->index(['project_id', 'status', 'created_at']);
});
```

New state (remove enum, use string):

```php
Schema::create('brainstorms', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->text('user_context')->nullable();
    $table->json('ideas')->nullable();
    $table->string('status')->default('pending'); // Changed from enum to string
    $table->text('error_message')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();

    $table->index(['project_id', 'status', 'created_at']);
});
```

**5. `2026_01_17_051116_create_agent_settings_table.php`**

Current state:

```php
Schema::create('agent_settings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->string('default_agent')->default('claude-code');
    $table->text('claude_code_api_key')->nullable();
    $table->text('opencode_api_key')->nullable();
    $table->timestamp('claude_code_last_tested_at')->nullable();
    $table->timestamp('opencode_last_tested_at')->nullable();
    $table->timestamps();

    $table->unique('project_id');
});
```

New state (remove API key columns that were deleted in later migration):

```php
Schema::create('agent_settings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->string('default_agent')->default('claude-code');
    $table->timestamps();

    $table->unique('project_id');
});
```

### Migrations to Keep Unchanged

These migrations are fine as-is:

- `0001_01_01_000000_create_users_table.php`
- `0001_01_01_000001_create_cache_table.php`
- `0001_01_01_000002_create_jobs_table.php`
- `2025_08_26_100418_add_two_factor_columns_to_users_table.php`
- `2026_01_14_013403_create_commits_table.php`
- `2026_01_14_013403_create_specs_table.php`

### Database Reset

After making all changes:

```bash
php artisan migrate:fresh
```

This will drop all tables and re-run migrations from scratch.

## Acceptance Criteria

- [ ] All database `enum()` usages are replaced with `string()` columns
- [ ] Migration `2026_01_17_054229_update_tasks_status_column.php` is deleted
- [ ] Migration `2026_01_16_045634_add_server_settings_to_projects_table.php` is deleted
- [ ] Migration `2026_01_18_031009_update_projects_remove_server_drivers.php` is deleted
- [ ] Migration `2026_01_18_033337_remove_agent_api_keys_and_opencode.php` is deleted
- [ ] `create_projects_table` migration includes all project columns from the start
- [ ] `create_tasks_table` migration uses string status column with 'queued' default
- [ ] `create_worktrees_table` migration uses string columns instead of enums
- [ ] `create_brainstorms_table` migration uses string status column
- [ ] `create_agent_settings_table` migration doesn't include API key columns
- [ ] `php artisan migrate:fresh` runs successfully
- [ ] All tests pass after migration refresh
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location**: `tests/Feature/Database/MigrationsTest.php`

**Key test cases**:

- Test that all migrations run successfully with `migrate:fresh`
- Test that projects table has all expected columns
- Test that tasks.status is a string column, not enum
- Test that worktrees.status is a string column, not enum
- Test that worktrees.database_isolation is a string column, not enum
- Test that brainstorms.status is a string column, not enum
- Test that agent_settings table doesn't have API key columns

### Manual Verification

After running `php artisan migrate:fresh`:

1. Verify database schema using `php artisan schema:dump`
2. Check that no enum columns exist in any tables
3. Verify all column defaults are set correctly
4. Run full test suite to ensure nothing broke

## Code Formatting

Format all code using:

- **Backend (PHP)**: Laravel Pint
    - Command: `vendor/bin/pint --dirty`

## Additional Notes

### Why Remove Enums?

Database enums have several drawbacks:

- Difficult to modify (requires ALTER TABLE)
- Not all databases support them the same way
- Can't easily add/remove values in migrations
- String columns are more flexible and portable
- Performance difference is negligible for most use cases

### Status Value Changes

The `tasks` table status values are being updated:

- Old: `idea`, `in_progress`, `review`, `done`, `failed`
- New: `queued`, `in_progress`, `waiting_review`, `done`
- New default: `queued` (was `idea`)

Make sure Task model uses the new status values in:

- Factories
- Seeders
- Status constants/enums in PHP code
- Frontend TypeScript types

### Migration Timestamp Order

Keep the same timestamp order for existing migrations that aren't being deleted. The order matters for foreign key constraints.

### Backup Consideration

Since we're running `migrate:fresh`, all data will be lost. This is fine for development, but ensure:

- Development database can be safely wiped
- No production data is affected
- Team members are aware of the reset

### Future Migrations

Going forward:

- Never use database enums
- Use string columns with validation in application code
- Consider using PHP enums for type safety in the application layer
- Update documentation to reflect this convention
