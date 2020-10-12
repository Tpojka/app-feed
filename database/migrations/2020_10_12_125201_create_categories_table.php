<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('term');
            $table->string('scheme')->nullable();
            $table->string('label')->nullable();
            $table->timestamps();
        });

        Schema::create('categoryables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained();
            $table->unsignedBigInteger('categoryable_id');
            $table->string('categoryable_type');
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
        Schema::dropIfExists('categoryables');
        Schema::dropIfExists('categories');
    }
}
