---
name: replace-getenv-with-systemenvironment
description: Replace all getenv() calls with SystemEnvironment class for better testing and consistency
depends_on: null
---

## Feature Description

Replace all direct `getenv()` calls throughout the codebase with the custom `App\Support\SystemEnvironment` class that was created for this purpose. This provides:

1. **Better testability** - `SystemEnvironment::fake()` allows easy mocking in tests
2. **Consistency** - Single abstraction for all system environment access
3. **Type safety** - Methods with proper return types and defaults
4. **Clarity** - More explicit intent when accessing system environment

The `SystemEnvironment` class wraps `getenv()` and provides:

- `all()` - Get all environment variables (replaces `getenv()`)
- `get($key, $default)` - Get single variable with optional default
- `has($key)` - Check if variable exists and is not empty
- `fake($env)` - Mock environment for testing
- `clearFake()` - Restore normal behavior after tests

## Files Requiring Changes

### Backend Files with getenv() Usage

1. **`app/Drivers/Agent/ClaudeDriver.php`** (3 occurrences)
    - Line 28: `new Process($command, $worktree->path, getenv())`
    - Line 61: `new Process([$binaryPath, '--version'], null, getenv())`
    - Line 102: `new Process($command, null, getenv())`

2. **`app/Drivers/Server/ArtisanDriver.php`** (6 occurrences)
    - Line 40: `Process::env(getenv())->run('php -v')`
    - Line 54: `->env(getenv())`
    - Line 75: `Process::env(getenv())->run("netstat...")`
    - Line 77: `Process::env(getenv())->run("lsof...")`
    - Line 111: `Process::env(getenv())->run("netstat...")`
    - Line 113: `Process::env(getenv())->run("lsof...")`

3. **`app/Actions/Server/GetServerStatus.php`** (1 occurrence)
    - Line 65: `Process::env(getenv())->run('php artisan --version')`

4. **`app/Actions/Guideline/AggregateGuidelines.php`** (1 occurrence)
    - Line 17: `->env(getenv())`

### Guideline Files to Update

5. **`.ai/guidelines/system-environment.md`**
    - Update all code examples to use `SystemEnvironment` instead of `getenv()`
    - Add section about `SystemEnvironment::fake()` for testing
    - Update "CRITICAL RULE" to mention `SystemEnvironment`

6. **Run `php artisan boost:update`** after updating guidelines
    - This regenerates `CLAUDE.md` with updated guidelines

## Implementation Plan

### Backend Components

**Files to Modify:**

1. **`app/Drivers/Agent/ClaudeDriver.php`**
    - Inject `SystemEnvironment` via constructor
    - Replace `getenv()` with `$this->env->all()`

2. **`app/Drivers/Server/ArtisanDriver.php`**
    - Inject `SystemEnvironment` via constructor
    - Replace `getenv()` with `$this->env->all()`

3. **`app/Actions/Server/GetServerStatus.php`**
    - Inject `SystemEnvironment` via constructor
    - Replace `getenv()` with `$this->env->all()`

4. **`app/Actions/Guideline/AggregateGuidelines.php`**
    - Inject `SystemEnvironment` via constructor
    - Replace `getenv()` with `$this->env->all()`

**Guideline Files:**

5. **`.ai/guidelines/system-environment.md`**
    - Update all code examples from `getenv()` to `SystemEnvironment`
    - Add testing section with `SystemEnvironment::fake()` examples
    - Update critical rules section

6. **Run Artisan Command:**
    - Execute `php artisan boost:update` to regenerate CLAUDE.md

### No Database Changes

No migrations needed.

### No Frontend Changes

This is a backend-only refactor.

## Acceptance Criteria

- [ ] All `getenv()` calls in application code replaced with `SystemEnvironment`
- [ ] `ClaudeDriver` uses injected `SystemEnvironment` instance
- [ ] `ArtisanDriver` uses injected `SystemEnvironment` instance
- [ ] `GetServerStatus` uses injected `SystemEnvironment` instance
- [ ] `AggregateGuidelines` uses injected `SystemEnvironment` instance
- [ ] `.ai/guidelines/system-environment.md` updated with `SystemEnvironment` examples
- [ ] `.ai/guidelines/system-environment.md` includes testing section with `fake()` examples
- [ ] `php artisan boost:update` executed successfully
- [ ] `CLAUDE.md` reflects updated guidelines
- [ ] All existing tests still pass
- [ ] Code formatted with Laravel Pint

## Testing Strategy

### Update Existing Tests

**Tests to update:**

- Any tests that mock Process or test drivers should use `SystemEnvironment::fake()` instead of Process environment mocking

**Example test pattern:**

```php
use App\Support\SystemEnvironment;

it('uses system environment for process execution', function () {
    SystemEnvironment::fake([
        'PATH' => '/usr/local/bin:/usr/bin',
        'ANTHROPIC_API_KEY' => 'test-key',
    ]);

    $driver = app(ClaudeDriver::class);
    $result = $driver->isAvailable();

    SystemEnvironment::clearFake();

    expect($result)->toBeTrue();
});
```

### Run Existing Test Suite

- `php artisan test --compact --filter=ClaudeDriver`
- `php artisan test --compact --filter=ArtisanDriver`
- `php artisan test --compact --filter=GetServerStatus`
- `php artisan test --compact tests/Feature/Http/Controllers/ProjectControllerTest.php`

All tests should pass after the refactor.

## Code Formatting

Format all code using: Laravel Pint

Command to run: `vendor/bin/pint --dirty`

## Additional Notes

### Why SystemEnvironment Instead of getenv()

1. **Testability**: `SystemEnvironment::fake()` makes it trivial to mock environment in tests without using Process::fake()
2. **Dependency Injection**: Follows Laravel's DI patterns - easier to test and maintain
3. **Consistency**: Single source of truth for system environment access
4. **Type Safety**: Proper return types and defaults instead of `false` returns
5. **Already Exists**: The class was created for this exact purpose but never fully adopted

### Pattern to Follow

**Before:**

```php
class ClaudeDriver
{
    public function spawn(Worktree $worktree, string $prompt): Process
    {
        $process = new Process($command, $path, getenv());
        return $process;
    }
}
```

**After:**

```php
class ClaudeDriver
{
    public function __construct(
        private SystemEnvironment $env,
    ) {}

    public function spawn(Worktree $worktree, string $prompt): Process
    {
        $process = new Process($command, $path, $this->env->all());
        return $process;
    }
}
```

### Testing Pattern

**Before:**

```php
it('works', function () {
    Process::fake([
        'claude --version' => Process::result('Claude 1.0'),
    ]);

    // test code
});
```

**After:**

```php
it('works', function () {
    SystemEnvironment::fake([
        'PATH' => '/usr/local/bin',
        'ANTHROPIC_API_KEY' => 'test-key',
    ]);

    Process::fake([
        'claude --version' => Process::result('Claude 1.0'),
    ]);

    // test code

    SystemEnvironment::clearFake();
});
```

### Guideline Updates

The `.ai/guidelines/system-environment.md` file should be updated to:

1. Replace all `getenv()` examples with `SystemEnvironment`
2. Show constructor injection pattern
3. Include testing section with `fake()` examples
4. Update the "CRITICAL RULE" section:
    - Old: "Always pass `getenv()` to `->env()`"
    - New: "Always inject `SystemEnvironment` and pass `$this->env->all()` to `->env()`"

### Post-Implementation

After completing this refactor:

1. Run `php artisan boost:update` to update CLAUDE.md
2. Verify CLAUDE.md contains updated system-environment guidelines
3. Check that no direct `getenv()` calls remain in app/ directory (except in SystemEnvironment itself)
