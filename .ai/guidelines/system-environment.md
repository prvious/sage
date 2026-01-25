# System Environment Guidelines

This app is a self-contained binary that interacts with the user's system environment, not application config files.

## When to Use System Environment vs Application Config

**System Environment:** External binaries (`claude`, `git`, `docker`), system env vars (`ANTHROPIC_API_KEY`, `PATH`), shell configs, system capabilities

**Application Config:** Database connections, app features, internal behavior

## Process Execution Rule

**CRITICAL: Always inject `SystemEnvironment` and call `->env($this->env->all())` when using `Process::class`**

Required steps:

1. Inject `App\Support\SystemEnvironment` in constructor
2. Call `->env($this->env->all())` before running process

```php
// ✅ CORRECT
class CheckAgentAuthenticated
{
    public function __construct(private SystemEnvironment $env) {}

    public function handle(): array
    {
        return Process::timeout(20)
            ->env($this->env->all())
            ->run('claude hello -p');
    }
}

// ❌ WRONG - Missing SystemEnvironment
Process::run("claude whoami");

// ❌ WRONG - Missing ->env($this->env->all())
Process::timeout(20)->run('claude hello');
```

## Environment Variable Access

```php
// ✅ CORRECT
class ApiKeyChecker
{
    public function __construct(private SystemEnvironment $env) {}

    public function hasApiKey(): bool
    {
        return $this->env->has('ANTHROPIC_API_KEY');
    }
}

// ❌ WRONG - Don't use config() or getenv()
config('services.anthropic.api_key');
getenv('ANTHROPIC_API_KEY');
```

## Binary Detection

```php
// ✅ CORRECT - Use ExecutableFinder
use Symfony\Component\Process\ExecutableFinder;

$path = (new ExecutableFinder())->find('claude');

// ❌ WRONG - Don't configure binary paths
config('sage.agents.claude.binary');
```

## Key Principles

1. **SystemEnvironment Required**: Always inject `SystemEnvironment` and use `->env($this->env->all())` with `Process::class`
2. **System First**: Prefer system env vars and PATH over app config
3. **No .env Management**: Users shouldn't manage `.env` for system integrations
4. **Testability**: Use `SystemEnvironment::fake()` for testing

## Testing

Use `SystemEnvironment::fake()` to mock environment variables:

```php
it('checks authentication', function () {
    SystemEnvironment::fake(['ANTHROPIC_API_KEY' => 'test-key']);
    Process::fake(['claude hello' => Process::result('{"success": true}')]);

    $result = app(CheckAgentAuthenticated::class)->handle();
    expect($result['authenticated'])->toBeTrue();
});
```

For available `SystemEnvironment` methods, see `app/Support/SystemEnvironment.php`.
