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
        Schema::create('leases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->string('lease_type')->index();
            $table->string('status')->default('draft')->index();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable()->index();
            $table->decimal('rent_amount', 12, 2)->nullable();
            $table->string('rent_currency', 3)->default('USD');
            $table->json('escalation_schedule')->nullable();
            $table->unsignedTinyInteger('renewal_lead_months')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('transaction_id');
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};
