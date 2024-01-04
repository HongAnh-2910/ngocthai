<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPlateReturnColumnsToTransactionSellLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->integer('plate_quantity_returned')->default(0);
            $table->float('plate_width_returned')->nullable();
            $table->float('plate_height_returned')->nullable();
            $table->float('warehouse_id_returned')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->dropColumn('plate_quantity_returned');
            $table->dropColumn('plate_width_returned');
            $table->dropColumn('plate_height_returned');
            $table->dropColumn('warehouse_id_returned');
        });
    }
}
