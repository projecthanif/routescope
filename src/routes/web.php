<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Projecthanif\LaravelRouteLens\Controllers\RouteLensController;

Route::get('/', [RouteLensController::class, 'index'])->name('index');
