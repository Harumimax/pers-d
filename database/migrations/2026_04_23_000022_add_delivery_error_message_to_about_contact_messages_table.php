<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('about_contact_messages', function (Blueprint $table): void {
            $table->text('delivery_error_message')->nullable()->after('delivery_error');
        });
    }

    public function down(): void
    {
        Schema::table('about_contact_messages', function (Blueprint $table): void {
            $table->dropColumn('delivery_error_message');
        });
    }
};
