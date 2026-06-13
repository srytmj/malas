<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_collections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_library_id')->constrained('user_libraries')->cascadeOnDelete();
            $table->foreignUuid('volume_id')->constrained()->cascadeOnDelete();
            $table->enum('condition', ['mint','very_good','good','fair','poor'])->default('good');
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_for_loan')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('deleted_by')->nullable();
            $table->text('deletion_reason')->nullable();
            $table->unique(['user_library_id', 'volume_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('user_collections'); }
};
