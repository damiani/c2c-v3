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
        Schema::create('transaction_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('scope_type')->default('system');
            $table->unsignedBigInteger('scope_id')->default(0);
            $table->string('field_key');
            $table->string('label');
            $table->string('data_type');
            $table->string('default_unit')->nullable();
            $table->string('default_format')->nullable();
            $table->json('default_options')->nullable();
            $table->json('value_schema')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['scope_type', 'scope_id', 'field_key'], 'tfd_scope_key_unique');
            $table->index(['tenant_id', 'field_key'], 'tfd_tenant_key_index');
            $table->index(['scope_type', 'scope_id'], 'tfd_scope_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_field_definitions');
    }
};
