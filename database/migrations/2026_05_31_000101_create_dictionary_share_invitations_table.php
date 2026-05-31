<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dictionary_share_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_dictionary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('target_email');
            $table->string('token_hash', 64)->unique();
            $table->string('status', 32);
            $table->timestampTz('expires_at');
            $table->timestampTz('accepted_at')->nullable();
            $table->timestampsTz();

            $table->index(['user_dictionary_id', 'status']);
            $table->index(['owner_user_id', 'status']);
            $table->index(['target_email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dictionary_share_invitations');
    }
};
