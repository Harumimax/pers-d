<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dictionary_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_dictionary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscriber_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_dictionary_id', 'subscriber_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dictionary_subscriptions');
    }
};
