<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('description')->nullable();
            $table->enum('status', ['planning', 'in_progress', 'completed', 'cancelled'])->default('planning');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('lead_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('title');
            $table->enum('status', ['pending', 'in_progress', 'done'])->default('pending');
            $table->date('due_date')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
        Schema::dropIfExists('projects');
    }
};
