<?php

// database/migrations/xxxx_xx_xx_create_categories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Relasi ke user
            $table->string('nama'); // Nama kategori
            $table->string('color')->nullable(); // Warna kategori (opsional)
            $table->timestamps(); // Tanggal pembuatan dan update kategori
        });
    }

    public function down(): void {
        Schema::dropIfExists('categories');
    }
};

