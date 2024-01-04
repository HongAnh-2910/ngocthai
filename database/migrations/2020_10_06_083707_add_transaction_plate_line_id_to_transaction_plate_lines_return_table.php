<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTransactionPlateLineIdToTransactionPlateLinesReturnTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_plate_lines_return', function (Blueprint $table) {
            $table->integer('transaction_plate_line_id')->after('plate_stock_id');
            $table->integer('variation_id');
            $table->float('width');
            $table->float('height');
            $table->integer('quantity');
            $table->string('sell_price_type');
            $table->float('unit_price', 22, 4);
            $table->integer('warehouse_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_plate_lines_return', function (Blueprint $table) {
            $table->dropColumn('transaction_plate_line_id');
            $table->dropColumn('variation_id');
            $table->dropColumn('width');
            $table->dropColumn('height');
            $table->dropColumn('quantity');
            $table->dropColumn('sell_price_type');
            $table->dropColumn('unit_price');
            $table->dropColumn('warehouse_id');
        });
    }
}
