<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    dd([
    'FRONTEND_URL_env' => env('FRONTEND_URL'),
    'FRONTEND_URL_default' => env('FRONTEND_URL', 'http://localhost:5173'),
    'config_cached' => app()->configurationIsCached(),
]);
    return view('welcome');

});
