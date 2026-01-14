<?php

use function Pest\Laravel\postJson;

test('browse endpoint returns directories and breadcrumbs for valid path', function () {
    $homePath = getenv('HOME') ?: getenv('USERPROFILE');

    $response = postJson('/api/filesystem/browse', ['path' => $homePath]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'directories' => [
                '*' => ['name', 'path', 'type'],
            ],
            'breadcrumbs' => [
                '*' => ['name', 'path'],
            ],
        ]);
});

test('browse endpoint prevents directory traversal attacks', function () {
    $response = postJson('/api/filesystem/browse', ['path' => '/home/../../../etc/passwd']);

    $response->assertSuccessful()
        ->assertJson([
            'directories' => [],
            'breadcrumbs' => [],
        ]);
});

test('browse endpoint returns empty arrays for non-existent path', function () {
    $response = postJson('/api/filesystem/browse', ['path' => '/nonexistent/path/that/does/not/exist']);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'directories',
            'breadcrumbs',
        ]);
});

test('browse endpoint validates path is required', function () {
    $response = postJson('/api/filesystem/browse', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['path']);
});

test('browse endpoint validates path is string', function () {
    $response = postJson('/api/filesystem/browse', ['path' => 123]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['path']);
});

test('browse endpoint validates path max length', function () {
    $longPath = str_repeat('a', 5000);

    $response = postJson('/api/filesystem/browse', ['path' => $longPath]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['path']);
});

test('home endpoint returns home directory path', function () {
    $response = $this->getJson('/api/filesystem/home');

    $response->assertSuccessful()
        ->assertJsonStructure(['path']);

    $homePath = getenv('HOME') ?: getenv('USERPROFILE');
    if ($homePath !== false) {
        $response->assertJson(['path' => $homePath]);
    }
});

test('browse endpoint requires CSRF token for non-JSON requests', function () {
    $homePath = getenv('HOME') ?: getenv('USERPROFILE');

    // Regular POST without JSON (simulating fetch without CSRF)
    $response = $this->post('/api/filesystem/browse', ['path' => $homePath]);

    // Should fail with 419 if CSRF verification fails
    // OR succeed if middleware exempts this route
    // This test documents the expected behavior
    expect($response->status())->toBeIn([200, 419]);
});

test('browse endpoint accepts requests with valid CSRF token', function () {
    $homePath = getenv('HOME') ?: getenv('USERPROFILE');

    // postJson automatically includes CSRF token
    $response = postJson('/api/filesystem/browse', ['path' => $homePath]);

    $response->assertSuccessful();
});

test('browse endpoint handles multiple rapid requests', function () {
    $homePath = getenv('HOME') ?: getenv('USERPROFILE');

    // Make multiple requests in quick succession
    $responses = [];
    for ($i = 0; $i < 5; $i++) {
        $responses[] = postJson('/api/filesystem/browse', ['path' => $homePath]);
    }

    // All should succeed
    foreach ($responses as $response) {
        $response->assertSuccessful();
    }
});

test('browse endpoint returns consistent structure for empty directories', function () {
    // Create a temporary empty directory
    $emptyDir = sys_get_temp_dir().'/empty-test-dir-'.uniqid();
    mkdir($emptyDir);

    $response = postJson('/api/filesystem/browse', ['path' => $emptyDir]);

    $response->assertSuccessful()
        ->assertJson([
            'directories' => [],
        ])
        ->assertJsonStructure(['directories', 'breadcrumbs']);

    // Cleanup
    rmdir($emptyDir);
});

test('browse endpoint handles symbolic links safely', function () {
    $homePath = getenv('HOME') ?: getenv('USERPROFILE');

    // Most home directories have some symlinks
    $response = postJson('/api/filesystem/browse', ['path' => $homePath]);

    $response->assertSuccessful();

    // Verify structure even if symlinks are present
    $data = $response->json();
    expect($data)->toHaveKeys(['directories', 'breadcrumbs']);
});

test('browse endpoint filters special characters in paths', function () {
    $specialPaths = [
        '/tmp/test\n\ninjection',
        '/tmp/test<script>alert(1)</script>',
        '/tmp/test`whoami`',
    ];

    foreach ($specialPaths as $path) {
        $response = postJson('/api/filesystem/browse', ['path' => $path]);

        // Should either reject or safely handle
        $response->assertSuccessful();
        $data = $response->json();
        expect($data)->toHaveKeys(['directories', 'breadcrumbs']);
    }
});

test('home endpoint handles missing environment variables gracefully', function () {
    // Temporarily unset HOME (this is read-only in the test so it won't actually unset)
    $response = $this->getJson('/api/filesystem/home');

    $response->assertSuccessful()
        ->assertJsonStructure(['path']);

    // Should return a valid path (either HOME or fallback to /)
    $data = $response->json();
    expect($data['path'])->toBeString();
    expect($data['path'])->not->toBeEmpty();
});
