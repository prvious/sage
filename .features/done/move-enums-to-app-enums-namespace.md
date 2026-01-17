---
name: move-enums-to-app-enums-namespace
description: Reorganize enums from App\ to App\Enums namespace and update all references
depends_on: null
---

## Feature Description

Refactor the codebase to move all PHP enums from the `App\` namespace to a dedicated `App\Enums\` namespace. This improves code organization by grouping all enum types in a single location, following Laravel conventions for organizing application code.

Currently, the `TaskStatus` enum is located at `app/TaskStatus.php` with namespace `App\`. This feature will:

1. Create the `app/Enums` directory
2. Move `TaskStatus.php` to `app/Enums/TaskStatus.php`
3. Update the namespace from `App\` to `App\Enums\`
4. Find and update all references throughout the codebase

## Implementation Plan

### Backend Components

**Directory Structure:**

- Create: `app/Enums/` directory
- Move: `app/TaskStatus.php` → `app/Enums/TaskStatus.php`
- Update namespace in: `app/Enums/TaskStatus.php`

**Files to Update (References):**

1. **Models:**
    - `app/Models/Task.php` (lines 41, 76, 84)
        - Update `casts()` method: `\App\TaskStatus::class` → `\App\Enums\TaskStatus::class`
        - Update `isRunning()` method: `\App\TaskStatus::InProgress` → `\App\Enums\TaskStatus::InProgress`
        - Update `scopeByStatus()` method: `\App\TaskStatus $status` → `\App\Enums\TaskStatus $status`

2. **Controllers:**
    - `app/Http/Controllers/TaskController.php` (line 26)
        - Update `store()` method: `\App\TaskStatus::Queued` → `\App\Enums\TaskStatus::Queued`

3. **Factories:**
    - `database/factories/TaskFactory.php` (lines 26, 41, 51, 64, 78)
        - Update default state and all factory states
        - Replace all `\App\TaskStatus::` → `\App\Enums\TaskStatus::`

4. **Tests:**
    - `tests/Feature/Dashboard/KanbanDataTest.php`
    - `tests/Browser/Dashboard/KanbanBoardTest.php`
    - `tests/Unit/Models/TaskTest.php`
    - Update all `\App\TaskStatus::` → `\App\Enums\TaskStatus::`
    - Add import statement: `use App\Enums\TaskStatus;`

### Configuration/Infrastructure

No environment variables or configuration changes required.

## Acceptance Criteria

- [ ] `app/Enums/` directory exists
- [ ] `TaskStatus` enum file moved to `app/Enums/TaskStatus.php`
- [ ] Namespace updated to `App\Enums` in TaskStatus.php
- [ ] Old `app/TaskStatus.php` file removed
- [ ] All model references updated to use `App\Enums\TaskStatus`
- [ ] All controller references updated to use `App\Enums\TaskStatus`
- [ ] All factory references updated to use `App\Enums\TaskStatus`
- [ ] All test references updated to use `App\Enums\TaskStatus`
- [ ] No `\App\TaskStatus` references remain in codebase
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Unit Tests

**Test file:** `tests/Unit/Models/TaskTest.php`

- Verify TaskStatus enum cast works correctly after namespace change
- Test model scopes using TaskStatus enum
- Test `isRunning()` method with TaskStatus::InProgress

### Feature Tests

**Test file:** `tests/Feature/Dashboard/KanbanDataTest.php`

- Verify Kanban dashboard data correctly uses TaskStatus enum
- Test filtering by status
- Test status transitions

**Test file:** `tests/Feature/Http/Controllers/TaskControllerTest.php` (if exists)

- Test task creation with default Queued status
- Test task updates with different statuses

### Browser Tests

**Test file:** `tests/Browser/Dashboard/KanbanBoardTest.php`

- Verify Kanban board renders correctly with updated enum references
- Test drag-and-drop status changes

### Verification Steps

After implementation, run:

1. `php artisan test --compact` - Ensure all tests pass
2. `vendor/bin/pint --dirty` - Verify code formatting
3. `grep -r "\\\\App\\\\TaskStatus" app/ tests/ database/` - Confirm no old references remain

## Code Formatting

Format all PHP code using Laravel Pint.

Commands to run:

```bash
vendor/bin/pint app/Enums/TaskStatus.php
vendor/bin/pint app/Models/Task.php
vendor/bin/pint app/Http/Controllers/TaskController.php
vendor/bin/pint database/factories/TaskFactory.php
vendor/bin/pint tests/
```

Or format all modified files:

```bash
vendor/bin/pint --dirty
```

## Additional Notes

### Search Strategy

Use the following grep patterns to find all references:

```bash
# Find all TaskStatus usages
grep -r "TaskStatus" app/ tests/ database/

# Find fully qualified references
grep -r "\\App\\TaskStatus" app/ tests/ database/

# Find use statements
grep -r "use App\\TaskStatus" app/ tests/ database/
```

### Migration Steps

1. Create `app/Enums` directory
2. Copy `TaskStatus.php` to `app/Enums/` and update namespace
3. Update all references using search and replace
4. Run tests to verify nothing broke
5. Remove old `app/TaskStatus.php` file
6. Run final tests and format code

### Future Considerations

If additional enums are added to the codebase in the future, they should be created directly in the `App\Enums\` namespace to maintain consistency.

### Potential Issues

- IDE auto-imports may need to be updated after the namespace change
- If using IDE refactoring tools, verify they catch all references including:
    - Enum casts in model `casts()` methods
    - Type hints in method signatures
    - Static enum case references (e.g., `TaskStatus::Queued`)
    - Factory default values and states
