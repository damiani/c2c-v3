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
        Schema::create('transaction_template_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('transaction_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('field_definition_id')->constrained('transaction_field_definitions')->cascadeOnDelete();
            $table->string('field_key');
            $table->string('section')->default('general');
            $table->string('label')->nullable();
            $table->string('unit')->nullable();
            $table->string('format')->nullable();
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('visibility_rules')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('calculation_rules')->nullable();
            $table->json('date_trigger_rules')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['transaction_template_id', 'field_definition_id'], 'ttf_template_definition_unique');
            $table->unique(['transaction_template_id', 'field_key'], 'ttf_template_key_unique');
            $table->index(['tenant_id', 'section'], 'ttf_tenant_section_index');
            $table->index(['sort_order'], 'ttf_sort_order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_template_fields');
    }
};
