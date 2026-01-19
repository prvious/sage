---
name: testable-environment-accessor
description: Create a testable wrapper for system environment variable access
depends_on: null
---

## Feature Description

Currently, the `CheckAgentAuthenticated` and potentially other actions directly access system environment variables using PHP's native `getenv()` function. This makes it difficult to test these actions in isolation because:

1. We cannot easily fake or mock environment variables in tests
2. Tests that rely on actual system environment become flaky and environment-dependent
3. The `Process` facade is easy to fake, but direct `getenv()` calls are not

This feature introduces a `SystemEnvironment` class in `App\Support` that wraps environment variable access. This wrapper can be dependency-injected into actions and easily faked during tests, similar to how we currently fake the `Process` facade.

## Implementation Plan

### Backend Components

**Support Classes:**

- `App\Support\SystemEnvironment` - New class that wraps `getenv()` calls with a clean, testable API

**Actions to Update:**

- `App\Actions\CheckAgentAuthenticated` - Replace direct `getenv()` calls with injected `SystemEnvironment`
- `App\Actions\ExpandHomePath` - Replace direct `getenv()` calls with injected `SystemEnvironment`
- Any other actions that currently use `getenv()` directly

**Test Support:**

- Implement a `fake()` static method on `SystemEnvironment` to enable clean, Laravel-style test faking

### SystemEnvironment API Design

```php
namespace App\Support;

class SystemEnvironment
{
    /**
     * Get a single environment variable.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Get all environment variables.
     */
    public function all(): array;

    /**
     * Check if an environment variable exists and is not empty.
     */
    public function has(string $key): bool;

    /**
     * Fake the system environment for testing.
     * Pass an array of environment variables to fake.
     */
    public static function fake(array $environment = []): void;

    /**
     * Clear any faked environment and restore normal behavior.
     */
    public static function clearFake(): void;
}
```

### Updated Action Signatures

**Before:**

```php
class CheckAgentAuthenticated
{
    public function handle(?string $binaryPath = null): array
    {
        if (filled(getenv('ANTHROPIC_API_KEY'))) {
            // ...
        }

        $process = Process::timeout(20)
            ->env(getenv())
            ->run("{$binary} hello -p --output-format json");
    }
}
```

**After:**

```php
class CheckAgentAuthenticated
{
    public function __construct(
        private readonly SystemEnvironment $env
    ) {}

    public function handle(?string $binaryPath = null): array
    {
        if ($this->env->has('ANTHROPIC_API_KEY')) {
            // ...
        }

        $process = Process::timeout(20)
            ->env($this->env->all())
            ->run("{$binary} hello -p --output-format json");
    }
}
```

## Acceptance Criteria

- [ ] `App\Support\SystemEnvironment` class is created with `get()`, `all()`, `has()`, `fake()`, and `clearFake()` methods
- [ ] Class follows `declare(strict_types=1)` and `final` conventions from existing Support classes
- [ ] `fake()` method accepts an array of environment variables to fake (e.g., `['ANTHROPIC_API_KEY' => 'test-key']`)
- [ ] `fake()` method with empty array allows testing with no environment variables set
- [ ] `clearFake()` method restores normal `getenv()` behavior after faking
- [ ] `CheckAgentAuthenticated` is refactored to use injected `SystemEnvironment` instead of direct `getenv()` calls
- [ ] `ExpandHomePath` is refactored to use injected `SystemEnvironment` instead of direct `getenv()` calls
- [ ] All existing tests for affected actions still pass
- [ ] New unit tests demonstrate `SystemEnvironment::fake()` usage similar to `Process::fake()`
- [ ] Tests show we can control environment variables during testing without relying on actual system env
- [ ] Code is formatted using Laravel Pint
- [ ] Debug `dd()` statement removed from `CheckAgentAuthenticated.php:17`

## Testing Strategy

### Unit Tests

**Test file location:** `tests/Unit/Support/SystemEnvironmentTest.php`

**Key test cases:**

- Test `get()` returns environment variable value from real system
- Test `get()` returns default value when variable doesn't exist
- Test `all()` returns all environment variables as array
- Test `has()` returns true when variable exists and is not empty
- Test `has()` returns false when variable doesn't exist or is empty
- Test `fake()` method overrides real environment variables
- Test `fake()` with specific variables returns only those variables in `all()`
- Test `fake()` with empty array simulates no environment variables
- Test `clearFake()` restores normal `getenv()` behavior
- Test `has()` works correctly with faked environment
- Test `get()` with default values works correctly with faked environment

**Test file location:** `tests/Unit/Actions/CheckAgentAuthenticatedTest.php`

**Key test cases:**

- Test authentication check uses faked `SystemEnvironment` to simulate API key presence
- Test authentication check uses faked `SystemEnvironment` to simulate API key absence
- Test that `SystemEnvironment::all()` is passed to Process facade's `env()` method
- Test both authentication paths (API key vs CLI auth) using faked environment

**Test file location:** `tests/Unit/Actions/ExpandHomePathTest.php`

**Key test cases:**

- Update existing tests to use faked `SystemEnvironment` instead of relying on actual system HOME/USERPROFILE variables
- Test home path expansion with faked HOME environment variable
- Test home path expansion with faked USERPROFILE for Windows

### Example Test Patterns Using `fake()`

```php
use App\Support\SystemEnvironment;

it('authenticates via API key when ANTHROPIC_API_KEY is present', function () {
    // Fake the system environment with an API key
    SystemEnvironment::fake([
        'ANTHROPIC_API_KEY' => 'sk-ant-test-key-12345',
    ]);

    $action = app(CheckAgentAuthenticated::class);
    $result = $action->handle();

    expect($result['authenticated'])->toBeTrue()
        ->and($result['auth_type'])->toBe('api_key');
});

it('checks CLI auth when ANTHROPIC_API_KEY is not present', function () {
    // Fake the system environment without an API key
    SystemEnvironment::fake([
        'PATH' => '/usr/local/bin:/usr/bin',
        'HOME' => '/home/user',
    ]);

    Process::fake([
        'claude hello -p --output-format json' => Process::result('{"success": true}'),
    ]);

    $action = app(CheckAgentAuthenticated::class);
    $result = $action->handle();

    expect($result['authenticated'])->toBeTrue()
        ->and($result['auth_type'])->toBe('cli');
});

it('expands home directory path using faked HOME variable', function () {
    SystemEnvironment::fake([
        'HOME' => '/home/testuser',
    ]);

    $action = app(ExpandHomePath::class);
    $result = $action->handle('~/projects/myapp');

    expect($result)->toBe('/home/testuser/projects/myapp');
});

it('works with empty environment when testing edge cases', function () {
    // Fake with no environment variables
    SystemEnvironment::fake([]);

    $action = app(CheckAgentAuthenticated::class);
    $result = $action->handle();

    expect($result['authenticated'])->toBeFalse()
        ->and($result['auth_type'])->toBe('none');
});
```

### Alternative: Using Mockery for Advanced Cases

For complex test scenarios where you need fine-grained control over method behavior, you can still use Mockery:

```php
use App\Support\SystemEnvironment;
use Mockery;

it('handles custom environment logic with mockery', function () {
    $mockEnv = Mockery::mock(SystemEnvironment::class);
    $mockEnv->shouldReceive('has')
        ->with('ANTHROPIC_API_KEY')
        ->once()
        ->andReturn(true);

    $action = new CheckAgentAuthenticated($mockEnv);
    $result = $action->handle();

    expect($result['authenticated'])->toBeTrue();
});
```

## Code Formatting

Format all code using Laravel Pint.

Command to run: `vendor/bin/pint --dirty`

## Additional Notes

### Design Considerations

1. **Simple wrapper, not a replacement for config()**: This class is specifically for system environment variables that the binary needs to detect at runtime (like `ANTHROPIC_API_KEY` in the user's shell). It's NOT meant to replace Laravel's `config()` system for application configuration.

2. **Follows System Environment Guidelines**: According to `.ai/guidelines/system-environment.md`, this application is distributed as a binary and needs to interact with the user's system environment. This wrapper makes that interaction testable while maintaining the same behavior.

3. **Constructor injection over facades**: Following Laravel best practices and existing codebase patterns, we use constructor injection rather than creating a facade for this support class.

4. **Laravel-style `fake()` method**: Following the pattern established by `Process::fake()`, `Queue::fake()`, and other Laravel test helpers, `SystemEnvironment::fake()` provides a clean, intuitive API for testing. This is the preferred approach over Mockery for most test cases.

5. **Fake implementation strategy**: The `fake()` method will use a static property to store faked environment values. When faked, the `get()`, `all()`, and `has()` methods will read from this faked array instead of calling `getenv()`. This allows us to completely control the environment during tests.

6. **Service container binding**: Since `SystemEnvironment` will be injected via constructor, tests can use `app(ActionClass::class)` to automatically resolve dependencies with the faked environment.

### Implementation Details for `fake()`

The `fake()` method should:

- Accept an array of environment variables to fake: `['KEY' => 'value', ...]`
- Store this array in a static property
- Make `get()`, `all()`, and `has()` check if faking is active before calling `getenv()`
- Allow passing an empty array to simulate an environment with no variables
- Persist until `clearFake()` is called or the test completes

### Breaking Changes

None - this is an internal refactoring. The public API of the affected actions remains unchanged.
