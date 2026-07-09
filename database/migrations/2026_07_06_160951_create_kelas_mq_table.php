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
        Schema::create('kelas_mq', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_id')->constrained('periode_akademik')->restrictOnDelete();
            $table->foreignId('tutor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('nama_kelas', 100);
            $table->string('jadwal', 100)->nullable();
            $table->enum('tingkat', ['Menegah', 'Mahir']);
            $table->unsignedInteger('kapasitas_jumlah');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelas_mq');
    }
};
