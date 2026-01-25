<?php

use App\Models\Project;

beforeEach(function () {
    $this->project = Project::factory()->create();
});

it('displays success toast after server flash', function () {
    session()->flash('toasts', [
        [
            'type' => 'success',
            'message' => 'Operation successful!',
        ],
    ]);

    $page = visit("/projects/{$this->project->id}/dashboard");

    // Wait for toast to appear
    $page->waitFor('li[data-sonner-toast]', 2);

    // Assert toast contains success message
    $page->assertSee('Operation successful!');
});

it('displays error toast after server flash', function () {
    session()->flash('toasts', [
        [
            'type' => 'error',
            'message' => 'Operation failed!',
        ],
    ]);

    $page = visit("/projects/{$this->project->id}/dashboard");

    $page->waitFor('li[data-sonner-toast]', 2);
    $page->assertSee('Operation failed!');
});

it('displays toast with description', function () {
    session()->flash('toasts', [
        [
            'type' => 'success',
            'message' => 'Task completed',
            'description' => 'All items processed successfully',
        ],
    ]);

    $page = visit("/projects/{$this->project->id}/dashboard");

    $page->waitFor('li[data-sonner-toast]', 2);
    $page->assertSee('Task completed');
    $page->assertSee('All items processed successfully');
});

it('displays multiple toasts in sequence', function () {
    session()->flash('toasts', [
        [
            'type' => 'success',
            'message' => 'First toast',
        ],
        [
            'type' => 'info',
            'message' => 'Second toast',
        ],
    ]);

    $page = visit("/projects/{$this->project->id}/dashboard");

    $page->waitFor('li[data-sonner-toast]', 2);
    $page->assertSee('First toast');
    $page->assertSee('Second toast');
});
