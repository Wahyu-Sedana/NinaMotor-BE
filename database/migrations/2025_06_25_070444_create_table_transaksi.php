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
        Schema::create('tb_transaksi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->dateTime('tanggal_transaksi')->useCurrent();
            $table->decimal('total', 12, 2);
            $table->string('metode_pembayaran')->nullable();
            $table->enum('status_pembayaran', ['pending', 'berhasil', 'gagal', 'expired', 'dibatalkan'])->default('pending');
            $table->enum('status_transaksi', ['pending', 'selesai', 'dibatalkan'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_transaksi');
    }
};
