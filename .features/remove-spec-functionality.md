---
name: remove-spec-functionality
description: Remove all Spec-related code and rely on Laravel Boost guidelines and Laravel Roster
depends_on: null
---

## Feature Description

This feature removes the entire "Spec" functionality from Sage, which currently allows users to generate AI-powered feature specifications that can be converted to tasks. Instead, Sage will rely on:

1. **Laravel Boost's custom guidelines** stored in the `.ai/` directory for project-specific conventions
2. **Laravel Roster** (https://github.com/laravel/roster) for gathering comprehensive project information
3. Users can use the `/features-plan` Claude Code skill directly for feature planning needs

The Spec system was an intermediary step between an idea and a task, but it adds unnecessary complexity when users can directly use Claude Code's feature planning capabilities with proper project context from Laravel Boost and Roster.

## Implementation Plan

### Backend Components

**Models to Remove**:

- `app/Models/Spec.php`
- Cascade delete relationship in `app/Models/Project.php` (specs relationship)
- Spec relationship and `spec_id` column in `app/Models/Task.php`

**Controllers to Remove**:

- `app/Http/Controllers/SpecController.php` (entire controller with all CRUD operations)

**Actions to Remove**:

- `app/Actions/Spec/GenerateTaskFromSpec.php`
- Remove the entire `app/Actions/Spec/` directory

**Services to Remove**:

- `app/Services/SpecGeneratorService.php`

**Support Classes to Remove**:

- `app/Support/SpecPrompts.php`

**Resources to Remove**:

- `app/Http/Resources/SpecResource.php`

**Database Changes**:

- Create migration to drop `specs` table
- Create migration to remove `spec_id` column from `tasks` table
- Keep existing migrations in history (don't delete migration files)

**Factories to Remove**:

- `database/factories/SpecFactory.php`

**Routes to Remove**:

- All spec-related routes in `routes/web.php`:
    - `projects.specs.index`
    - `projects.specs.create`
    - `projects.specs.store`
    - `projects.specs.show`
    - `projects.specs.edit`
    - `projects.specs.update`
    - `projects.specs.destroy`
    - `projects.specs.generate`
    - `projects.specs.refine`
    - `projects.specs.preview-task`
    - `projects.specs.create-task`

### Frontend Components

**Pages to Remove**:

- `resources/js/pages/projects/specs/index.tsx`
- `resources/js/pages/projects/specs/create.tsx`
- `resources/js/pages/projects/specs/edit.tsx`
- `resources/js/pages/projects/specs/show.tsx`
- Remove the entire `resources/js/pages/projects/specs/` directory

**Components to Remove**:

- `resources/js/components/spec-to-task-dialog.tsx`

**Wayfinder Actions to Remove**:

- `resources/js/actions/App/Http/Controllers/SpecController.ts`

**TypeScript Types to Update**:

- Remove `Spec` type from `resources/js/types/index.d.ts`
- Remove `spec_id` from `Task` type in `resources/js/types/index.d.ts`

**Routing Updates**:

- Remove "Specs" navigation link from `resources/js/components/layout/app-sidebar.tsx`

### Configuration/Infrastructure

**No changes required** - Spec generation used the Anthropic API which is still used by other parts of the application.

### Tests to Remove

**Unit Tests**:

- `tests/Unit/Models/SpecTest.php`

**Feature Tests**:

- `tests/Feature/Specs/SpecGeneratorTest.php`
- `tests/Feature/Specs/SpecToTaskTest.php`
- `tests/Feature/CostTracking/SpecControllerCostTrackingTest.php` (only if it exclusively tests spec controller)

**Browser Tests**:

- `tests/Browser/Specs/SpecsPagesLayoutTest.php`
- `tests/Browser/Specs/SpecToTaskTest.php`
- Remove the entire `tests/Browser/Specs/` directory

### Automaker Features to Archive

**Move to archive** (don't delete, for historical reference):

- `.automaker/features/spec-to-task/`
- `.automaker/features/spec-collaboration/`
- `.automaker/features/spec-export/`
- `.automaker/features/spec-version-history/`

## Acceptance Criteria

- [ ] All Spec model references are removed from the codebase
- [ ] `specs` table is dropped via migration
- [ ] `spec_id` column is removed from `tasks` table via migration
- [ ] All spec-related routes return 404
- [ ] Sidebar navigation no longer shows "Specs" link
- [ ] No spec-related pages are accessible
- [ ] Task model no longer has spec relationship
- [ ] Project model no longer has specs relationship
- [ ] All spec-related files (controllers, models, services, actions, resources) are deleted
- [ ] All spec-related tests are deleted
- [ ] All spec-related frontend components are deleted
- [ ] TypeScript types are updated to remove Spec references
- [ ] All tests pass after removal
- [ ] Code is formatted with Pint and Prettier
- [ ] Application runs without errors after removal
- [ ] No broken links in the UI

## Testing Strategy

### Feature Tests

**Test file location**: `tests/Feature/Removal/SpecFunctionalityRemovedTest.php`

Create a new test to verify removal:

- Verify `specs` table does not exist in database
- Verify `spec_id` column does not exist in `tasks` table
- Verify all spec routes return 404
- Verify Task model does not have `spec_id` in fillable
- Verify Project model does not have `specs()` relationship method
- Verify no spec-related classes exist in codebase

### Manual Testing Checklist

After implementation:

- [ ] Visit dashboard - no spec links visible
- [ ] Try to access `/projects/1/specs` - should 404
- [ ] Create a new task - no spec_id field
- [ ] Run migrations on fresh database - no specs table created
- [ ] Check sidebar navigation - no "Specs" menu item
- [ ] Run full test suite - all tests pass

## Code Formatting

**PHP Code**: `vendor/bin/pint`
**Frontend Code**: `pnpm run format` (Prettier)
**TypeScript**: Automatically formatted with Prettier

Commands to run:

```bash
vendor/bin/pint
pnpm run format
```

## Migration Strategy

### Database Migrations

Create two new migrations:

1. **Remove spec_id from tasks table**:

```php
// database/migrations/YYYY_MM_DD_HHMMSS_remove_spec_id_from_tasks_table.php
public function up(): void
{
    Schema::table('tasks', function (Blueprint $table) {
        $table->dropForeign(['spec_id']); // if foreign key exists
        $table->dropColumn('spec_id');
    });
}

public function down(): void
{
    Schema::table('tasks', function (Blueprint $table) {
        $table->foreignId('spec_id')->nullable()->constrained()->cascadeOnDelete();
    });
}
```

2. **Drop specs table**:

```php
// database/migrations/YYYY_MM_DD_HHMMSS_drop_specs_table.php
public function up(): void
{
    Schema::dropIfExists('specs');
}

public function down(): void
{
    Schema::create('specs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('project_id')->constrained()->cascadeOnDelete();
        $table->string('title');
        $table->text('content');
        $table->boolean('generated_from_idea');
        $table->timestamps();
    });
}
```

### Execution Order

1. Create and run migrations (drop spec_id from tasks, then drop specs table)
2. Remove backend code (controllers, models, actions, services, resources)
3. Remove frontend code (pages, components, types)
4. Remove tests
5. Update routes
6. Update sidebar navigation
7. Run tests
8. Format code
9. Verify application functionality

## Additional Notes

### Why Remove Specs?

1. **Redundancy**: The `/features-plan` skill in Claude Code already provides comprehensive feature planning
2. **Simplicity**: One less concept for users to understand
3. **Maintenance**: Less code to maintain and test
4. **Better Tooling**: Laravel Boost + Roster provide richer project context than custom Spec generation
5. **Direct Workflow**: Users can go from idea → feature plan → task without intermediate "Spec" storage

### Laravel Roster Integration (Future)

When Laravel Roster becomes available:

- It will automatically analyze project structure
- Provide comprehensive project information (routes, models, controllers, etc.)
- Feed into Claude Code's feature planning capabilities
- No custom "Spec" system needed - Roster + Laravel Boost + `/features-plan` skill handle it all

### Data Migration Consideration

If users have existing specs they want to preserve:

- **Option 1**: Export existing specs to markdown files before running migrations
- **Option 2**: Create a one-time export command that dumps specs to `.features/` directory
- **Recommended**: Since specs can be regenerated using `/features-plan`, data loss is acceptable

### Breaking Changes

This is a **breaking change** for users who have existing specs stored in the database. Consider:

- Adding a notice in the changelog
- Providing export functionality before removal
- Documenting the new workflow (use `/features-plan` instead)

### References to Update in Documentation

- Update README.md to remove "Spec Generator" from dashboard views section
- Update any user documentation that mentions specs
- Add section explaining the new feature planning workflow using `/features-plan` skill
