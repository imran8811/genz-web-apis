<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();        // Home, Work, ...
            $table->string('recipient_name');
            $table->string('phone', 30);
            $table->string('address_line_1');           // House / street
            $table->string('address_line_2')->nullable();
            $table->string('area')->nullable();         // locality / sector
            $table->string('city')->default('Multan');
            $table->string('landmark')->nullable();
            $table->string('country', 100)->default('Pakistan');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_addresses');
    }
};
