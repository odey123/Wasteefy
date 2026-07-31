<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// This app has no browser-based login page (admin auth is API-token only via
// /api/admin/login). This route exists solely so Laravel's auth middleware
// has a "login" route to redirect to when a request doesn't ask for JSON,
// instead of crashing with a RouteNotFoundException.
Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');
