---
name: custom-guidelines-refactor
description: Refactor Context Files page to Custom Guidelines for managing .ai/guidelines/ directory
depends_on: null
---

## Feature Description

Currently, the application has a "Context Files" page at `/projects/{project}/context` that manages markdown files in the `.ai/` directory at the project root. According to Laravel's AI documentation, the recommended approach is to use the `.ai/guidelines/` directory for custom AI guidelines that help AI agents understand project-specific conventions.

This feature will:
- Rename "Context Files" to "Custom Guidelines" throughout the application
- Change the managed directory from `.ai/` to `.ai/guidelines/`
- Update all routes, controllers, actions, and frontend components to reflect this change
- Maintain backward compatibility by keeping the existing file management functionality
- Support both `.md` and `.blade.php` files as per Laravel Boost documentation
- Update UI to reflect the new purpose (managing AI guidelines rather than generic context)

## Implementation Plan

### Backend Components

**Controllers:**
- Rename `ContextController` to `GuidelineController`
- Update route model binding parameter from `{file}` to `{guideline}`
- Update all method names and variable names to use "guideline" terminology

**Actions:**
- Rename `app/Actions/Context/` directory to `app/Actions/Guideline/`
- Rename all action classes:
  - `ListContextFiles` → `ListGuidelines`
  - `ReadContextFile` → `ReadGuideline`
  - `WriteContextFile` → `WriteGuideline`
  - `DeleteContextFile` → `DeleteGuideline`
  - `AggregateContextFiles` → `AggregateGuidelines`
- Update all actions to work with `.ai/guidelines/` directory instead of `.ai/`
- Update `ListGuidelines` to support both `.md` and `.blade.php` extensions

**Form Requests:**
- Rename `StoreContextFileRequest` to `StoreGuidelineRequest`
- Update validation rules if needed to support both `.md` and `.blade.php` extensions

**Routes:**
- Update route names from `projects.context.*` to `projects.guidelines.*`
- Update route paths from `/context` to `/guidelines`
- Update route parameter from `{file}` to `{guideline}`

### Frontend Components

**Pages to Rename/Update:**
- `resources/js/pages/projects/context/index.tsx` → `resources/js/pages/projects/guidelines/index.tsx`
- `resources/js/pages/projects/context/create.tsx` → `resources/js/pages/projects/guidelines/create.tsx`
- `resources/js/pages/projects/context/show.tsx` → `resources/js/pages/projects/guidelines/show.tsx`
- `resources/js/pages/projects/context/edit.tsx` → `resources/js/pages/projects/guidelines/edit.tsx`

**UI Updates:**
- Change all "Context Files" references to "Custom Guidelines"
- Update breadcrumbs, page titles, and navigation
- Update help text to explain AI guidelines purpose (following Laravel Boost docs)
- Add support for creating both `.md` and `.blade.php` files (dropdown selector)
- Update empty state messaging to encourage users to create guidelines

**Navigation:**
- Update sidebar/navigation link from "Context" to "Guidelines"
- Update any tooltips or help text

### Directory Structure

**Before:**
```
project-root/
├── .ai/
│   ├── file1.md
│   └── file2.md
```

**After:**
```
project-root/
├── .ai/
│   └── guidelines/
│       ├── api-conventions.md
│       ├── architecture.md
│       └── testing-standards.blade.php
```

## Acceptance Criteria

- [ ] `ContextController` is renamed to `GuidelineController`
- [ ] All actions in `app/Actions/Context/` are moved to `app/Actions/Guideline/` and renamed
- [ ] `StoreContextFileRequest` is renamed to `StoreGuidelineRequest`
- [ ] All routes are updated from `/context` to `/guidelines` and `projects.context.*` to `projects.guidelines.*`
- [ ] All frontend pages are moved from `projects/context/` to `projects/guidelines/`
- [ ] All UI references to "Context Files" are changed to "Custom Guidelines"
- [ ] Actions now work with `.ai/guidelines/` directory instead of `.ai/`
- [ ] `ListGuidelines` supports both `.md` and `.blade.php` file extensions
- [ ] File creation allows selecting between `.md` and `.blade.php` extensions
- [ ] Help text explains AI guidelines purpose (referencing Laravel Boost)
- [ ] Empty state encourages creating guidelines with helpful examples
- [ ] Navigation is updated to show "Guidelines" instead of "Context"
- [ ] All existing tests are updated and passing
- [ ] New tests verify `.ai/guidelines/` directory management
- [ ] Code is formatted using Laravel Pint and Prettier

## Testing Strategy

### Feature Tests

**Test file location:** `tests/Feature/Http/Controllers/GuidelineControllerTest.php`

**Key test cases:**
- Test index page lists guidelines from `.ai/guidelines/` directory
- Test create page renders successfully
- Test store action creates guideline in `.ai/guidelines/` directory
- Test store supports both `.md` and `.blade.php` extensions
- Test show page displays guideline content
- Test edit page renders with existing content
- Test update action modifies guideline file
- Test delete action removes guideline file
- Test aggregate action combines guidelines
- Test guidelines are listed in alphabetical order
- Test only `.md` and `.blade.php` files are shown

### Browser Tests

**Test file location:** `tests/Browser/GuidelinesManagementTest.php`

**Key test cases:**
- Test user can create a new `.md` guideline
- Test user can create a new `.blade.php` guideline
- Test user can edit existing guideline
- Test user can delete guideline
- Test user can view guideline content
- Test empty state shows helpful message
- Test navigation shows "Guidelines" link
- Test page title shows "Custom Guidelines"

### Unit Tests

**Test file location:** `tests/Unit/Actions/Guideline/ListGuidelinesTest.php`

**Key test cases:**
- Test lists all `.md` files from `.ai/guidelines/`
- Test lists all `.blade.php` files from `.ai/guidelines/`
- Test returns empty collection when directory doesn't exist
- Test files are sorted alphabetically
- Test ignores non-markdown/blade files

**Test file location:** `tests/Unit/Actions/Guideline/WriteGuidelineTest.php`

**Key test cases:**
- Test creates `.ai/guidelines/` directory if it doesn't exist
- Test writes `.md` file correctly
- Test writes `.blade.php` file correctly
- Test prevents path traversal attacks
- Test validates file extensions

## Code Formatting

Format all code using:
- **Backend (PHP):** Laravel Pint - `vendor/bin/pint --dirty`
- **Frontend (TypeScript/React):** Prettier - `npx prettier --write resources/js/pages/projects/guidelines/**/*.tsx`

## Additional Notes

### Design Considerations

1. **Laravel Boost Alignment**: This refactoring aligns with Laravel's official AI documentation. The `.ai/guidelines/` directory is the recommended location for project-specific AI guidelines that work with Laravel Boost and other AI tools.

2. **File Extensions**: Support both `.md` and `.blade.php` extensions as recommended by Laravel Boost docs. Blade templates allow dynamic content generation if needed in the future.

3. **Backward Compatibility**: While we're changing the directory structure, the file management functionality remains the same. Existing markdown files in `.ai/` won't be automatically migrated (that would be a separate migration feature if needed).

4. **User Education**: Update UI messaging to explain what custom guidelines are for and how they help AI agents. Reference examples from Laravel Boost docs:
   - API conventions
   - Architecture decisions
   - Testing standards
   - Domain-specific terminology
   - Team coding standards

5. **Empty State Improvements**: When no guidelines exist, show a helpful empty state with:
   - Brief explanation of what custom guidelines are
   - Link to Laravel Boost documentation
   - Example use cases (API conventions, architecture docs, etc.)
   - Prominent "Create Guideline" button

### Implementation Strategy

**Phase 1: Backend Refactoring**
1. Rename and move all Actions classes
2. Rename Controller
3. Update Form Request
4. Update routes
5. Update actions to use `.ai/guidelines/` directory
6. Update `ListGuidelines` to support both extensions

**Phase 2: Frontend Refactoring**
1. Rename page directories and files
2. Update all component imports
3. Update UI text from "Context Files" to "Custom Guidelines"
4. Add file extension selector in create form
5. Update empty states with guideline-specific messaging

**Phase 3: Testing**
1. Rename and update all existing tests
2. Add new tests for `.blade.php` support
3. Add browser tests for new UI features
4. Ensure all tests pass

**Phase 4: Final Polish**
1. Run Pint and Prettier
2. Update navigation
3. Review help text and tooltips
4. Test end-to-end workflow

### Migration Considerations

**Note**: This feature does NOT automatically migrate existing files from `.ai/` to `.ai/guidelines/`. If a migration is desired, that would be a separate feature with:
- Detection of existing `.ai/*.md` files
- User confirmation prompt
- Automated move operation
- Rollback capability

For now, users can manually move files or recreate them in the new location.

### Security Considerations

- Maintain existing path traversal prevention
- Validate file extensions (only `.md` and `.blade.php`)
- Ensure guidelines directory is within project root
- Don't execute or render Blade templates in preview (security risk)
- Blade files are for AI consumption, not application rendering

### Performance Considerations

- Directory listing should remain efficient (small number of files expected)
- No changes needed to caching or optimization
- Consider adding file count limit if needed (e.g., max 100 guidelines)

### Breaking Changes

**Route Changes:**
- `/projects/{project}/context` → `/projects/{project}/guidelines`
- Route names: `projects.context.*` → `projects.guidelines.*`

**Class Renames:**
- `ContextController` → `GuidelineController`
- `StoreContextFileRequest` → `StoreGuidelineRequest`
- All action classes renamed (Context → Guideline)

**Directory Change:**
- Managed directory: `.ai/` → `.ai/guidelines/`

These are internal changes and won't affect end users unless they have bookmarks or direct links to the old routes.
