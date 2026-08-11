<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Points a URL straight at a Livewire component instead of a controller.
    Route::livewire('posts', 'pages::posts')->name('posts');
});

require __DIR__.'/settings.php';
