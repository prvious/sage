---
name: use-fetch-hook
description: Custom React hook wrapping fetch with CSRF tokens and default headers
depends_on: null
---

## Feature Description

Create a custom React hook `useFetch()` that wraps the native `fetch()` API and automatically includes Laravel CSRF tokens, JSON content-type headers, and other necessary headers for API requests. This provides a simpler, more consistent way to make API calls throughout the application without manually managing authentication tokens and headers.

Key features:

- **CSRF Token Injection**: Automatically includes `X-CSRF-TOKEN` header from Laravel
- **JSON by Default**: Sets `Content-Type: application/json` and `Accept: application/json`
- **Simple API**: Returns a fetch function that works like native fetch
- **Type-Safe**: Full TypeScript support with proper types
- **Error Handling**: Consistent error responses
- **Request Interceptor**: Hook into requests before they're sent

This eliminates the need to manually add CSRF tokens and headers to every fetch call, reducing boilerplate and preventing security vulnerabilities from forgotten tokens.

## Implementation Plan

### Frontend Components

**Hooks to Create:**

- `resources/js/hooks/use-fetch.tsx` - Main useFetch hook implementation

**Utility Functions:**

- `resources/js/lib/csrf.ts` - CSRF token management utilities
- `resources/js/lib/fetch-client.ts` - Enhanced fetch client (optional, for more features)

**Type Definitions:**

- Add TypeScript types for useFetch return value
- Extend RequestInit with custom options

**No Backend Changes:**

- Uses existing Laravel CSRF token mechanism
- No new routes or controllers needed

### useFetch Hook Implementation

**Basic Implementation:**

```tsx
// resources/js/hooks/use-fetch.tsx
import { useCallback } from 'react';

/**
 * Custom fetch hook that automatically includes CSRF token and headers
 */
export function useFetch() {
    const fetch = useCallback(async (url: string | URL | Request, init?: RequestInit) => {
        // Get CSRF token from meta tag
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Merge default headers with user-provided headers
        const headers = new Headers(init?.headers);

        // Add CSRF token if present
        if (token) {
            headers.set('X-CSRF-TOKEN', token);
        }

        // Add JSON headers if not already set
        if (!headers.has('Content-Type')) {
            headers.set('Content-Type', 'application/json');
        }
        if (!headers.has('Accept')) {
            headers.set('Accept', 'application/json');
        }

        // Add X-Requested-With for Laravel to detect AJAX requests
        headers.set('X-Requested-With', 'XMLHttpRequest');

        // Merge init options
        const mergedInit: RequestInit = {
            ...init,
            headers,
            credentials: init?.credentials ?? 'same-origin', // Include cookies by default
        };

        // Make the request
        return window.fetch(url, mergedInit);
    }, []);

    return fetch;
}
```

**Enhanced Implementation with Type Safety:**

```tsx
// resources/js/hooks/use-fetch.tsx
import { useCallback } from 'react';

interface UseFetchOptions extends RequestInit {
    // Add custom options if needed
    skipCsrf?: boolean;
    skipJsonHeaders?: boolean;
}

type FetchFunction = (url: string | URL | Request, init?: UseFetchOptions) => Promise<Response>;

/**
 * Custom fetch hook that automatically includes CSRF token and headers
 *
 * @example
 * const fetch = useFetch();
 *
 * // GET request
 * const response = await fetch('/api/projects');
 * const data = await response.json();
 *
 * // POST request
 * const response = await fetch('/api/projects', {
 *   method: 'POST',
 *   body: JSON.stringify({ name: 'My Project' }),
 * });
 */
export function useFetch(): FetchFunction {
    const fetch = useCallback<FetchFunction>(async (url, init) => {
        const { skipCsrf = false, skipJsonHeaders = false, ...fetchInit } = init ?? {};

        // Get CSRF token from meta tag
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Merge default headers with user-provided headers
        const headers = new Headers(fetchInit.headers);

        // Add CSRF token if present and not skipped
        if (token && !skipCsrf) {
            headers.set('X-CSRF-TOKEN', token);
        }

        // Add JSON headers if not already set and not skipped
        if (!skipJsonHeaders) {
            if (!headers.has('Content-Type')) {
                headers.set('Content-Type', 'application/json');
            }
            if (!headers.has('Accept')) {
                headers.set('Accept', 'application/json');
            }
        }

        // Add X-Requested-With for Laravel to detect AJAX requests
        headers.set('X-Requested-With', 'XMLHttpRequest');

        // Merge init options
        const mergedInit: RequestInit = {
            ...fetchInit,
            headers,
            credentials: fetchInit.credentials ?? 'same-origin',
        };

        // Make the request
        return window.fetch(url, mergedInit);
    }, []);

    return fetch;
}

/**
 * Helper function to get CSRF token
 */
export function getCsrfToken(): string | null {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? null;
}
```

**Advanced Implementation with JSON Helper:**

```tsx
// resources/js/hooks/use-fetch.tsx
import { useCallback } from 'react';

interface UseFetchOptions extends RequestInit {
    skipCsrf?: boolean;
    skipJsonHeaders?: boolean;
}

interface FetchFunction {
    (url: string | URL | Request, init?: UseFetchOptions): Promise<Response>;
    json: <T = any>(url: string | URL | Request, init?: UseFetchOptions) => Promise<T>;
    post: (url: string | URL | Request, data?: any, init?: UseFetchOptions) => Promise<Response>;
    put: (url: string | URL | Request, data?: any, init?: UseFetchOptions) => Promise<Response>;
    patch: (url: string | URL | Request, data?: any, init?: UseFetchOptions) => Promise<Response>;
    delete: (url: string | URL | Request, init?: UseFetchOptions) => Promise<Response>;
}

export function useFetch(): FetchFunction {
    const baseFetch = useCallback(async (url: string | URL | Request, init?: UseFetchOptions) => {
        const { skipCsrf = false, skipJsonHeaders = false, ...fetchInit } = init ?? {};

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const headers = new Headers(fetchInit.headers);

        if (token && !skipCsrf) {
            headers.set('X-CSRF-TOKEN', token);
        }

        if (!skipJsonHeaders) {
            if (!headers.has('Content-Type')) {
                headers.set('Content-Type', 'application/json');
            }
            if (!headers.has('Accept')) {
                headers.set('Accept', 'application/json');
            }
        }

        headers.set('X-Requested-With', 'XMLHttpRequest');

        const mergedInit: RequestInit = {
            ...fetchInit,
            headers,
            credentials: fetchInit.credentials ?? 'same-origin',
        };

        return window.fetch(url, mergedInit);
    }, []);

    // Helper for JSON responses
    const json = useCallback(
        async <T = any,>(url: string | URL | Request, init?: UseFetchOptions) => {
            const response = await baseFetch(url, init);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json() as Promise<T>;
        },
        [baseFetch],
    );

    // Helper for POST requests
    const post = useCallback(
        async (url: string | URL | Request, data?: any, init?: UseFetchOptions) => {
            return baseFetch(url, {
                ...init,
                method: 'POST',
                body: data ? JSON.stringify(data) : undefined,
            });
        },
        [baseFetch],
    );

    // Helper for PUT requests
    const put = useCallback(
        async (url: string | URL | Request, data?: any, init?: UseFetchOptions) => {
            return baseFetch(url, {
                ...init,
                method: 'PUT',
                body: data ? JSON.stringify(data) : undefined,
            });
        },
        [baseFetch],
    );

    // Helper for PATCH requests
    const patch = useCallback(
        async (url: string | URL | Request, data?: any, init?: UseFetchOptions) => {
            return baseFetch(url, {
                ...init,
                method: 'PATCH',
                body: data ? JSON.stringify(data) : undefined,
            });
        },
        [baseFetch],
    );

    // Helper for DELETE requests
    const deleteFn = useCallback(
        async (url: string | URL | Request, init?: UseFetchOptions) => {
            return baseFetch(url, {
                ...init,
                method: 'DELETE',
            });
        },
        [baseFetch],
    );

    // Attach helpers to the main fetch function
    const fetch = baseFetch as FetchFunction;
    fetch.json = json;
    fetch.post = post;
    fetch.put = put;
    fetch.patch = patch;
    fetch.delete = deleteFn;

    return fetch;
}
```

### Usage Examples

**Basic Usage:**

```tsx
import { useFetch } from '@/hooks/use-fetch';

export default function MyComponent() {
    const fetch = useFetch();

    const handleFetchData = async () => {
        // GET request
        const response = await fetch('/api/projects');
        const projects = await response.json();
    };

    const handleCreateProject = async () => {
        // POST request
        const response = await fetch('/api/projects', {
            method: 'POST',
            body: JSON.stringify({
                name: 'My Project',
                path: '/var/www/project',
            }),
        });

        if (response.ok) {
            const project = await response.json();
            console.log('Created:', project);
        }
    };

    return (
        <div>
            <button onClick={handleFetchData}>Fetch Projects</button>
            <button onClick={handleCreateProject}>Create Project</button>
        </div>
    );
}
```

**With Helper Methods:**

```tsx
import { useFetch } from '@/hooks/use-fetch';

export default function MyComponent() {
    const fetch = useFetch();

    const handleFetchData = async () => {
        try {
            const projects = await fetch.json<Project[]>('/api/projects');
            console.log(projects);
        } catch (error) {
            console.error('Failed to fetch projects:', error);
        }
    };

    const handleCreateProject = async () => {
        try {
            const response = await fetch.post('/api/projects', {
                name: 'My Project',
                path: '/var/www/project',
            });

            if (response.ok) {
                const project = await response.json();
                console.log('Created:', project);
            }
        } catch (error) {
            console.error('Failed to create project:', error);
        }
    };

    return (
        <div>
            <button onClick={handleFetchData}>Fetch Projects</button>
            <button onClick={handleCreateProject}>Create Project</button>
        </div>
    );
}
```

**With Custom Headers:**

```tsx
const fetch = useFetch();

// Skip CSRF for public API
const response = await fetch('/api/public/data', {
    skipCsrf: true,
});

// Custom headers
const response = await fetch('/api/projects', {
    headers: {
        'X-Custom-Header': 'value',
    },
});

// Skip JSON headers (for FormData, etc.)
const formData = new FormData();
formData.append('file', file);

const response = await fetch('/api/upload', {
    method: 'POST',
    body: formData,
    skipJsonHeaders: true, // Don't set Content-Type for FormData
});
```

### CSRF Token Utility

```tsx
// resources/js/lib/csrf.ts

/**
 * Get CSRF token from meta tag
 */
export function getCsrfToken(): string | null {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? null;
}

/**
 * Check if CSRF token exists
 */
export function hasCsrfToken(): boolean {
    return getCsrfToken() !== null;
}

/**
 * Refresh CSRF token (useful after login/logout)
 */
export async function refreshCsrfToken(): Promise<void> {
    const response = await window.fetch('/sanctum/csrf-cookie', {
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error('Failed to refresh CSRF token');
    }
}
```

## Acceptance Criteria

- [ ] useFetch hook is created and exported
- [ ] Hook returns a fetch function that works like native fetch
- [ ] CSRF token is automatically included in X-CSRF-TOKEN header
- [ ] Content-Type: application/json header is set by default
- [ ] Accept: application/json header is set by default
- [ ] X-Requested-With: XMLHttpRequest header is set by default
- [ ] credentials: 'same-origin' is set by default
- [ ] User-provided headers are merged with default headers
- [ ] User-provided headers can override default headers
- [ ] skipCsrf option allows skipping CSRF token
- [ ] skipJsonHeaders option allows skipping JSON headers
- [ ] Hook is properly typed with TypeScript
- [ ] Hook works with GET, POST, PUT, PATCH, DELETE requests
- [ ] Hook works with FormData (when skipJsonHeaders is true)
- [ ] getCsrfToken utility function is exported
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Unit Tests (Optional)

**Test file location:** `tests/Frontend/Hooks/useFetchTest.ts` (if using Vitest)

**Key test cases:**

- Test useFetch returns a function
- Test CSRF token is included in headers
- Test JSON headers are included by default
- Test X-Requested-With header is included
- Test custom headers are merged correctly
- Test skipCsrf option works
- Test skipJsonHeaders option works
- Test credentials are set to same-origin by default

### Browser Tests

**Test file location:** `tests/Browser/UseFetchTest.php`

**Key test cases:**

- Test API request with useFetch includes CSRF token
- Test API POST request succeeds with automatic CSRF token
- Test API request without CSRF token fails (when not using hook)
- Test custom headers are sent correctly
- Test FormData upload works with skipJsonHeaders

## Code Formatting

Format all code using: **Prettier** and **oxfmt** (for JavaScript/TypeScript)

Commands to run:

- `pnpm run format` - Format JavaScript/TypeScript files

## Additional Notes

### Why Not Use Inertia Router?

**When to Use useFetch:**

- API requests that don't need page navigation
- Background data fetching
- File uploads
- Real-time updates
- Non-Inertia endpoints

**When to Use Inertia Router:**

- Page navigation
- Form submissions that redirect
- Full-page requests

**Both Can Coexist:**

```tsx
import { router } from '@inertiajs/react';
import { useFetch } from '@/hooks/use-fetch';

// Inertia for page navigation
router.post('/projects', data);

// useFetch for API calls
const fetch = useFetch();
await fetch.post('/api/projects', data);
```

### CSRF Token Meta Tag

Ensure Laravel layout includes CSRF token:

```blade
<!-- resources/views/app.blade.php -->
<head>
  @vite('resources/js/app.tsx')
  @inertiaHead

  <!-- CSRF Token -->
  <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
```

### Error Handling

**Enhanced Error Handling:**

```tsx
export class FetchError extends Error {
    constructor(
        message: string,
        public status: number,
        public response: Response,
    ) {
        super(message);
        this.name = 'FetchError';
    }
}

// In useFetch hook
const json = useCallback(
    async <T = any,>(url: string | URL | Request, init?: UseFetchOptions) => {
        const response = await baseFetch(url, init);

        if (!response.ok) {
            const message = `HTTP error! status: ${response.status}`;
            throw new FetchError(message, response.status, response);
        }

        return response.json() as Promise<T>;
    },
    [baseFetch],
);
```

**Usage with Error Handling:**

```tsx
try {
    const data = await fetch.json('/api/projects');
} catch (error) {
    if (error instanceof FetchError) {
        if (error.status === 404) {
            console.log('Not found');
        } else if (error.status === 422) {
            const errors = await error.response.json();
            console.log('Validation errors:', errors);
        }
    }
}
```

### Request Interceptors

**Advanced: Add Request Interceptors:**

```tsx
type RequestInterceptor = (
    url: string | URL | Request,
    init: RequestInit,
) =>
    | Promise<{ url: string | URL | Request; init: RequestInit }>
    | {
          url: string | URL | Request;
          init: RequestInit;
      };

const interceptors: RequestInterceptor[] = [];

export function addRequestInterceptor(interceptor: RequestInterceptor) {
    interceptors.push(interceptor);
}

// In useFetch
let finalUrl = url;
let finalInit = mergedInit;

for (const interceptor of interceptors) {
    const result = await interceptor(finalUrl, finalInit);
    finalUrl = result.url;
    finalInit = result.init;
}

return window.fetch(finalUrl, finalInit);
```

**Usage:**

```tsx
// Add auth token interceptor
addRequestInterceptor((url, init) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        const headers = new Headers(init.headers);
        headers.set('Authorization', `Bearer ${token}`);
        return { url, init: { ...init, headers } };
    }
    return { url, init };
});
```

### Alternative: Axios-like API

For developers familiar with Axios:

```tsx
export function useFetch() {
    return {
        get: (url: string, config?: RequestInit) => baseFetch(url, { ...config, method: 'GET' }),
        post: (url: string, data?: any, config?: RequestInit) =>
            baseFetch(url, {
                ...config,
                method: 'POST',
                body: JSON.stringify(data),
            }),
        put: (url: string, data?: any, config?: RequestInit) =>
            baseFetch(url, {
                ...config,
                method: 'PUT',
                body: JSON.stringify(data),
            }),
        patch: (url: string, data?: any, config?: RequestInit) =>
            baseFetch(url, {
                ...config,
                method: 'PATCH',
                body: JSON.stringify(data),
            }),
        delete: (url: string, config?: RequestInit) => baseFetch(url, { ...config, method: 'DELETE' }),
    };
}
```

### Performance Considerations

**Memoization:**

- Hook uses `useCallback` to memoize fetch function
- Prevents re-creating function on every render
- Stable identity for dependency arrays

**CSRF Token Caching:**

- Token is read from DOM on each request
- Consider caching if performance is critical
- Token rarely changes during session

**Bundle Size:**

- Hook adds minimal bundle size (~1-2KB)
- No external dependencies
- Tree-shakeable if using helper methods

### Security Notes

**CSRF Protection:**

- Laravel validates CSRF token on state-changing requests (POST, PUT, PATCH, DELETE)
- GET requests don't require CSRF token
- Token must match session token

**Same-Origin Policy:**

- `credentials: 'same-origin'` includes cookies only for same-origin requests
- Use `credentials: 'include'` for cross-origin with credentials

**HTTPS:**

- Always use HTTPS in production
- CSRF tokens are sensitive data

### Migration Guide

**From Native Fetch:**

Before:

```tsx
const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

const response = await fetch('/api/projects', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': token,
    },
    body: JSON.stringify(data),
});
```

After:

```tsx
const fetch = useFetch();

const response = await fetch('/api/projects', {
    method: 'POST',
    body: JSON.stringify(data),
});
```

**From Axios:**

Before:

```tsx
axios.post('/api/projects', data);
```

After:

```tsx
const fetch = useFetch();
await fetch.post('/api/projects', data);
```

### Dependencies

This feature has no dependencies and can be implemented independently.

**Implementation order:**

- Can be implemented at any time
- Does not depend on other features
- Other features do not depend on this
