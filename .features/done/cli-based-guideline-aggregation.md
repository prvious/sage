---
name: cli-based-guideline-aggregation
description: Execute boost:update command directly in project directory instead of using Artisan facade
depends_on: null
---

## Feature Description

Update the `AggregateGuidelines` action to execute `php artisan boost:update` directly in the project's root directory using `Process` instead of Laravel's `Artisan` facade. This ensures the command runs in the correct project context with proper environment variables and working directory.

### Why This Change?

The current implementation uses `Artisan::call()` which runs the command in Sage's application context, not in the target project's context. Since `boost:update` needs to aggregate guidelines from the project's `.ai/guidelines/` directory and update the project's `CLAUDE.md` file, it must run in the project's root directory with the project's environment.

## Implementation Plan

### Backend Components

- **Actions**: Modify `app/Actions/Guideline/AggregateGuidelines.php`
    - Remove dependency on `Illuminate\Support\Facades\Artisan`
    - Add dependency on `Illuminate\Support\Facades\Process`
    - Change from `Artisan::call()` to `Process::path()->run()`
    - Set working directory to `$project->path`
    - Pass system environment variables using `getenv()`
    - Maintain same return structure: `['exit_code', 'output', 'success']`

- **Controllers**: No changes to `GuidelineController`
    - Controller already handles action results correctly

### Configuration/Infrastructure

- Working directory: Set to `$project->path` for process execution
- Environment variables: Use `getenv()` to pass system environment to subprocess
- Command: `php artisan boost:update` (relative to project path)
- Timeout: Add appropriate timeout (e.g., 60 seconds) for command execution

## Acceptance Criteria

- [ ] `AggregateGuidelines` action executes `php artisan boost:update` in the project's root directory
- [ ] Process runs with the project's working directory set correctly
- [ ] System environment variables are passed to the subprocess
- [ ] Command output is captured and returned in the response
- [ ] Exit code is captured and returned in the response
- [ ] Error output is captured if command fails
- [ ] Timeout is configured to prevent hanging processes
- [ ] Action throws exception with helpful message if command fails
- [ ] All existing tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Unit Tests

**Test file location**: `tests/Unit/Actions/Guideline/AggregateGuidelinesTest.php`

**Key test cases**:

- Test successful execution returns correct structure
- Test command runs in project's working directory
- Test system environment variables are passed
- Test timeout is applied
- Test error handling when command fails
- Test exception when boost:update command doesn't exist

**Implementation approach**:

```php
use Illuminate\Support\Facades\Process;
use function Pest\Laravel\mock;

it('executes boost:update in project directory', function () {
    Process::fake([
        'php artisan boost:update' => Process::result(
            output: 'Guidelines aggregated successfully',
            exitCode: 0
        ),
    ]);

    $project = Project::factory()->create(['path' => '/path/to/project']);
    $action = new AggregateGuidelines;

    $result = $action->handle($project);

    expect($result['success'])->toBeTrue();
    expect($result['exit_code'])->toBe(0);
    expect($result['output'])->toContain('Guidelines aggregated successfully');

    Process::assertRan(function ($command, $path) use ($project) {
        return $command === 'php artisan boost:update'
            && $path === $project->path;
    });
});

it('captures error output when command fails', function () {
    Process::fake([
        'php artisan boost:update' => Process::result(
            errorOutput: 'Command failed: File not found',
            exitCode: 1
        ),
    ]);

    $project = Project::factory()->create(['path' => '/path/to/project']);
    $action = new AggregateGuidelines;

    $result = $action->handle($project);

    expect($result['success'])->toBeFalse();
    expect($result['exit_code'])->toBe(1);
    expect($result['output'])->toContain('Command failed');
});
```

### Feature Tests

**Test file location**: `tests/Feature/Guideline/GuidelineControllerTest.php`

**Key test cases**:

- Test aggregate endpoint executes command successfully
- Test aggregate endpoint handles command failure
- Test aggregate endpoint returns proper redirect and flash messages

**Implementation approach**:

```php
it('aggregates guidelines using CLI command', function () {
    Process::fake([
        'php artisan boost:update' => Process::result(
            output: 'Guidelines aggregated',
            exitCode: 0
        ),
    ]);

    $project = Project::factory()->create();

    $response = $this->post(route('projects.guidelines.aggregate', $project));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Guidelines aggregated successfully.');

    Process::assertRan('php artisan boost:update');
});
```

## Code Formatting

Format all code using: **Laravel Pint**

Command to run: `vendor/bin/pint --dirty`

## Additional Notes

### System Environment Guidelines

This change aligns with the `.ai/system-environment` guidelines:

- **Use System Environment**: The command will run with system environment variables via `getenv()`
- **Process Execution**: Using `Process::path()->env(getenv())->run()` pattern
- **External Dependencies**: The command executes in the target project's context, not Sage's context

### Implementation Example

```php
use Illuminate\Support\Facades\Process;

final readonly class AggregateGuidelines
{
    public function handle(Project $project): array
    {
        $result = Process::path($project->path)
            ->timeout(60)
            ->env(getenv())
            ->run('php artisan boost:update');

        if (!$result->successful()) {
            throw new RuntimeException(
                'Failed to aggregate guidelines: ' . $result->errorOutput()
            );
        }

        return [
            'exit_code' => $result->exitCode(),
            'output' => $result->output(),
            'success' => $result->successful(),
        ];
    }
}
```

### Error Scenarios

1. **Command doesn't exist**: Should throw clear exception
2. **Project path doesn't exist**: Process will fail with clear error
3. **Timeout**: Command takes too long and is killed
4. **Permission issues**: Command fails with permission error

### Performance Considerations

- Timeout set to 60 seconds (aggregation should be fast)
- Command runs synchronously (blocking)
- Consider adding progress feedback for long-running aggregations (future enhancement)

### Security Considerations

- Command runs in project's context with project's permissions
- System environment variables are passed (safe for local execution)
- No user input is passed to the command (no injection risk)
- Project path comes from database (trusted source)
