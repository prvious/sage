<?php

declare(strict_types=1);

use App\Actions\Brainstorm\ParseGeneratedIdeas;

uses()->group('brainstorm', 'unit');

it('parses valid JSON response', function () {
    $response = json_encode([
        [
            'title' => 'API Rate Limiting',
            'description' => 'Implement rate limiting for API endpoints',
            'priority' => 'high',
            'category' => 'feature',
        ],
        [
            'title' => 'Error Tracking',
            'description' => 'Add error tracking service integration',
            'priority' => 'medium',
            'category' => 'infrastructure',
        ],
    ]);

    $action = new ParseGeneratedIdeas;
    $ideas = $action->handle($response);

    expect($ideas)->toHaveCount(2);
    expect($ideas[0])->toHaveKeys(['title', 'description', 'priority', 'category']);
    expect($ideas[0]['title'])->toBe('API Rate Limiting');
    expect($ideas[0]['priority'])->toBe('high');
});

it('extracts JSON from markdown code block', function () {
    $response = "```json\n".json_encode([
        [
            'title' => 'Test Feature',
            'description' => 'Test description',
            'priority' => 'low',
            'category' => 'feature',
        ],
    ])."\n```";

    $action = new ParseGeneratedIdeas;
    $ideas = $action->handle($response);

    expect($ideas)->toHaveCount(1);
    expect($ideas[0]['title'])->toBe('Test Feature');
});

it('normalizes priority values', function () {
    $response = json_encode([
        ['title' => 'A', 'description' => 'Desc', 'priority' => 'urgent', 'category' => 'feature'],
        ['title' => 'B', 'description' => 'Desc', 'priority' => 'critical', 'category' => 'feature'],
        ['title' => 'C', 'description' => 'Desc', 'priority' => 'minor', 'category' => 'feature'],
        ['title' => 'D', 'description' => 'Desc', 'priority' => 'unknown', 'category' => 'feature'],
    ]);

    $action = new ParseGeneratedIdeas;
    $ideas = $action->handle($response);

    expect($ideas[0]['priority'])->toBe('high');
    expect($ideas[1]['priority'])->toBe('high');
    expect($ideas[2]['priority'])->toBe('low');
    expect($ideas[3]['priority'])->toBe('medium'); // default
});

it('normalizes category values', function () {
    $response = json_encode([
        ['title' => 'A', 'description' => 'Desc', 'priority' => 'high', 'category' => 'new feature'],
        ['title' => 'B', 'description' => 'Desc', 'priority' => 'high', 'category' => 'improvement'],
        ['title' => 'C', 'description' => 'Desc', 'priority' => 'high', 'category' => 'infra'],
        ['title' => 'D', 'description' => 'Desc', 'priority' => 'high', 'category' => 'unknown'],
    ]);

    $action = new ParseGeneratedIdeas;
    $ideas = $action->handle($response);

    expect($ideas[0]['category'])->toBe('feature');
    expect($ideas[1]['category'])->toBe('enhancement');
    expect($ideas[2]['category'])->toBe('infrastructure');
    expect($ideas[3]['category'])->toBe('feature'); // default
});

it('throws exception for invalid JSON', function () {
    $action = new ParseGeneratedIdeas;

    expect(fn () => $action->handle('not json'))
        ->toThrow(InvalidArgumentException::class, 'Could not extract valid JSON');
});

it('throws exception for missing title', function () {
    $response = json_encode([
        ['description' => 'Desc', 'priority' => 'high', 'category' => 'feature'],
    ]);

    $action = new ParseGeneratedIdeas;

    expect(fn () => $action->handle($response))
        ->toThrow(InvalidArgumentException::class, 'must have a title');
});

it('throws exception for missing description', function () {
    $response = json_encode([
        ['title' => 'Title', 'priority' => 'high', 'category' => 'feature'],
    ]);

    $action = new ParseGeneratedIdeas;

    expect(fn () => $action->handle($response))
        ->toThrow(InvalidArgumentException::class, 'must have a description');
});

it('uses default values for missing priority and category', function () {
    $response = json_encode([
        ['title' => 'Title', 'description' => 'Description'],
    ]);

    $action = new ParseGeneratedIdeas;
    $ideas = $action->handle($response);

    expect($ideas[0]['priority'])->toBe('medium');
    expect($ideas[0]['category'])->toBe('feature');
});
