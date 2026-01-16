<?php

use App\Http\Controllers\AnimalFilterController;
use Illuminate\Support\Facades\Route;

Route::get('/animals/filters', [AnimalFilterController::class, 'filters']);
