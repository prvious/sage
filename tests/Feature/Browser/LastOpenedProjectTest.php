<?php

use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('redirects to projects list when no cached project exists', function () {
    $page = visit('/');

    $page->assertPathIs('/projects')
        ->assertNoJavascriptErrors();
});
