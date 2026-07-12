<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model_type', 150);
            $table->string('model_id', 50);
            $table->string('model_label', 255)->default('');
            $table->enum('action', ['insert', 'update', 'delete']);
            $table->unsignedInteger('user_id')->nullable();
            $table->string('user_name', 100)->default('');
            $table->mediumText('field_changes')->nullable();
            $table->string('ip_address', 45)->default('');
            $table->timestamp('created_at')->useCurrent();

            $table->index('model_type');
            $table->index('model_id');
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
