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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');  // Relasi ke tabel users
            $table->foreignId('category_id')->constrained()->onDelete('cascade');  // Relasi ke kategori
            $table->string('title');  // Judul tugas
            $table->text('description')->nullable();  // Deskripsi tugas
            $table->foreignId('task_status_id')->nullable()->constrained('task_statuses')->onDelete('set null');
            $table->dateTime('due_date')->nullable();  // Tanggal jatuh tempo
            $table->timestamps();  // Kolom untuk created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
