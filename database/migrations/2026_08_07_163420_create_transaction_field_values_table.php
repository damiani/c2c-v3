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
        Schema::create('transaction_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('field_definition_id')->constrained('transaction_field_definitions')->restrictOnDelete();
            $table->foreignId('template_field_id')->nullable()->constrained('transaction_template_fields')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('field_key');
            $table->string('data_type');
            $table->longText('value_text')->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->bigInteger('value_integer')->nullable();
            $table->decimal('value_decimal', 18, 6)->nullable();
            $table->decimal('value_money_amount', 15, 2)->nullable();
            $table->char('value_currency', 3)->nullable();
            $table->date('value_date')->nullable();
            $table->timestamp('value_datetime')->nullable();
            $table->json('value_json')->nullable();
            $table->string('value_unit')->nullable();
            $table->string('selected_option_key')->nullable();
            $table->string('source_type')->default('user');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['transaction_id', 'field_definition_id'], 'tfv_transaction_definition_unique');
            $table->index(['tenant_id', 'field_key'], 'tfv_tenant_key_index');
            $table->index(['tenant_id', 'data_type'], 'tfv_tenant_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_field_values');
    }
};
