<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropRemainingPlateStockIdColumnFromTransactionPlateLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_plate_lines', function (Blueprint $table) {
            $table->dropColumn('remaining_plate_stock_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_plate_lines', function (Blueprint $table) {
            $table->integer('remaining_plate_stock_id')->nullable();
        });
    }
}
