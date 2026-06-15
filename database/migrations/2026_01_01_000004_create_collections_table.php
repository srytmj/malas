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
            $table->foreignUuid('series_id')->constrained()->cascadeOnDelete();
            // edition_id FK added after editions table in a later migration
            $table->uuid('edition_id')->nullable()->index();
            $table->enum('condition', ['mint', 'good', 'fair', 'poor'])->default('good');
            $table->decimal('price_per_volume', 10, 2)->nullable()
                  ->comment('Override harga per volume untuk edisi ini');
            $table->string('location')->nullable()->comment('Lokasi rak, contoh: Rak A Baris 2');
            $table->date('acquired_at')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->text('deleted_reason')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();

            $table->index('series_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
