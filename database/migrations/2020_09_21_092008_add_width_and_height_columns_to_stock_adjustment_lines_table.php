<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWidthAndHeightColumnsToStockAdjustmentLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stock_adjustment_lines', function (Blueprint $table) {
            $table->float('width')->nullable();
            $table->float('height')->nullable();
            $table->integer('quantity_line')->nullable();
            $table->integer('warehouse_id')->nullable();
            $table->integer('plate_stock_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stock_adjustment_lines', function (Blueprint $table) {
            $table->dropColumn('width');
            $table->dropColumn('height');
            $table->dropColumn('quantity_line');
            $table->dropColumn('warehouse_id');
            $table->dropColumn('plate_stock_id');
        });
    }
}
