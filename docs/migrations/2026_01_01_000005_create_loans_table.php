<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('borrower_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('borrower_name')->comment('Wajib diisi, untuk peminjam non-user sekalipun');
            $table->string('borrower_contact')->nullable();
            $table->enum('status', ['pending', 'active', 'returned', 'overdue', 'lost', 'cancelled'])
                ->default('pending');
            $table->timestamp('loaned_at')->nullable()->comment('Diisi saat status → active');
            $table->date('due_date');
            $table->timestamp('returned_at')->nullable()->comment('Diisi saat status → returned');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->text('deleted_reason')->nullable();
            $table->timestamps();

            $table->index('collection_id');
            $table->index('status');
            $table->index('due_date');
            $table->index('borrower_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
