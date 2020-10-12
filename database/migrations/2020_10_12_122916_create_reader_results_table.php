<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReaderResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reader_results', function (Blueprint $table) {
            $table->id();
            $table->dateTimeTz('modified_since');
            $table->dateTimeTz('date');
            $table->text('document_content')->nullable()->default(null)->comment('Should be considered is this field needed');
            $table->string('url');
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
        Schema::dropIfExists('reader_results');
    }
}
