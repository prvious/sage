# App/Services guidelines

- Service classes encapsulate interactions with external systems and third-party services.
- Services live in `app/Services`, they are named based on what they interact with, with a `Service` suffix.
- Services should be called from Action classes, not directly from controllers, jobs, or commands.
- Create new services with `php artisan make:class "Services/{name}Service"`
- Service classes must provide a `fake()` method for convenient testing.

## When to Use Services

Use service classes for:

- **External system interactions**: Git, Docker, system binaries via Process
- **Third-party API clients**: GitHub API, Anthropic API, external webhooks
- **Complex system operations**: File system operations, SSH connections
- **Process execution**: Running CLI commands and parsing output

## Testing with fake()

All service classes must implement a `fake()` method that:

- Mocks the underlying dependencies (e.g., `Process::fake()`, `Http::fake()`)
- Returns a mock instance for setting expectations
- Allows for assertions after the test completes
- Follows Laravel's facade mocking patterns

## Key Principles

1. **Single Responsibility**: Each service handles one external system or API
2. **Dependency Injection**: Inject dependencies (Process, Http, etc.) via constructor
3. **Testability**: Always provide a `fake()` method for easy mocking
4. **Consistency**: Follow Laravel's mocking patterns and conventions
5. **Type Safety**: Use proper type hints and return types
