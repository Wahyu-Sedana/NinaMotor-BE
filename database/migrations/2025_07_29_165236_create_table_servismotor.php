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
        Schema::create('tb_servismotor', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->string('no_kendaraan');
            $table->enum('jenis_motor', [
                'matic',
                'manual'
            ]);
            $table->text('keluhan');
            $table->enum('status', [
                'pending',
                'rejected',
                'in_service',
                'done'
            ])->default('pending');
            $table->text('catatan_admin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_servismotor');
    }
};
