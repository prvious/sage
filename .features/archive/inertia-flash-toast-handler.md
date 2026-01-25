---
name: inertia-flash-toast-handler
description: Add flash data handler to automatically display server-side flash messages as toasts
depends_on: null
---

## Feature Description

Add an automatic flash data extractor in the app layout to intercept flash messages sent from the server via Inertia's flash data feature. This allows controllers to flash toast notifications that automatically appear in the UI without requiring manual toast calls in each component.

**Benefits**:

- Centralized toast handling for server responses
- Controllers can send notifications via simple `Inertia::flash()` calls
- No need to manually trigger toasts in frontend components
- Consistent notification handling across the application
- Works seamlessly with Inertia's page reloads and redirects

**Usage Example**:

Backend (Controller):

```php
Inertia::flash('toasts', [
    [
        'type' => 'success',
        'message' => 'Feature workflow created successfully!',
    ],
]);
```

Frontend (Automatic):

- Flash data intercepted in layout
- Toast automatically displayed using `sonner`
- No manual toast calls needed in components

## Current Implementation

**Toast Library**:

- Using `sonner` toast library
- Already imported in `app-layout.tsx`
- `<Toaster position='top-right' />` component renders toasts

**Toast Methods Available**:

- `toast.success(message, options)`
- `toast.error(message, options)`
- `toast.info(message, options)`
- `toast.warning(message, options)`
- `toast(message, options)` - default/neutral
- `toast.promise(promise, messages)` - for promises
- `toast.loading(message)` - loading state

**Inertia Integration**:

- Using `@inertiajs/react` v2.3.11
- `usePage()` hook provides access to flash data
- Flash data persists across redirects
- Flash data available in `usePage().props.flash`

## Implementation Plan

### Backend Components

**Middleware** (modify existing):

- Update `app/Http/Middleware/HandleInertiaRequests.php`:
    - Add `toasts` to shared data
    - Share flash `toasts` key with every Inertia response
    - Example:
        ```php
        public function share(Request $request): array
        {
            return [
                ...parent::share($request),
                'flash' => [
                    'toasts' => fn () => $request->session()->get('toasts'),
                ],
            ];
        }
        ```

**Helper Function** (optional):

- Create `app/Support/helpers.php` or add to existing helpers:
    ```php
    function flash_toast(string $type, string $message, ?string $description = null): void
    {
        Inertia::flash('toasts', [
            [
                'type' => $type,
                'message' => $message,
                'description' => $description,
            ],
        ]);
    }
    ```

**No Routes/Controllers**:

- No new routes needed
- No new controllers needed
- Existing controllers can use flash data

**No Database Changes**:

- No migrations needed
- No model changes needed

### Frontend Components

**New Hook**:

- Create `resources/js/hooks/use-flash-toasts.ts`:
    - Custom hook to handle flash toasts
    - Uses `usePage()` to get flash data
    - Uses `useEffect` to trigger toasts
    - Supports multiple toast types
    - Example implementation:

        ```tsx
        import { useEffect } from 'react';
        import { usePage } from '@inertiajs/react';
        import { toast } from 'sonner';

        interface FlashToast {
            type: 'success' | 'error' | 'info' | 'warning' | 'default';
            message: string;
            description?: string;
            duration?: number;
        }

        export function useFlashToasts() {
            const { flash } = usePage().props;

            useEffect(() => {
                const toasts = flash?.toasts as FlashToast[] | undefined;

                if (toasts && Array.isArray(toasts)) {
                    toasts.forEach((toastData) => {
                        const toastFn = toast[toastData.type] || toast;
                        toastFn(toastData.message, {
                            description: toastData.description,
                            duration: toastData.duration || 4000,
                        });
                    });
                }
            }, [flash]);
        }
        ```

**Modified Components**:

- Update `resources/js/components/layout/app-layout.tsx`:
    - Import and call `useFlashToasts()` hook
    - Hook automatically processes flash toasts
    - Example:

        ```tsx
        import { useFlashToasts } from '@/hooks/use-flash-toasts';

        export function AppLayout({ children }: AppLayoutProps) {
            useFlashToasts(); // Add this line

            return (
                <QuickTaskProvider>
                    <div className='flex h-screen bg-background'>{/* ... rest of layout */}</div>
                </QuickTaskProvider>
            );
        }
        ```

**TypeScript Types**:

- Update `resources/js/types/index.d.ts`:
    - Add flash toast types to SharedData
    - Example:

        ```tsx
        interface FlashToast {
            type: 'success' | 'error' | 'info' | 'warning' | 'default';
            message: string;
            description?: string;
            duration?: number;
        }

        interface SharedData {
            // ... existing props
            flash?: {
                toasts?: FlashToast[];
            };
        }
        ```

### Configuration/Infrastructure

**No Configuration Changes**:

- No environment variables needed
- No build changes needed
- No third-party integrations needed

## Acceptance Criteria

- [ ] Flash toasts automatically appear when sent from server
- [ ] `Inertia::flash('toasts', [...])` triggers toasts in UI
- [ ] Support for success, error, info, warning toast types
- [ ] Toast message displays correctly
- [ ] Optional description displays below message
- [ ] Custom duration supported (defaults to 4000ms)
- [ ] Multiple toasts can be sent at once
- [ ] Toasts work with Inertia redirects
- [ ] Toasts work with Inertia page reloads
- [ ] No duplicate toasts on page navigation
- [ ] Hook integrates cleanly with existing layout
- [ ] TypeScript types properly defined
- [ ] All tests pass
- [ ] Code formatted with Pint and Prettier

## Testing Strategy

### Unit Tests

**Test file**: `tests/Unit/Hooks/UseFlashToastsTest.php`

- Not applicable - frontend hook testing done in browser tests

### Feature Tests

**Test file**: `tests/Feature/Middleware/HandleInertiaRequestsTest.php`

- Test flash toasts shared in Inertia responses
- Test toasts key exists in shared data
- Test toasts array structure
- Test empty toasts returns null/undefined

**Test file**: `tests/Feature/Http/FlashToastIntegrationTest.php`

- Test controller flashes toast data
- Test redirect includes flash toasts
- Test multiple toasts flashed at once
- Test toast data structure validates correctly

### Browser Tests

**Test file**: `tests/Browser/FlashToasts/FlashToastDisplayTest.php`

- Test success toast appears after server flash
- Test error toast appears after server flash
- Test info toast appears after server flash
- Test warning toast appears after server flash
- Test toast with description displays correctly
- Test multiple toasts display in sequence
- Test toasts appear after redirect
- Test toasts appear after Inertia reload
- Test toasts don't duplicate on navigation
- Test toast auto-dismisses after duration
- Test custom duration respected

## Code Formatting

**PHP**:

```bash
vendor/bin/pint
```

**TypeScript/React**:

```bash
pnpm run format
```

## Additional Notes

### Flash Data Structure

**Single Toast**:

```php
Inertia::flash('toasts', [
    [
        'type' => 'success',
        'message' => 'Operation completed!',
    ],
]);
```

**Multiple Toasts**:

```php
Inertia::flash('toasts', [
    [
        'type' => 'success',
        'message' => 'Feature created!',
        'description' => '5 tasks added to board',
    ],
    [
        'type' => 'info',
        'message' => 'Processing in background',
        'duration' => 6000,
    ],
]);
```

**With Description**:

```php
Inertia::flash('toasts', [
    [
        'type' => 'error',
        'message' => 'Failed to process request',
        'description' => 'Please check your input and try again',
    ],
]);
```

### Helper Function Usage

If optional helper function is created:

```php
// Simple usage
flash_toast('success', 'Saved!');

// With description
flash_toast('error', 'Failed', 'Invalid data provided');

// Then redirect
return redirect()->route('dashboard');
```

### Toast Types Mapping

Map Laravel flash types to Sonner toast methods:

- `success` → `toast.success()`
- `error` → `toast.error()`
- `info` → `toast.info()`
- `warning` → `toast.warning()`
- `default` or any other → `toast()`

### Preventing Duplicate Toasts

The `useEffect` dependency on `flash` ensures:

- Toasts only fire when flash data changes
- Navigation with same flash data doesn't re-trigger
- Flash data cleared after being displayed

### Integration with Existing Features

**Queue Jobs**:

- Jobs can't directly use `Inertia::flash()`
- Use WebSocket events for job completion (already implemented)
- Flash toasts best for synchronous responses

**Form Submissions**:

- Perfect for form validation feedback
- Success/error messages after create/update/delete
- Example:

    ```php
    public function store(Request $request)
    {
        // ... validation and storage

        Inertia::flash('toasts', [
            ['type' => 'success', 'message' => 'Created successfully!'],
        ]);

        return redirect()->route('index');
    }
    ```

**Page Reloads**:

- Flash toasts persist across Inertia reloads
- Useful for `router.reload()` after actions
- Toasts appear after data refreshes

### Edge Cases

**Empty Toasts**:

- Hook safely handles undefined/null flash data
- No toasts appear if flash.toasts is empty
- No errors thrown for invalid data

**Invalid Toast Type**:

- Falls back to default `toast()` method
- Still displays message correctly
- Logs warning in console (optional)

**Long Messages**:

- Sonner automatically handles long text
- Consider truncating in backend if very long
- Use description for additional details

### Future Enhancements

**Toast Actions**:

- Add action buttons to toasts
- Example: "Undo" button for delete actions
- Requires additional flash data structure

**Toast Grouping**:

- Group related toasts together
- Collapse multiple similar toasts
- Useful for bulk operations

**Persistent Toasts**:

- Some toasts don't auto-dismiss
- User must manually close
- Useful for critical errors

**Toast History**:

- Keep history of recent toasts
- Allow user to review past notifications
- Could integrate with notification center

### Comparison with WebSocket Events

**Flash Toasts** (this feature):

- ✅ Synchronous responses
- ✅ Form submissions
- ✅ Immediate redirects
- ✅ Simple controller actions
- ❌ Async job completions
- ❌ Real-time updates

**WebSocket Events** (existing):

- ❌ Synchronous responses
- ❌ Simple redirects
- ✅ Async job completions
- ✅ Real-time updates
- ✅ Background processing
- ✅ Multi-user updates

Use both together for comprehensive notification coverage.
