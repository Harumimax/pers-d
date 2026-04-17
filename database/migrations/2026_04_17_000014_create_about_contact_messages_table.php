<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('about_contact_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('contact_email');
            $table->string('subject', 128);
            $table->text('message');
            $table->string('delivery_status', 20)->index();
            $table->timestamp('delivered_at')->nullable();
            $table->text('delivery_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('about_contact_messages');
    }
};
