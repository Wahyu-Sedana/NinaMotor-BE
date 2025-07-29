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
        Schema::create('tb_bookmark', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->string('sparepart_id');
            $table->timestamps();
            $table->unique(['user_id', 'sparepart_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_bookmark');
    }
};
