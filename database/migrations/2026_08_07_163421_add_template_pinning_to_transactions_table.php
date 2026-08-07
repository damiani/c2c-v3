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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('transaction_template_id')->nullable()->after('owner_user_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('transaction_template_version')->nullable()->after('transaction_template_id');
            $table->json('field_schema_snapshot')->nullable()->after('property_data');

            $table->index(['tenant_id', 'transaction_template_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'transaction_template_id']);
            $table->dropConstrainedForeignId('transaction_template_id');
            $table->dropColumn([
                'transaction_template_version',
                'field_schema_snapshot',
            ]);
        });
    }
};
