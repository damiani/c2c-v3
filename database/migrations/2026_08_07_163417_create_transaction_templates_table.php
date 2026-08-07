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
        Schema::create('transaction_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('scope_type')->default('system');
            $table->unsignedBigInteger('scope_id')->default(0);
            $table->string('template_key');
            $table->string('name');
            $table->string('transaction_type')->index();
            $table->text('description')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('status')->default('active')->index();
            $table->boolean('is_default')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['scope_type', 'scope_id', 'template_key', 'version'], 'tt_scope_key_version_unique');
            $table->index(['tenant_id', 'transaction_type'], 'tt_tenant_type_index');
            $table->index(['scope_type', 'scope_id'], 'tt_scope_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_templates');
    }
};
