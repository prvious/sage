---
name: toast-helper-class
description: Create Toast helper class with Sonner-compatible API for simple notifications
depends_on: inertia-flash-toast-handler
---

## Feature Description

Create a `Toast` helper class that provides a clean, fluent PHP API for creating toast notifications. The class mirrors the Sonner/Shadcn UI toast signature, making it intuitive for developers familiar with the frontend toast API.

**Benefits**:

- Clean, readable toast creation: `Toast::success('Saved!')->flash()`
- Type-safe API with IDE autocomplete
- Consistent signature with frontend Sonner library
- Support for description and duration
- Chainable methods for fluent API
- Works seamlessly with Inertia flash data handler

**Usage Examples**:

```php
// Simple success toast
Toast::success('Feature created successfully!')->flash();

// With description
Toast::error('Failed to process')
    ->description('Please check your input and try again')
    ->flash();

// With custom duration
Toast::info('Processing in background')
    ->duration(6000)
    ->flash();

// Warning with all options
Toast::warning('Action required')
    ->description('Your subscription expires in 3 days')
    ->duration(8000)
    ->flash();

// Chain multiple toasts
Toast::success('Data saved')->flash();
Toast::info('Email sent to admin')->duration(3000)->flash();
```

## Implementation Plan

### Backend Components

**Support Class**:

- Create `app/Support/Toast.php`
    - Static factory methods: `success()`, `error()`, `info()`, `warning()`
    - Instance methods: `description()`, `duration()`, `flash()`
    - Fluent interface (chainable methods)
    - Type hints for IDE support
    - Validation for toast types

**Class Structure**:

```php
<?php

declare(strict_types=1);

namespace App\Support;

use Inertia\Inertia;

final class Toast
{
    private string $type;
    private string $message;
    private ?string $description = null;
    private ?int $duration = null;

    private function __construct(string $type, string $message)
    {
        $this->type = $type;
        $this->message = $message;
    }

    /**
     * Create a success toast.
     */
    public static function success(string $message): self
    {
        return new self('success', $message);
    }

    /**
     * Create an error toast.
     */
    public static function error(string $message): self
    {
        return new self('error', $message);
    }

    /**
     * Create an info toast.
     */
    public static function info(string $message): self
    {
        return new self('info', $message);
    }

    /**
     * Create a warning toast.
     */
    public static function warning(string $message): self
    {
        return new self('warning', $message);
    }

    /**
     * Set the toast description (subtitle).
     */
    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set the toast duration in milliseconds.
     */
    public function duration(int $milliseconds): self
    {
        $this->duration = $milliseconds;

        return $this;
    }

    /**
     * Flash the toast to the session for Inertia.
     */
    public function flash(): void
    {
        $toastData = [
            'type' => $this->type,
            'message' => $this->message,
        ];

        if ($this->description !== null) {
            $toastData['description'] = $this->description;
        }

        if ($this->duration !== null) {
            $toastData['duration'] = $this->duration;
        }

        // Get existing toasts or initialize empty array
        $existingToasts = session()->get('toasts', []);

        // Add new toast
        $existingToasts[] = $toastData;

        // Flash to session
        Inertia::flash('toasts', $existingToasts);
    }

    /**
     * Convert toast to array (for testing).
     */
    public function toArray(): array
    {
        $data = [
            'type' => $this->type,
            'message' => $this->message,
        ];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->duration !== null) {
            $data['duration'] = $this->duration;
        }

        return $data;
    }
}
```

**No Controllers**:

- No new controllers needed
- Existing controllers use Toast class

**No Routes**:

- No new routes needed
- No API endpoints needed

**No Database Changes**:

- No migrations needed
- No model changes needed

### Frontend Components

**No Frontend Changes**:

- Uses existing `inertia-flash-toast-handler` feature
- Frontend already handles toast flash data
- No modifications to hooks or components needed

### Configuration/Infrastructure

**Autoloading** (if needed):

- `Toast` class auto-discovered by Laravel
- Located in `app/Support/` namespace
- No composer changes needed

**No Environment Variables**:

- No configuration needed
- No settings required

## Acceptance Criteria

- [ ] `Toast::success($message)` creates success toast
- [ ] `Toast::error($message)` creates error toast
- [ ] `Toast::info($message)` creates info toast
- [ ] `Toast::warning($message)` creates warning toast
- [ ] `->description($text)` adds description to toast
- [ ] `->duration($ms)` sets custom duration
- [ ] `->flash()` flashes toast to session
- [ ] Methods are chainable (fluent interface)
- [ ] Multiple toasts can be flashed in sequence
- [ ] Toast data matches Sonner/Inertia format
- [ ] Toasts appear correctly in frontend
- [ ] Type hints provide IDE autocomplete
- [ ] All tests pass
- [ ] Code formatted with Pint

## Testing Strategy

### Unit Tests

**Test file**: `tests/Unit/Support/ToastTest.php`

- Test success toast creation
- Test error toast creation
- Test info toast creation
- Test warning toast creation
- Test description method adds description
- Test duration method sets duration
- Test chainable methods work correctly
- Test toArray() returns correct structure
- Test flash() adds toast to session
- Test multiple toasts accumulate in session
- Test toast without description/duration
- Test toast with all options set

**Example Test**:

```php
it('creates success toast with description and duration', function () {
    $toast = Toast::success('Feature created')
        ->description('5 tasks added')
        ->duration(5000);

    expect($toast->toArray())->toBe([
        'type' => 'success',
        'message' => 'Feature created',
        'description' => '5 tasks added',
        'duration' => 5000,
    ]);
});

it('flashes toast to session', function () {
    Toast::success('Saved!')->flash();

    expect(session()->get('toasts'))->toHaveCount(1);
    expect(session()->get('toasts')[0])->toBe([
        'type' => 'success',
        'message' => 'Saved!',
    ]);
});

it('accumulates multiple toasts', function () {
    Toast::success('First')->flash();
    Toast::error('Second')->flash();

    expect(session()->get('toasts'))->toHaveCount(2);
});
```

### Feature Tests

**Test file**: `tests/Feature/Support/ToastIntegrationTest.php`

- Test toast appears after controller redirect
- Test multiple toasts display correctly
- Test toast with Inertia response
- Test toast persists across redirects
- Test toast integrates with flash handler

**Example Test**:

```php
it('displays toast after controller action', function () {
    $response = $this->post('/some-action', ['data' => 'value']);

    // In controller: Toast::success('Action completed')->flash();

    $response->assertSessionHas('toasts');
    expect(session()->get('toasts')[0]['type'])->toBe('success');
});
```

### Browser Tests

**Test file**: `tests/Browser/Toast/ToastHelperTest.php`

- Test success toast appears in UI
- Test error toast appears in UI
- Test toast with description displays correctly
- Test toast with custom duration auto-dismisses
- Test multiple toasts appear in sequence
- Test toasts work after form submission
- Test toasts work after redirect

## Code Formatting

**PHP**:

```bash
vendor/bin/pint
```

## Additional Notes

### Design Decisions

**Final Class**:

- Toast is `final` to prevent extension
- Keeps API simple and predictable
- Use composition over inheritance

**Private Constructor**:

- Forces use of static factory methods
- Ensures valid toast types
- Clear, readable API

**Fluent Interface**:

- All setter methods return `$this`
- Enables method chaining
- Reads like natural language

**Session Accumulation**:

- `flash()` appends to existing toasts
- Multiple toasts can be sent at once
- Session cleared after display (by Inertia)

### Usage Patterns

**Simple Success**:

```php
public function store(Request $request)
{
    // ... save data

    Toast::success('Project created!')->flash();

    return redirect()->route('projects.show', $project);
}
```

**Error with Details**:

```php
public function update(Request $request)
{
    try {
        // ... update logic
    } catch (\Exception $e) {
        Toast::error('Update failed')
            ->description($e->getMessage())
            ->flash();

        return back();
    }
}
```

**Background Processing Notice**:

```php
public function process(Request $request)
{
    ProcessJob::dispatch($data);

    Toast::info('Processing in background')
        ->description('You\'ll receive a notification when complete')
        ->duration(6000)
        ->flash();

    return redirect()->route('dashboard');
}
```

**Multiple Notifications**:

```php
public function bulkDelete(Request $request)
{
    $deleted = $this->deleteItems($request->ids);

    Toast::success("Deleted {$deleted} items")->flash();
    Toast::info('Audit log updated')->duration(3000)->flash();

    return back();
}
```

### Type Validation

**Valid Types**:

- `success` - Green checkmark
- `error` - Red X
- `info` - Blue info icon
- `warning` - Yellow warning icon

**Invalid Types**:

- Handled by static factory methods
- No way to create invalid type
- Type-safe at compile time

### Duration Guidelines

**Recommended Durations**:

- Success: 4000ms (default)
- Info: 5000ms
- Warning: 6000ms
- Error: 7000ms (users need time to read)

**Custom Durations**:

- Quick notices: 2000-3000ms
- Important info: 6000-8000ms
- Critical errors: 10000ms+
- Never use < 1000ms (too fast to read)

### Integration with Flash Handler

**Flow**:

1. Controller calls `Toast::success()->flash()`
2. Toast adds data to session `toasts` key
3. Inertia shares flash data with frontend
4. `useFlashToasts()` hook reads flash data
5. Hook calls `toast.success()` from Sonner
6. Toast appears in UI

**Dependencies**:

- Requires `inertia-flash-toast-handler` feature
- Works with existing `HandleInertiaRequests` middleware
- No changes to frontend needed

### Migration from Direct Flash

**Before** (manual flash):

```php
Inertia::flash('toasts', [
    [
        'type' => 'success',
        'message' => 'Saved!',
    ],
]);
```

**After** (Toast helper):

```php
Toast::success('Saved!')->flash();
```

**Benefits**:

- More readable and maintainable
- Type-safe with IDE support
- Less boilerplate code
- Chainable methods
- Consistent API

### Testing Helpers

**In Tests**:

```php
// Assert toast was flashed
expect(session()->has('toasts'))->toBeTrue();

// Assert toast count
expect(session()->get('toasts'))->toHaveCount(1);

// Assert toast type
expect(session()->get('toasts')[0]['type'])->toBe('success');

// Assert toast message
expect(session()->get('toasts')[0]['message'])->toBe('Saved!');
```

### Future Enhancements

**Possible Additions** (not in this feature):

- `Toast::default()` for neutral toasts
- `Toast::promise()` for async operations
- `Toast::loading()` for loading states
- `Toast::action()` for action buttons
- `Toast::persistent()` for non-dismissible toasts
- `Toast::position()` for custom positioning
- `Toast::group()` for grouped toasts

Keep the initial implementation simple and add these only if needed.

### Performance Considerations

**Session Storage**:

- Toasts stored in session temporarily
- Cleared after first display
- Minimal memory footprint
- No database overhead

**Multiple Toasts**:

- Each `flash()` call appends to array
- All toasts sent in single session key
- Efficient for bulk operations
- No performance impact

### Comparison with Alternatives

**vs Direct Inertia Flash**:

- ✅ More readable
- ✅ Type-safe
- ✅ Chainable
- ✅ Less boilerplate

**vs Global Helper Function**:

- ✅ Namespaced (no conflicts)
- ✅ Discoverable via IDE
- ✅ Testable
- ✅ Object-oriented

**vs Facade**:

- ❌ No need for service container
- ✅ Simpler implementation
- ✅ Direct instantiation
- ✅ Less magic

The static factory pattern provides the best balance of simplicity and usability.
