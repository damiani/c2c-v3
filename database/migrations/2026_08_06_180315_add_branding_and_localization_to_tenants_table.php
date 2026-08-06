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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('name');
            $table->string('logo_path')->nullable()->after('status');
            $table->string('primary_color', 7)->default('#2563eb')->after('logo_path');
            $table->string('accent_color', 7)->default('#16a34a')->after('primary_color');
            $table->string('sender_name')->nullable()->after('accent_color');
            $table->string('sender_email')->nullable()->after('sender_name');
            $table->string('default_locale', 8)->default('en')->after('sender_email');
            $table->json('supported_locales')->nullable()->after('default_locale');
            $table->json('enabled_integrations')->nullable()->after('supported_locales');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'display_name',
                'logo_path',
                'primary_color',
                'accent_color',
                'sender_name',
                'sender_email',
                'default_locale',
                'supported_locales',
                'enabled_integrations',
            ]);
        });
    }
};
