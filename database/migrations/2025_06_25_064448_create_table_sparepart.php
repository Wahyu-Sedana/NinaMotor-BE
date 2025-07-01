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
        Schema::create('tb_sparepart', function (Blueprint $table) {
            $table->string('kode_sparepart')->primary();
            $table->unsignedBigInteger('kategori_id');
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->integer('stok')->default(0);
            $table->decimal('harga_beli', 12, 2);
            $table->decimal('harga_jual', 12, 2);
            $table->string('merk')->nullable();
            $table->text('gambar_produk')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_sparepart');
    }
};
