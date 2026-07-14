<?php

use Illuminate\Support\Facades\Route;

Route::get('/docs/openapi.yaml', function () {
    return response()->file(base_path('docs/openapi.yaml'), [
        'Content-Type' => 'application/yaml',
    ]);
});

Route::view('/docs', 'docs.swagger');
