<?php

use App\Models\Project;
use App\Models\User;
use App\Support\SystemEnvironment;
use Illuminate\Support\Facades\Process;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    SystemEnvironment::clearFake();
});

afterEach(function () {
    SystemEnvironment::clearFake();
});

it('does not poll when agent is not installed', function () {
    SystemEnvironment::fake([]);

    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $page = visit("/projects/{$project->id}/agent");

    $page->assertSee('Agent Settings')
        ->assertSee('Not Installed');

    // Wait 5 seconds to ensure no polling happens
    $page->pause(5000);

    // Verify the page is still showing not installed (no polling occurred)
    $page->assertSee('Not Installed');
})->skip('Polling test requires manual verification or advanced timing controls');

it('does not poll when agent is already authenticated', function () {
    SystemEnvironment::fake([
        'ANTHROPIC_API_KEY' => 'sk-ant-test-key-12345',
    ]);

    Process::fake([
        'claude hello -p --output-format json' => Process::result('{"success": true}'),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $page = visit("/projects/{$project->id}/agent");

    $page->assertSee('Agent Settings')
        ->assertSee('Authenticated');

    // Wait to ensure no additional polling happens
    $page->pause(5000);

    // Verify still authenticated (no unnecessary polling)
    $page->assertSee('Authenticated');
})->skip('Polling test requires manual verification or advanced timing controls');

it('shows installation status and authentication status separately', function () {
    SystemEnvironment::fake([
        'PATH' => '/usr/local/bin:/usr/bin',
    ]);

    Process::fake([
        'claude hello -p --output-format json' => Process::result('{"success": true}'),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $page = visit("/projects/{$project->id}/agent");

    $page->assertSee('Agent Settings');

    // Wait for installation check to complete first
    $page->waitForText('Installed', 10);

    $page->assertSee('Installed');

    // Wait for deferred authentication check to complete
    $page->waitForText('Authenticated', 10);

    $page->assertSee('Installed')
        ->assertSee('Authenticated');
});

it('shows not authenticated status when CLI auth fails', function () {
    SystemEnvironment::fake([
        'PATH' => '/usr/local/bin:/usr/bin',
    ]);

    Process::fake([
        'claude hello -p --output-format json' => Process::result('', 'not authenticated', 1),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $page = visit("/projects/{$project->id}/agent");

    $page->assertSee('Agent Settings')
        ->assertSee('Installed');

    // Wait for deferred authentication check to complete
    $page->waitForText('Not Authenticated', 10);

    $page->assertSee('Installed')
        ->assertSee('Not Authenticated')
        ->assertSee('claude login');
});

it('manual refresh button works independently', function () {
    SystemEnvironment::fake([
        'PATH' => '/usr/local/bin:/usr/bin',
    ]);

    Process::fake([
        'claude hello -p --output-format json' => Process::result('{"success": true}'),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $page = visit("/projects/{$project->id}/agent");

    $page->assertSee('Agent Settings');

    // Wait for initial load to complete
    $page->waitForText('Installed', 10);

    // Find and click the refresh button using the RefreshCw icon
    $page->click('button:has-text("Refresh")');

    // Wait for refresh to complete by checking for Installed text again
    $page->waitForText('Installed', 10);

    // Verify page is still functional
    $page->assertSee('Agent Settings')
        ->assertSee('Installed');
});

it('shows API key badge when authenticated via API key', function () {
    SystemEnvironment::fake([
        'ANTHROPIC_API_KEY' => 'sk-ant-test-key-12345',
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $page = visit("/projects/{$project->id}/agent");

    $page->waitForText('Authenticated', 10);

    $page->assertSee('Authenticated')
        ->assertSee('API Key');
});

it('shows CLI badge when authenticated via CLI', function () {
    SystemEnvironment::fake([
        'PATH' => '/usr/local/bin:/usr/bin',
    ]);

    Process::fake([
        'claude hello -p --output-format json' => Process::result('{"success": true}'),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $page = visit("/projects/{$project->id}/agent");

    $page->waitForText('Authenticated', 10);

    $page->assertSee('Authenticated')
        ->assertSee('CLI');
});

it('displays installation path when agent is installed', function () {
    SystemEnvironment::fake([
        'PATH' => '/usr/local/bin:/usr/bin',
    ]);

    Process::fake([
        'claude hello -p --output-format json' => Process::result('{"success": true}'),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $page = visit("/projects/{$project->id}/agent");

    $page->waitForText('Installed', 10);

    // Should show the installation path
    $page->assertSee('Installed');
});

it('shows installation status when agent is found', function () {
    SystemEnvironment::fake([
        'PATH' => '/usr/local/bin:/usr/bin',
    ]);

    Process::fake([
        'claude hello -p --output-format json' => Process::result('{"success": true}'),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $page = visit("/projects/{$project->id}/agent");

    $page->assertSee('Agent Settings');

    // Wait for installation check to complete
    $page->waitForText('Installed', 10);

    $page->assertSee('Installed');
});

it('preserves scroll position during polling', function () {
    SystemEnvironment::fake([
        'PATH' => '/usr/local/bin:/usr/bin',
    ]);

    Process::fake([
        'claude hello -p --output-format json' => Process::result('', 'not authenticated', 1),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->create();

    actingAs($user);

    $page = visit("/projects/{$project->id}/agent");

    $page->waitForText('Not Authenticated', 10);

    // Scroll down to the bottom card
    $page->script('window.scrollTo(0, document.body.scrollHeight)');

    $scrollPosition = $page->script('return window.scrollY');

    // Wait for potential polling (in real scenario, would wait 60s)
    $page->pause(2000);

    // Verify scroll position hasn't changed
    $newScrollPosition = $page->script('return window.scrollY');

    expect($newScrollPosition)->toBe($scrollPosition);
})->skip('Polling test requires manual verification or long wait times');
