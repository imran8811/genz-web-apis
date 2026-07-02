<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pizzas a deal lets the customer choose from.
        Schema::create('deal_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('food_item_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['deal_id', 'food_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_options');
    }
};
