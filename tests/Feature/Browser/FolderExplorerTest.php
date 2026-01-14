<?php

test('can open folder browser dialog', function () {
    $page = visit('/projects/create');

    $page->assertSee('Create Project')
        ->assertSee('Project Path')
        ->assertSee('Browse')
        ->click('Browse')
        ->assertSee('Select Project Path')
        ->assertSee('Browse Folders')
        ->assertNoJavascriptErrors();
});

test('can navigate folders in browser', function () {
    $page = visit('/projects/create');

    $page->click('Browse')
        ->waitFor('[class*="breadcrumb"]')
        ->assertNoJavascriptErrors();
});

test('can select folder path', function () {
    $page = visit('/projects/create');

    $page->click('Browse')
        ->waitFor('button:has-text("Select")')
        ->click('button:has-text("Select")')
        ->assertNoJavascriptErrors();
});

test('folder browser shows home button', function () {
    $page = visit('/projects/create');

    $page->click('Browse')
        ->assertSee('Home')
        ->assertNoJavascriptErrors();
});
