<?php

use App\Models\PasswordResetMailDelivery;
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
        Schema::create('password_reset_mail_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('locale', 5)->nullable();
            $table->string('delivery_status')->default(PasswordResetMailDelivery::STATUS_PENDING);
            $table->timestamp('delivered_at')->nullable();
            $table->string('delivery_error')->nullable();
            $table->text('delivery_error_message')->nullable();
            $table->timestamps();

            $table->index('email');
            $table->index('delivery_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_mail_deliveries');
    }
};
