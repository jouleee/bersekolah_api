<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediaSosialTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media_sosial', function (Blueprint $table) {
            $table->id();
            $table->text('twibbon_link')->nullable();
            $table->text('instagram_link')->nullable();
            $table->text('link_grup_beasiswa')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('judul_essay')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('media_sosial');
    }
}
