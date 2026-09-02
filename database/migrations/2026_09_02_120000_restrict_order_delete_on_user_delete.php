<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Orders must outlive the customer record.
 *
 * `orders.user_id` was created with cascadeOnDelete, so deleting a user also
 * destroyed every order they had ever placed — the revenue vanished from the
 * books. Account deletion anonymises the user instead (see
 * AuthController::deleteAccount), and this restriction makes the destructive
 * path fail loudly rather than quietly shredding sales history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
