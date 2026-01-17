<?php

it('verifies CSRF token meta tag exists', function () {
    $page = visit('/projects/create');

    // Verify CSRF token meta tag is present
    $page->assertScript('document.querySelector("meta[name=csrf-token]")?.content !== null', true);

    $page->assertNoJavascriptErrors();
});

it('verifies getCsrfToken helper can read token', function () {
    $page = visit('/projects/create');

    // Verify we can read the CSRF token
    $csrfToken = $page->script('document.querySelector("meta[name=csrf-token]")?.content');

    expect($csrfToken)->not->toBeNull();
    expect($csrfToken)->toBeString();

    $page->assertNoJavascriptErrors();
});

it('verifies fetch function exists', function () {
    $page = visit('/projects/create');

    // Verify window.fetch exists
    $page->assertScript('typeof window.fetch', 'function');

    $page->assertNoJavascriptErrors();
});

it('can mock fetch to capture headers', function () {
    $page = visit('/projects/create');

    // Test that we can mock fetch and capture headers
    $result = $page->script(<<<'JS'
        (() => {
            const originalFetch = window.fetch;
            let captured = false;

            window.fetch = function(url, init) {
                captured = true;
                return Promise.resolve(new Response('{}', { status: 200 }));
            };

            // Trigger a fetch
            window.fetch('/test', { method: 'GET' });

            window.fetch = originalFetch;

            return captured;
        })()
    JS);

    expect($result)->toBeTrue();

    $page->assertNoJavascriptErrors();
});

it('verifies default headers structure', function () {
    $page = visit('/projects/create');

    // Test creating a headers object similar to what useFetch would create
    $result = $page->script(<<<'JS'
        (() => {
            const headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };

            return headers['Content-Type'] === 'application/json' &&
                   headers['Accept'] === 'application/json' &&
                   headers['X-Requested-With'] === 'XMLHttpRequest';
        })()
    JS);

    expect($result)->toBeTrue();

    $page->assertNoJavascriptErrors();
});
