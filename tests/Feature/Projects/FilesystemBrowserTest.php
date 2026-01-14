<?php

declare(strict_types=1);

use function Pest\Laravel\get;

it('loads project create page with default home directory', function () {
    $response = get('/projects/create');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('projects/create')
        ->has('directories')
        ->has('breadcrumbs')
        ->has('currentPath')
        ->has('homePath'));
});

it('accepts path query parameter', function () {
    $testPath = getcwd();

    $response = get("/projects/create?path={$testPath}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('currentPath', $testPath));
});

it('returns directory listing for valid path', function () {
    $testPath = getcwd();

    $response = get("/projects/create?path={$testPath}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('directories')
        ->has('breadcrumbs'));
});

it('returns breadcrumbs for path', function () {
    $testPath = getcwd();

    $response = get("/projects/create?path={$testPath}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('breadcrumbs', fn ($breadcrumbs) => $breadcrumbs->each(fn ($crumb) => $crumb
            ->has('name')
            ->has('path'))));
});

it('rejects directory traversal attempts', function () {
    $response = get('/projects/create?path=../../../etc/passwd');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('currentPath', '/'));
});

it('handles invalid paths gracefully', function () {
    $invalidPath = '/this/path/does/not/exist/at/all';

    $response = get("/projects/create?path={$invalidPath}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('directories')
        ->has('currentPath'));
});

it('returns home path', function () {
    $response = get('/projects/create');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('homePath')
        ->where('homePath', fn ($homePath) => ! empty($homePath)));
});

it('navigating to subdirectory updates props', function () {
    $homeDir = getenv('HOME') ?: getenv('USERPROFILE') ?: '/';

    // First load home directory
    $response = get("/projects/create?path={$homeDir}");
    $response->assertOk();

    $directories = $response->viewData('page')['props']['directories'] ?? [];

    if (count($directories) > 0) {
        $subDir = $directories[0]['path'];

        // Navigate to subdirectory
        $response = get("/projects/create?path={$subDir}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('currentPath', $subDir));
    }
});
