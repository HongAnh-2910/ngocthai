<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteRemainingPlateLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('remaining_plate_lines');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('remaining_plate_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('transaction_plate_line_id');
            $table->integer('plate_stock_id');
            $table->timestamps();
        });
    }
}
