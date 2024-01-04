<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTargetCategoryLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('target_category_lines', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('target_id')->unsigned();
            $table->foreign('target_id')->references('id')->on('targets')->onDelete('cascade');
            $table->integer('category_id')->unsigned();
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->integer('sub_category_id')->unsigned()->nullable();
            $table->foreign('sub_category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->integer('quantity');
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
        Schema::dropIfExists('target_category_lines');
    }
}
