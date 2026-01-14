<?php

use App\Models\Project;
use App\Models\Spec;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->project = Project::factory()->create();
});

it('displays specs index page', function () {
    Spec::factory()->count(3)->create(['project_id' => $this->project->id]);

    $response = $this->get(route('specs.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Specs/Index')
        ->has('specs.data', 3)
    );
});

it('displays create spec page', function () {
    $response = $this->get(route('specs.create'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Specs/Create'));
});

it('generates spec from idea using AI', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [
                ['text' => '# Feature Spec: User Authentication'],
            ],
        ]),
    ]);

    $response = $this->postJson(route('specs.generate'), [
        'idea' => 'Add user authentication to the app',
        'type' => 'feature',
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'content' => '# Feature Spec: User Authentication',
    ]);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.anthropic.com/v1/messages' &&
               $request->hasHeader('x-api-key') &&
               $request['model'] === config('services.anthropic.model') &&
               str_contains($request['messages'][0]['content'], 'Add user authentication to the app');
    });
});

it('generates API spec type', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [
                ['text' => '# API Spec: REST Endpoints'],
            ],
        ]),
    ]);

    $response = $this->postJson(route('specs.generate'), [
        'idea' => 'Create REST API for user management',
        'type' => 'api',
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'content' => '# API Spec: REST Endpoints',
    ]);
});

it('generates refactor spec type', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [
                ['text' => '# Refactor Spec: Code Optimization'],
            ],
        ]),
    ]);

    $response = $this->postJson(route('specs.generate'), [
        'idea' => 'Refactor authentication system for better performance',
        'type' => 'refactor',
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'content' => '# Refactor Spec: Code Optimization',
    ]);
});

it('generates bug spec type', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [
                ['text' => '# Bug Fix Spec: Login Issue'],
            ],
        ]),
    ]);

    $response = $this->postJson(route('specs.generate'), [
        'idea' => 'Users cannot login with valid credentials',
        'type' => 'bug',
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'content' => '# Bug Fix Spec: Login Issue',
    ]);
});

it('requires idea for spec generation', function () {
    $response = $this->postJson(route('specs.generate'), [
        'type' => 'feature',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['idea']);
});

it('requires type for spec generation', function () {
    $response = $this->postJson(route('specs.generate'), [
        'idea' => 'Add user authentication',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['type']);
});

it('validates idea minimum length', function () {
    $response = $this->postJson(route('specs.generate'), [
        'idea' => 'short',
        'type' => 'feature',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['idea']);
});

it('validates type is one of allowed values', function () {
    $response = $this->postJson(route('specs.generate'), [
        'idea' => 'Add user authentication to the app',
        'type' => 'invalid',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['type']);
});

it('handles AI service errors gracefully', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response('API Error', 500),
    ]);

    $response = $this->postJson(route('specs.generate'), [
        'idea' => 'Add user authentication',
        'type' => 'feature',
    ]);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
    ]);
});

it('stores a new spec', function () {
    $response = $this->post(route('specs.store'), [
        'project_id' => $this->project->id,
        'title' => 'User Authentication Feature',
        'content' => '# Feature Spec',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('specs', [
        'project_id' => $this->project->id,
        'title' => 'User Authentication Feature',
        'content' => '# Feature Spec',
    ]);
});

it('requires project_id when storing spec', function () {
    $response = $this->post(route('specs.store'), [
        'title' => 'User Authentication Feature',
        'content' => '# Feature Spec',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['project_id']);
});

it('requires title when storing spec', function () {
    $response = $this->post(route('specs.store'), [
        'project_id' => $this->project->id,
        'content' => '# Feature Spec',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['title']);
});

it('requires content when storing spec', function () {
    $response = $this->post(route('specs.store'), [
        'project_id' => $this->project->id,
        'title' => 'User Authentication Feature',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['content']);
});

it('displays spec details', function () {
    $spec = Spec::factory()->create(['project_id' => $this->project->id]);

    $response = $this->get(route('specs.show', $spec));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Specs/Show')
        ->has('spec')
    );
});

it('displays edit spec page', function () {
    $spec = Spec::factory()->create(['project_id' => $this->project->id]);

    $response = $this->get(route('specs.edit', $spec));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Specs/Edit'));
});

it('updates a spec', function () {
    $spec = Spec::factory()->create(['project_id' => $this->project->id]);

    $response = $this->put(route('specs.update', $spec), [
        'title' => 'Updated Title',
        'content' => '# Updated Content',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('specs', [
        'id' => $spec->id,
        'title' => 'Updated Title',
        'content' => '# Updated Content',
    ]);
});

it('deletes a spec', function () {
    $spec = Spec::factory()->create(['project_id' => $this->project->id]);

    $response = $this->delete(route('specs.destroy', $spec));

    $response->assertRedirect();
    $this->assertDatabaseMissing('specs', ['id' => $spec->id]);
});

it('refines existing spec with feedback', function () {
    $spec = Spec::factory()->create([
        'project_id' => $this->project->id,
        'content' => '# Original Spec',
    ]);

    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [
                ['text' => '# Refined Spec'],
            ],
        ]),
    ]);

    $response = $this->postJson(route('specs.refine', $spec), [
        'feedback' => 'Add more details about edge cases',
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'content' => '# Refined Spec',
    ]);

    Http::assertSent(function ($request) {
        return str_contains($request['messages'][0]['content'], '# Original Spec') &&
               str_contains($request['messages'][0]['content'], 'Add more details about edge cases');
    });
});

it('requires feedback for spec refinement', function () {
    $spec = Spec::factory()->create(['project_id' => $this->project->id]);

    $response = $this->postJson(route('specs.refine', $spec), []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['feedback']);
});

it('validates feedback minimum length for refinement', function () {
    $spec = Spec::factory()->create(['project_id' => $this->project->id]);

    $response = $this->postJson(route('specs.refine', $spec), [
        'feedback' => 'short',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['feedback']);
});

it('handles refinement AI service errors gracefully', function () {
    $spec = Spec::factory()->create(['project_id' => $this->project->id]);

    Http::fake([
        'api.anthropic.com/*' => Http::response('API Error', 500),
    ]);

    $response = $this->postJson(route('specs.refine', $spec), [
        'feedback' => 'Add more details about edge cases',
    ]);

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
    ]);
});
