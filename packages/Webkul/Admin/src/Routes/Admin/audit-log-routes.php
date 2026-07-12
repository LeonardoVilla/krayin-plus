<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuditLogController;

Route::get('audit-log', [AuditLogController::class, 'index'])->name('admin.audit_log.index');
