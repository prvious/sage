<?php

declare(strict_types=1);

use App\Support\Toast;
use Inertia\Testing\AssertableInertia as Assert;

it('flashes single toast and appears in Inertia response', function () {
    Toast::success('Operation completed successfully')->flash();

    $this->get('/projects')
        ->assertInertia(fn (Assert $page) => $page
            ->hasFlash('toasts')
            ->hasFlash('toasts.0.type', 'success')
            ->hasFlash('toasts.0.message', 'Operation completed successfully')
        );
});

it('flashes toast with description', function () {
    Toast::error('Failed to process')
        ->description('Please check your input')
        ->flash();

    $this->get('/projects')
        ->assertInertia(fn (Assert $page) => $page
            ->hasFlash('toasts.0.type', 'error')
            ->hasFlash('toasts.0.message', 'Failed to process')
            ->hasFlash('toasts.0.description', 'Please check your input')
        );
});

it('flashes toast with custom duration', function () {
    Toast::info('Processing in background')
        ->duration(6000)
        ->flash();

    $this->get('/projects')
        ->assertInertia(fn (Assert $page) => $page
            ->hasFlash('toasts.0.type', 'info')
            ->hasFlash('toasts.0.message', 'Processing in background')
            ->hasFlash('toasts.0.duration', 6000)
        );
});

it('flashes toast with all options', function () {
    Toast::warning('Action required')
        ->description('Your subscription expires in 3 days')
        ->duration(8000)
        ->flash();

    $this->get('/projects')
        ->assertInertia(fn (Assert $page) => $page
            ->hasFlash('toasts.0.type', 'warning')
            ->hasFlash('toasts.0.message', 'Action required')
            ->hasFlash('toasts.0.description', 'Your subscription expires in 3 days')
            ->hasFlash('toasts.0.duration', 8000)
        );
});

it('accumulates multiple toasts', function () {
    Toast::success('Data saved')->flash();
    Toast::info('Email sent to admin')->duration(3000)->flash();

    $this->get('/projects')
        ->assertInertia(fn (Assert $page) => $page
            ->hasFlash('toasts.0.type', 'success')
            ->hasFlash('toasts.0.message', 'Data saved')
            ->hasFlash('toasts.1.type', 'info')
            ->hasFlash('toasts.1.message', 'Email sent to admin')
            ->hasFlash('toasts.1.duration', 3000)
        );
});

it('flashes different toast types correctly', function () {
    Toast::success('Success message')->flash();

    $this->get('/projects')
        ->assertInertia(fn (Assert $page) => $page
            ->hasFlash('toasts.0.type', 'success')
        );
})->with([
    ['success', 'Success message'],
    ['error', 'Error message'],
    ['info', 'Info message'],
    ['warning', 'Warning message'],
]);

it('toasts are available in flash data after flashing', function () {
    Toast::success('Feature created!')->flash();

    $this->get('/projects')
        ->assertInertia(fn (Assert $page) => $page
            ->hasFlash('toasts.0.type', 'success')
            ->hasFlash('toasts.0.message', 'Feature created!')
        );
});

it('handles toast without optional fields', function () {
    Toast::success('Simple message')->flash();

    $this->get('/projects')
        ->assertInertia(fn (Assert $page) => $page
            ->hasFlash('toasts.0.type', 'success')
            ->hasFlash('toasts.0.message', 'Simple message')
            ->missingFlash('toasts.0.description')
            ->missingFlash('toasts.0.duration')
        );
});
