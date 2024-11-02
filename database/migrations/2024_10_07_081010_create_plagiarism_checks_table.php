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
        Schema::create('plagiarism_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_document_id')->constrained('user_documents')->onDelete('cascade');
            $table->foreignId('reference_document_id')->constrained('reference_documents')->onDelete('cascade');
            $table->decimal('similarity_percentage', 5, 2); // Simpan persentase kemiripan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plagiarism_checks');
    }
};
