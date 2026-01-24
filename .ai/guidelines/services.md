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

## Example: GitService

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Process\PendingProcess;
use Mockery;
use Mockery\MockInterface;

final readonly class GitService
{
    public function __construct(
        private ProcessFactory $process
    ) {}

    /**
     * Create a fake instance for testing.
     */
    public static function fake(array $commands = []): MockInterface
    {
        // Mock the underlying Process facade
        Process::fake($commands);

        // Create and bind a mock of this service
        $mock = Mockery::mock(GitService::class);
        app()->instance(GitService::class, $mock);

        return $mock;
    }

    public function status(string $path): array
    {
        $result = $this->process
            ->path($path)
            ->run('git status --porcelain');

        return $this->parseStatus($result->output());
    }

    public function commit(string $path, string $message): bool
    {
        $result = $this->process
            ->path($path)
            ->run(['git', 'commit', '-m', $message]);

        return $result->successful();
    }

    private function parseStatus(string $output): array
    {
        // Parse git status output...
        return [];
    }
}
```

## Example: Using GitService in an Action

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Services\GitService;

final readonly class CommitChanges
{
    public function __construct(
        private GitService $git
    ) {}

    public function handle(string $path, string $message): bool
    {
        // Get current status
        $status = $this->git->status($path);

        if (empty($status)) {
            return false;
        }

        // Commit changes
        return $this->git->commit($path, $message);
    }
}
```

## Example: Testing with fake()

```php
<?php

use App\Services\GitService;
use App\Actions\CommitChanges;

it('commits changes when files are modified', function () {
    // Mock the GitService
    $git = GitService::fake();

    // Set expectations
    $git->shouldReceive('status')
        ->once()
        ->with('/path/to/repo')
        ->andReturn(['modified' => ['file.txt']]);

    $git->shouldReceive('commit')
        ->once()
        ->with('/path/to/repo', 'Update file')
        ->andReturn(true);

    // Execute action
    $action = new CommitChanges($git);
    $result = $action->handle('/path/to/repo', 'Update file');

    expect($result)->toBeTrue();
});

it('does not commit when no changes exist', function () {
    $git = GitService::fake();

    $git->shouldReceive('status')
        ->once()
        ->andReturn([]);

    $git->shouldNotReceive('commit');

    $action = new CommitChanges($git);
    $result = $action->handle('/path/to/repo', 'Update file');

    expect($result)->toBeFalse();
});
```

## Alternative: Partial Faking with Process

For simpler cases, you can use `Process::fake()` directly in tests:

```php
it('gets git status', function () {
    Process::fake([
        'git status --porcelain' => Process::result('M file.txt'),
    ]);

    $service = app(GitService::class);
    $status = $service->status('/path/to/repo');

    expect($status)->not->toBeEmpty();
});
```

## Key Principles

1. **Single Responsibility**: Each service handles one external system or API
2. **Dependency Injection**: Inject dependencies (Process, Http, etc.) via constructor
3. **Testability**: Always provide a `fake()` method for easy mocking
4. **Consistency**: Follow Laravel's mocking patterns and conventions
5. **Type Safety**: Use proper type hints and return types
