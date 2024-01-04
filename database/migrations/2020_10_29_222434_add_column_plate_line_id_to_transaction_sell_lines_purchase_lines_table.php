<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnPlateLineIdToTransactionSellLinesPurchaseLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_sell_lines_purchase_lines', function (Blueprint $table) {
            $table->bigInteger('plate_line_id')->index()->default(0)->after('purchase_line_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_sell_lines_purchase_lines', function (Blueprint $table) {
            $table->dropColumn('plate_line_id');
        });
    }
}
