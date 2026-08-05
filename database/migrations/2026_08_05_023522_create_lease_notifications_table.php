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
        Schema::create('lease_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lease_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('lead_time_months');
            $table->string('alert_type')->index();
            $table->timestamp('alert_at')->index();
            $table->timestamp('fired_at')->nullable();
            $table->string('status')->default('scheduled')->index();
            $table->string('agent_action')->nullable();
            $table->string('escalation_status')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lease_notifications');
    }
};
