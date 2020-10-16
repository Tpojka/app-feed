<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('feeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reader_result_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('url')->nullable();
            $table->string('language')->nullable();
            $table->string('logo')->nullable();
            $table->string('title')->nullable();
            $table->string('public_id')->nullable();
            $table->text('description')->nullable();
            $table->dateTimeTz('last_modified')->nullable();
            $table->string('link')->nullable();
            $table->string('host')->nullable();
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
        Schema::dropIfExists('feeds');
    }
}
