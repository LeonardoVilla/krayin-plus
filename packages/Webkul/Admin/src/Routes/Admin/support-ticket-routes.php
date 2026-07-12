<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\SupportTicket\SupportTicketController;

Route::controller(SupportTicketController::class)->prefix('support-tickets')->group(function () {
    Route::get('', 'index')->name('admin.support_tickets.index');

    Route::get('create', 'create')->name('admin.support_tickets.create');

    Route::post('create', 'store')->name('admin.support_tickets.store');

    Route::get('edit/{id}', 'edit')->name('admin.support_tickets.edit');

    Route::put('edit/{id}', 'update')->name('admin.support_tickets.update');

    Route::delete('{id}', 'destroy')->name('admin.support_tickets.delete');

    Route::post('mass-destroy', 'massDestroy')->name('admin.support_tickets.mass_delete');
});
