<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_volumes', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('volume_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_owned')->default(true);
            $table->timestamps();

            $table->unique(['collection_id', 'volume_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_volumes');
    }
};
