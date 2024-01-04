<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyWidthHeightAttributeToTransactionSellLinesAndPurchaseLines extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->dropColumn('width');
            $table->dropColumn('height');
        });

        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->decimal('width', 10, 4);
            $table->decimal('height', 10, 4);
        });

        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->dropColumn('width');
            $table->dropColumn('height');
        });

        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->decimal('width', 10, 4);
            $table->decimal('height', 10, 4);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
