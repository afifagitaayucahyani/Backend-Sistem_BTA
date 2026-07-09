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
        Schema::create('absensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas_mq')->restrictOnDelete();
            $table->foreignId('mahasiswa_id')-> nullable()->constrained('mahasiswa')->restrictOnDelete();
            $table->foreignId('tutor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('tanggal_absensi');
            $table->enum('status_absensi', ['Hadir', 'Izin', 'Sakit', 'Alpa']);
            $table->boolean('is_valid')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};
