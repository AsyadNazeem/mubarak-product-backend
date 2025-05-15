<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add missing columns to categories table
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'description')) {
                $table->text('description')->nullable();
            }

            if (!Schema::hasColumn('categories', 'status')) {
                $table->enum('status', ['active', 'inactive'])->default('active');
            }

            if (!Schema::hasColumn('categories', 'image_path')) {
                $table->string('image_path')->nullable();
            }
        });

        // Add missing columns to sub_categories table
        Schema::table('sub_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('sub_categories', 'description')) {
                $table->text('description')->nullable();
            }

            if (!Schema::hasColumn('sub_categories', 'status')) {
                $table->enum('status', ['active', 'inactive'])->default('active');
            }

            if (!Schema::hasColumn('sub_categories', 'image_path')) {
                $table->string('image_path')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['description', 'status', 'image_path']);
        });

        Schema::table('sub_categories', function (Blueprint $table) {
            $table->dropColumn(['description', 'status', 'image_path']);
        });
    }
};
