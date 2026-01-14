---
name: project-management
description: CRUD operations for managing Laravel projects in Sage
depends_on: database-and-models
---

## Detailed Description

This feature provides the ability to add, view, update, and delete Laravel projects in Sage. Each project represents a Laravel application that Sage will manage, including its worktrees, tasks, and preview URLs.

### Key Capabilities
- Add new Laravel projects by selecting a directory
- Validate that the selected directory is a valid Laravel project
- Configure server driver (Caddy/Nginx) per project
- Set base URL for preview generation
- List all registered projects
- Edit project settings
- Delete projects (with confirmation)
- View project details and statistics (worktree count, task count, etc.)

### User Stories
1. As a developer, I want to register my Laravel project with Sage so I can use its features
2. As a developer, I want to configure which server driver my project uses
3. As a developer, I want to see all my registered projects in one dashboard
4. As a developer, I want to remove old projects that I'm no longer working on

## Detailed Implementation Plan

### Step 1: Create Controller and Routes
```bash
php artisan make:controller ProjectController --no-interaction
```

Define routes in `routes/web.php`:
- `GET /projects` - List all projects (Inertia page)
- `POST /projects` - Create new project
- `GET /projects/{project}` - Show project details
- `PATCH /projects/{project}` - Update project
- `DELETE /projects/{project}` - Delete project

### Step 2: Create Form Request Validation
```bash
php artisan make:request StoreProjectRequest --no-interaction
php artisan make:request UpdateProjectRequest --no-interaction
```

**Validation Rules:**
- `name` - required, string, max:255
- `path` - required, string, must exist as directory, must contain valid Laravel project
- `server_driver` - required, in:caddy,nginx
- `base_url` - required, string, valid domain format

### Step 3: Create Project Validator Service
```bash
php artisan make:class Services/ProjectValidator --no-interaction
```

Implement validation logic:
- Check if path exists
- Check if `composer.json` exists and contains `laravel/framework`
- Check if `.env` file exists
- Verify path is absolute, not relative

### Step 4: Create Inertia Pages (React + shadcn/ui)

**Pages to Create:**
- `resources/js/Pages/Projects/Index.tsx` - Project list with cards
- `resources/js/Pages/Projects/Show.tsx` - Project details dashboard
- `resources/js/Pages/Projects/Create.tsx` - New project form
- `resources/js/Pages/Projects/Edit.tsx` - Edit project form

**UI Components:**
- Project card showing name, path, worktree count, active tasks
- Form with file picker for project path
- Server driver radio buttons (Caddy/Nginx)
- Base URL input with validation
- Delete confirmation dialog

### Step 5: Implement Controller Actions

**Index Method:**
- Fetch all projects with counts (worktrees, tasks)
- Return Inertia view with projects data

**Store Method:**
- Validate request using `StoreProjectRequest`
- Validate Laravel project using `ProjectValidator`
- Create project record
- Redirect to project show page with success message

**Show Method:**
- Load project with relationships (worktrees, tasks, specs)
- Return Inertia view with project details

**Update Method:**
- Validate request using `UpdateProjectRequest`
- Update project record
- Return success response

**Destroy Method:**
- Check if project has active worktrees
- If yes, require confirmation or prevent deletion
- Delete project (cascade to worktrees, tasks via foreign keys)
- Redirect to projects index

### Step 6: Add Wayfinder Routes
```bash
php artisan wayfinder:generate
```

Import and use typed routes in React components:
```typescript
import { index, store, show } from '@/actions/App/Http/Controllers/ProjectController'
```

### Step 7: Create Feature Tests

Test coverage:
- `it('can list all projects')`
- `it('can create a new project with valid path')`
- `it('rejects invalid Laravel project paths')`
- `it('can update project settings')`
- `it('can delete project without worktrees')`
- `it('prevents deleting project with active worktrees')`
- `it('validates base_url format correctly')`

### Step 8: Create Browser Tests (Pest Browser)

E2E test coverage:
- `it('can create a project through the UI')`
- `it('shows validation errors in the form')`
- `it('can navigate to project details')`
- `it('can delete a project with confirmation')`

### Step 9: Format Code
```bash
vendor/bin/pint --dirty
```
