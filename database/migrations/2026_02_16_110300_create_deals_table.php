<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('group')->nullable();        // "Pizza Deals", "Burger Deals", ...
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('tag')->nullable();          // e.g. "Special"
            $table->string('image_url')->nullable();
            $table->boolean('requires_selection')->default(false);
            $table->string('selection_size')->nullable(); // Small | Medium | Large
            $table->unsignedInteger('selection_count')->default(1);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
