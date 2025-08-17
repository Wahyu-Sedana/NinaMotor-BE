<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tb_servismotor', function (Blueprint $table) {
            $table->decimal('harga_servis', 15, 2)->nullable()->after('catatan_admin');
            $table->string('transaksi_id')->nullable()->after('harga_servis');
            $table->foreign('transaksi_id')->references('id')->on('tb_transaksi')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('tb_servismotor', function (Blueprint $table) {
            $table->dropForeign(['transaksi_id']);
            $table->dropColumn(['harga_servis', 'transaksi_id']);
        });
    }
};
