---
name: remove-cost-tracking
description: Remove cost tracking functionality and 'Costs' link from sidebar
depends_on: null
---

## Feature Description

This feature removes the cost tracking dashboard and all related backend functionality from Sage. The cost tracking system was designed to monitor API usage and calculate costs for Claude API calls, but it's not needed at the moment.

This includes:

- Removing the "Costs" navigation link from the sidebar
- Removing the cost tracking dashboard page
- Removing all backend components (models, controllers, actions, resources)
- Dropping the `api_usages` table from the database
- Removing all cost tracking tests

**Note**: The `RecordApiUsage` action is currently used by `SpecController`. Since the Spec functionality is also being removed in a separate feature, we can safely remove all cost tracking code.

## Implementation Plan

### Backend Components

**Models to Remove**:

- `app/Models/ApiUsage.php`
- Remove `api_usages` relationship from `app/Models/Project.php` (if exists)
- Remove `api_usages` relationship from `app/Models/Task.php` (if exists)

**Controllers to Remove**:

- `app/Http/Controllers/CostTrackingController.php`

**Actions to Remove**:

- `app/Actions/Cost/RecordApiUsage.php`
- `app/Actions/Cost/CalculateCost.php`
- Remove the entire `app/Actions/Cost/` directory

**Resources to Remove**:

- `app/Http/Resources/ApiUsageResource.php`

**Database Changes**:

- Create migration to drop `api_usages` table
- Keep existing migration file in history (don't delete migration files)

**Factories to Remove**:

- `database/factories/ApiUsageFactory.php`

**Routes to Remove**:

- Remove cost tracking route in `routes/web.php`:
    - `projects.costs.index` (GET `/projects/{project}/costs`)

**Code References to Update**:

- Remove `RecordApiUsage` usage from `app/Http/Controllers/SpecController.php`:
    - Remove constructor injection of `RecordApiUsage`
    - Remove `recordUsage()` method
    - Remove calls to `recordUsage()` in `generate()` and `refine()` methods
- Remove `RecordApiUsage` usage from `app/Jobs/Agent/RunAgent.php` (if used)

### Frontend Components

**Pages to Remove**:

- `resources/js/pages/projects/costs/index.tsx`
- Remove the entire `resources/js/pages/projects/costs/` directory

**Wayfinder Actions to Remove**:

- `resources/js/actions/App/Http/Controllers/CostTrackingController.ts`

**TypeScript Types to Update**:

- Remove `ApiUsage` type from `resources/js/types/index.d.ts` (if exists)

**Routing Updates**:

- Remove "Costs" navigation link from `resources/js/components/layout/app-sidebar.tsx`

### Configuration/Infrastructure

**No changes required** - Cost tracking didn't require any special configuration.

### Tests to Remove

**Unit Tests**:

- `tests/Unit/Models/ApiUsageTest.php`
- `tests/Unit/Actions/Cost/CalculateCostTest.php` (if exists)
- `tests/Unit/Actions/Cost/RecordApiUsageTest.php` (if exists)

**Feature Tests**:

- `tests/Feature/CostTracking/CostTrackingControllerTest.php`
- `tests/Feature/CostTracking/RecordApiUsageTest.php`
- `tests/Feature/CostTracking/CalculateCostTest.php`
- `tests/Feature/CostTracking/SpecControllerCostTrackingTest.php`
- Remove the entire `tests/Feature/CostTracking/` directory

**Browser Tests**:

- `tests/Browser/CostTracking/CostTrackingPageTest.php`
- `tests/Browser/CostTracking/CostTrackingNavigationTest.php`
- Remove the entire `tests/Browser/CostTracking/` directory

### Automaker Features to Archive

**Move to archive** (don't delete, for historical reference):

- `.automaker/features/cost-tracking-dashboard/`
- `.automaker/features/export-reports/` (if related to cost tracking)

## Acceptance Criteria

- [ ] `api_usages` table is dropped via migration
- [ ] All ApiUsage model references are removed from the codebase
- [ ] "Costs" link is removed from sidebar navigation
- [ ] Cost tracking dashboard page is not accessible
- [ ] All cost tracking routes return 404
- [ ] SpecController no longer references RecordApiUsage (or is removed entirely)
- [ ] All cost-related files are deleted (models, controllers, actions, resources, factories)
- [ ] All cost-related tests are deleted
- [ ] All cost-related frontend components are deleted
- [ ] TypeScript types are updated to remove ApiUsage references
- [ ] All tests pass after removal
- [ ] Code is formatted with Pint and Prettier
- [ ] Application runs without errors after removal
- [ ] No broken links in the UI
- [ ] RunAgent job no longer attempts to record API usage (if applicable)

## Testing Strategy

### Feature Tests

**Test file location**: `tests/Feature/Removal/CostTrackingRemovedTest.php`

Create a new test to verify removal:

- Verify `api_usages` table does not exist in database
- Verify cost tracking route returns 404
- Verify no cost-related classes exist in codebase
- Verify SpecController does not reference RecordApiUsage

### Manual Testing Checklist

After implementation:

- [ ] Visit dashboard - no "Costs" link visible in sidebar
- [ ] Try to access `/projects/1/costs` - should 404
- [ ] Run migrations on fresh database - no `api_usages` table created
- [ ] Check sidebar navigation - no "Costs" menu item
- [ ] Run full test suite - all tests pass
- [ ] Verify spec generation still works (if not removed yet)

## Code Formatting

**PHP Code**: `vendor/bin/pint`
**Frontend Code**: `pnpm run format` (Prettier)

Commands to run:

```bash
vendor/bin/pint
pnpm run format
```

## Migration Strategy

### Database Migration

Create new migration to drop the `api_usages` table:

```php
// database/migrations/YYYY_MM_DD_HHMMSS_drop_api_usages_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('api_usages');
    }

    public function down(): void
    {
        Schema::create('api_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('source');
            $table->string('model');
            $table->integer('input_tokens');
            $table->integer('output_tokens');
            $table->integer('cache_creation_input_tokens')->nullable();
            $table->integer('cache_read_input_tokens')->nullable();
            $table->decimal('estimated_cost', 10, 6);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
            $table->index(['task_id', 'created_at']);
        });
    }
};
```

### Execution Order

1. Create and run migration to drop `api_usages` table
2. Remove backend code (controllers, models, actions, resources)
3. Remove frontend code (pages, components, types)
4. Remove tests
5. Update routes
6. Update sidebar navigation
7. Update SpecController to remove RecordApiUsage usage (or wait for spec removal feature)
8. Run tests
9. Format code
10. Verify application functionality

## Additional Notes

### Why Remove Cost Tracking?

1. **Not needed at the moment**: Cost tracking adds complexity without immediate value
2. **Can be re-added later**: If needed in the future, the feature can be rebuilt with updated requirements
3. **Simplify codebase**: Less code to maintain and test
4. **Reduce API overhead**: No need to calculate and store costs for every API call

### Related Feature Removal

The `remove-spec-functionality` feature also removes spec-related code that uses `RecordApiUsage`. The two features can be implemented in either order:

- **Option 1**: Remove cost tracking first → Update SpecController to remove cost tracking → Then remove Spec functionality
- **Option 2**: Remove Spec functionality first → Then remove cost tracking (no SpecController updates needed)

**Recommended**: Implement both features in parallel or implement cost tracking removal first.

### Data Consideration

Since this is cost tracking data (can be regenerated if needed), data loss is acceptable. No export functionality is necessary.

### Future Re-implementation

If cost tracking is needed in the future, consider:

- Using a third-party service for cost tracking
- Implementing it as an optional feature toggle
- Storing only aggregated data instead of every API call
- Using Laravel Pulse or similar for metrics instead of custom implementation

### References to Update in Documentation

- Update README.md to remove "Cost tracking" from nice-to-have features (or mark as removed)
- Remove any documentation mentioning the cost tracking dashboard
- No need to document new workflow since feature is being removed entirely
