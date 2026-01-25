# App/Actions guidelines

- This application uses the Action pattern and prefers for much logic to live in reusable and composable Action classes.
- Actions live in `app/Actions`, they are named based on what they do, with no suffix.
- Actions will be called from many different places: jobs, commands, HTTP requests, API requests, MCP requests, and more.
- Create dedicated Action classes for business logic with a single `handle()` method.
- Create new actions with `php artisan make:action "{name}" --no-interaction`
- Wrap complex operations in `DB::transaction()` within actions when multiple models are involved.

## When to Use Actions

Use actions for any interaction or mutation of data and system state - anything the application has control over:

- **Database mutations**: Creating, updating, or deleting records
- **Configuration changes**: Modifying application settings or user preferences
- **File system operations**: Creating, moving, or deleting files
- **External API calls**: Interacting with third-party service classes via Service classes
- **Complex business logic**: Operations involving multiple steps or models
- **System state changes**: Updating cache, queues, or other system components

Actions provide a single, testable, reusable entry point for these operations across the application.
