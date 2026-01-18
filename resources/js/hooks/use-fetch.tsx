import { useCallback } from "react";

interface UseFetchOptions extends RequestInit {
    skipCsrf?: boolean;
    skipJsonHeaders?: boolean;
}

type FetchFunction = {
    (url: RequestInfo | URL, init?: UseFetchOptions): Promise<Response>;
    json: <T = unknown>(
        url: RequestInfo | URL,
        init?: UseFetchOptions,
    ) => Promise<T>;
    post: (
        url: RequestInfo | URL,
        body?: unknown,
        init?: UseFetchOptions,
    ) => Promise<Response>;
    put: (
        url: RequestInfo | URL,
        body?: unknown,
        init?: UseFetchOptions,
    ) => Promise<Response>;
    patch: (
        url: RequestInfo | URL,
        body?: unknown,
        init?: UseFetchOptions,
    ) => Promise<Response>;
    delete: (
        url: RequestInfo | URL,
        init?: UseFetchOptions,
    ) => Promise<Response>;
};

function getCsrfToken(): string | null {
    const meta = document.querySelector<HTMLMetaElement>(
        'meta[name="csrf-token"]',
    );
    return meta?.content ?? null;
}

export function useFetch(): FetchFunction {
    const fetchFn = useCallback<FetchFunction>(
        async (url: RequestInfo | URL, init?: UseFetchOptions) => {
            const {
                skipCsrf = false,
                skipJsonHeaders = false,
                headers = {},
                ...restInit
            } = init ?? {};

            const defaultHeaders: HeadersInit = {};

            // Add CSRF token if not skipped
            if (!skipCsrf) {
                const token = getCsrfToken();
                if (token) {
                    defaultHeaders["X-CSRF-TOKEN"] = token;
                }
            }

            // Add JSON headers if not skipped
            if (!skipJsonHeaders) {
                defaultHeaders["Content-Type"] = "application/json";
                defaultHeaders["Accept"] = "application/json";
            }

            // Always add X-Requested-With for Laravel to detect AJAX requests
            defaultHeaders["X-Requested-With"] = "XMLHttpRequest";

            const mergedHeaders = { ...defaultHeaders, ...headers };

            return window.fetch(url, {
                ...restInit,
                headers: mergedHeaders,
                credentials: restInit.credentials ?? "same-origin",
            });
        },
        [],
    ) as FetchFunction;

    // Helper method: automatic JSON parsing
    fetchFn.json = async <T = unknown,>(
        url: RequestInfo | URL,
        init?: UseFetchOptions,
    ): Promise<T> => {
        const response = await fetchFn(url, init);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return (await response.json()) as T;
    };

    // Helper method: POST request
    fetchFn.post = async (
        url: RequestInfo | URL,
        body?: unknown,
        init?: UseFetchOptions,
    ): Promise<Response> => {
        return fetchFn(url, {
            ...init,
            method: "POST",
            body: body ? JSON.stringify(body) : undefined,
        });
    };

    // Helper method: PUT request
    fetchFn.put = async (
        url: RequestInfo | URL,
        body?: unknown,
        init?: UseFetchOptions,
    ): Promise<Response> => {
        return fetchFn(url, {
            ...init,
            method: "PUT",
            body: body ? JSON.stringify(body) : undefined,
        });
    };

    // Helper method: PATCH request
    fetchFn.patch = async (
        url: RequestInfo | URL,
        body?: unknown,
        init?: UseFetchOptions,
    ): Promise<Response> => {
        return fetchFn(url, {
            ...init,
            method: "PATCH",
            body: body ? JSON.stringify(body) : undefined,
        });
    };

    // Helper method: DELETE request
    fetchFn.delete = async (
        url: RequestInfo | URL,
        init?: UseFetchOptions,
    ): Promise<Response> => {
        return fetchFn(url, {
            ...init,
            method: "DELETE",
        });
    };

    return fetchFn;
}
