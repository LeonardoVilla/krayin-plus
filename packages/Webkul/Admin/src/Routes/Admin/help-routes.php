<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HelpController;

Route::get('help', [HelpController::class, 'index'])->name('admin.help.index');
