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
        Schema::create('transaction_field_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('field_definition_id')->constrained('transaction_field_definitions')->cascadeOnDelete();
            $table->string('scope_type');
            $table->unsignedBigInteger('scope_id');
            $table->string('label')->nullable();
            $table->string('unit')->nullable();
            $table->string('format')->nullable();
            $table->json('option_labels')->nullable();
            $table->boolean('is_required')->nullable();
            $table->boolean('is_visible')->nullable();
            $table->unsignedInteger('sort_order')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'scope_type', 'scope_id', 'field_definition_id'], 'tfo_scope_definition_unique');
            $table->index(['tenant_id', 'scope_type', 'scope_id'], 'tfo_scope_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_field_overrides');
    }
};
