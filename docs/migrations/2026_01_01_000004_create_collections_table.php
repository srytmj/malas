<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('volume_id')->constrained()->cascadeOnDelete();
            $table->enum('condition', ['mint', 'good', 'fair', 'poor'])->default('good');
            $table->string('location')->nullable()->comment('Lokasi rak, misal: Rak A Baris 2');
            $table->text('notes')->nullable();
            $table->date('acquired_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Satu user hanya bisa punya satu record per volume
            $table->unique(['user_id', 'volume_id']);
            $table->index('user_id');
            $table->index('volume_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
