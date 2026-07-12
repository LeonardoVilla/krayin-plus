<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Project\ProjectController;
use Webkul\Admin\Http\Controllers\Project\ProjectTaskController;

Route::controller(ProjectController::class)->prefix('projects')->group(function () {
    Route::get('', 'index')->name('admin.projects.index');

    Route::get('create', 'create')->name('admin.projects.create');

    Route::post('create', 'store')->name('admin.projects.store');

    Route::get('edit/{id}', 'edit')->name('admin.projects.edit');

    Route::put('edit/{id}', 'update')->name('admin.projects.update');

    Route::delete('{id}', 'destroy')->name('admin.projects.delete');

    Route::post('mass-destroy', 'massDestroy')->name('admin.projects.mass_delete');
});

Route::controller(ProjectTaskController::class)->prefix('projects/{projectId}/tasks')->group(function () {
    Route::get('create', 'create')->name('admin.projects.tasks.create');

    Route::post('create', 'store')->name('admin.projects.tasks.store');

    Route::get('kanban', 'kanban')->name('admin.projects.tasks.kanban');

    Route::get('gantt', 'gantt')->name('admin.projects.tasks.gantt');

    Route::get('edit/{id}', 'edit')->name('admin.projects.tasks.edit');

    Route::put('edit/{id}', 'update')->name('admin.projects.tasks.update');

    Route::put('{id}/status', 'updateStatus')->name('admin.projects.tasks.status');

    Route::put('{id}/dates', 'updateDates')->name('admin.projects.tasks.dates');

    Route::delete('{id}', 'destroy')->name('admin.projects.tasks.delete');
});
