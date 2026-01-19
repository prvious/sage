---
name: agent-status-polling-and-ui-refactor
description: Add 1-minute polling for unauthenticated agents and refactor deferred UI to be non-nested
depends_on: null
---

## Feature Description

Currently, the Agent Settings page (`resources/js/pages/projects/agent.tsx`) has two issues:

1. **No automatic polling**: When the agent is not authenticated, users must manually click "Refresh" to check if authentication has been completed externally (e.g., after running `claude login` in their terminal).

2. **Nested deferred props**: The `agentAuthenticated` deferred prop is nested inside the `agentInstalled` deferred component, making the code harder to read and maintain. This violates the principle shown in the reference implementation where each status check is handled independently at the same level.

This feature will:

- Add automatic polling every 60 seconds when the agent is **not authenticated** (but installed)
- Refactor the component to handle deferred props separately (not nested)
- Follow the pattern from the reference implementation where status checks are independent and managed at the parent level
- Automatically stop polling when the agent becomes authenticated

## Implementation Plan

### Frontend Components

**Page to Modify:**

- `resources/js/pages/projects/agent.tsx` - Refactor to:
    - Use Inertia's `usePoll` hook for automatic polling
    - Flatten the deferred prop structure (no nesting)
    - Handle loading states for each status check independently
    - Automatically stop polling when authentication succeeds

**Component Structure (After Refactoring):**

```typescript
import { usePoll } from '@inertiajs/react';

// Use Inertia's built-in polling mechanism
const { start: startPolling, stop: stopPolling } = usePoll(
    60000, // 60 seconds
    {
        only: ['agentAuthenticated'],
        preserveScroll: true,
    },
    {
        autoStart: false, // We'll control when to start
    }
);

// Control polling based on authentication status
useEffect(() => {
    if (agentInstalled?.installed && !agentAuthenticated?.authenticated) {
        startPolling();
    } else {
        stopPolling();
    }

    return () => stopPolling();
}, [agentInstalled?.installed, agentAuthenticated?.authenticated]);

// Render each deferred section independently (not nested)
<Deferred data="agentInstalled" fallback={<InstallationSkeleton />}>
    {/* Installation status UI */}
</Deferred>

{agentInstalled?.installed && (
    <Deferred data="agentAuthenticated" fallback={<AuthenticationSkeleton />}>
        {/* Authentication status UI */}
    </Deferred>
)}
```

### Backend Components

**No backend changes required** - The existing controller and actions already support independent status checks via Inertia's `only` parameter.

### Styling

- Use existing Shadcn UI components (Alert, Card, Badge, etc.)
- Maintain current Tailwind classes for consistency
- Add subtle visual indicator when polling is active (optional enhancement)

## Acceptance Criteria

- [ ] Component uses Inertia's `usePoll` hook (not manual `setInterval`)
- [ ] Polling is initialized with `autoStart: false` for conditional control
- [ ] Polling starts automatically when agent is installed but not authenticated
- [ ] Polling interval is 60 seconds (1 minute)
- [ ] Polling stops automatically when agent becomes authenticated
- [ ] Polling only reloads `agentAuthenticated` data (not full page) via `only: ['agentAuthenticated']`
- [ ] `agentInstalled` and `agentAuthenticated` deferred props are handled separately (not nested)
- [ ] Each deferred prop has its own loading skeleton
- [ ] Manual refresh button still works and doesn't interfere with polling
- [ ] No polling occurs when agent is not installed
- [ ] No polling occurs when agent is already authenticated
- [ ] Polling cleanup occurs automatically when component unmounts (built-in to `usePoll`)
- [ ] `preserveScroll: true` is used to prevent scroll jump during polling
- [ ] Polling automatically throttles by 90% when browser tab is inactive (default `usePoll` behavior)
- [ ] Code is formatted using Prettier
- [ ] All existing functionality remains intact

## Testing Strategy

### Browser Tests

**Test file location:** `tests/Browser/AgentStatusPollingTest.php`

**Key test cases:**

- Test polling does NOT start when agent is not installed
- Test polling does NOT start when agent is already authenticated
- Test polling DOES start when agent is installed but not authenticated
- Test polling stops after authentication succeeds
- Test manual refresh works independently of polling
- Test component unmount cleans up polling automatically (Inertia's built-in behavior)
- Test polling uses `preserveScroll: true` to prevent scroll jumps
- Test UI shows separate loading states for installation and authentication checks
- Test that `usePoll` is called with correct parameters (60000ms, only: ['agentAuthenticated'], autoStart: false)
- Test that polling only reloads `agentAuthenticated`, not `agentInstalled`

### Unit Tests (Component)

While Pest Browser tests cover the integration, consider adding Vitest tests for the polling logic if you want pure unit coverage of the React hooks.

## Code Formatting

Format all code using Prettier.

Command to run: `pnpm run format` or `npx prettier --write resources/js/pages/projects/agent.tsx`

## Additional Notes

### Design Considerations

1. **Inertia's usePoll hook**: Using Inertia v2's built-in polling mechanism instead of manual `setInterval`. This provides automatic cleanup, throttling when tab is inactive (90% reduction), and consistent behavior across the app.

2. **60-second interval**: This balances responsiveness with server load. Users who run `claude login` in their terminal will see the updated status within a minute without needing to manually refresh.

3. **Conditional polling**: Polling only occurs when the agent is installed but not authenticated. This prevents unnecessary requests when:
    - Agent is not installed (can't authenticate anyway)
    - Agent is already authenticated (no need to keep checking)

4. **Scroll preservation**: Using `preserveScroll: true` prevents the page from jumping to the top during polling updates, maintaining a better UX.

5. **Automatic cleanup**: Inertia's `usePoll` automatically stops polling when the component unmounts, preventing memory leaks. No manual cleanup needed.

6. **Independent status checks**: Following the reference implementation pattern, installation and authentication checks are handled at the same level, not nested. This makes the code more readable and maintainable.

7. **Tab throttling**: By default, polling frequency reduces by 90% when the browser tab loses focus, saving resources. We use the default behavior (no `keepAlive: true`) since authentication checks can wait when the tab is inactive.

### Why Inertia's usePoll Instead of Manual setInterval

**Benefits of using Inertia's built-in polling:**

1. **Automatic tab throttling**: Reduces polling frequency by 90% when tab is inactive, saving server resources
2. **Automatic cleanup**: No need to manually clear intervals on unmount
3. **Consistent with Inertia patterns**: Uses the same router.reload mechanism as manual refreshes
4. **Less boilerplate**: No need to manage interval refs or cleanup logic
5. **Battle-tested**: Inertia's implementation handles edge cases we might miss

**Inertia's usePoll Pattern:**

```typescript
import { usePoll } from '@inertiajs/react';

// Set up polling with conditional start
const { start, stop } = usePoll(
    60000,
    {
        only: ['agentAuthenticated'],
        preserveScroll: true,
    },
    {
        autoStart: false,
    },
);

// Control when to poll
useEffect(() => {
    if (shouldPoll) {
        start();
    } else {
        stop();
    }
}, [shouldPoll]);
```

This is much cleaner than the manual approach shown in the reference implementation.

### Implementation Strategy

**Step 1: Import usePoll**

- Add `import { usePoll } from '@inertiajs/react'` to the component

**Step 2: Set up polling with autoStart: false**

- Initialize `usePoll(60000, { only: ['agentAuthenticated'], preserveScroll: true }, { autoStart: false })`
- Destructure `start` and `stop` methods

**Step 3: Flatten deferred props**

- Move `agentAuthenticated` Deferred component to be a sibling of `agentInstalled`, not a child
- Use conditional rendering (`{agentInstalled?.installed && ...}`) to only show auth check when installed

**Step 4: Add polling control logic**

- Use `useEffect` with dependencies on `agentInstalled?.installed` and `agentAuthenticated?.authenticated`
- Call `startPolling()` when installed but not authenticated
- Call `stopPolling()` when not installed or already authenticated
- Return cleanup function that calls `stopPolling()`

**Step 5: Test thoroughly**

- Ensure polling starts/stops at the right times
- Verify scroll preservation works
- Check that manual refresh doesn't break polling
- Verify tab throttling works (polling slows down when tab is inactive)

### Performance Considerations

- Polling only reloads `agentAuthenticated` data, not the full page or `agentInstalled`
- Server-side, the authentication check is relatively lightweight (runs a CLI command with 20s timeout)
- If the authentication check becomes expensive, consider adding a cache layer with 30s TTL

### Future Enhancements

- Add visual indicator showing "Checking every 60s..." when polling is active
- Allow users to configure polling interval in settings
- Add toast notification when authentication status changes
- Consider WebSocket-based status updates instead of polling (more real-time)

### Breaking Changes

None - this is a pure enhancement. The component's props and behavior remain backward compatible.
