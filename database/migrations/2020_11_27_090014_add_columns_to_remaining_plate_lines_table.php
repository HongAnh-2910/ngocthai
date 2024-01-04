<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsToRemainingPlateLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('remaining_plate_lines', function (Blueprint $table) {
            $table->integer('transaction_id')->nullable();
            $table->integer('transaction_sell_line_id')->nullable();
            $table->float('width')->nullable();
            $table->float('height')->nullable();
            $table->integer('quantity')->nullable();
            $table->float('total_quantity')->nullable();
            $table->integer('product_id')->nullable();
            $table->integer('variation_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('remaining_plate_lines', function (Blueprint $table) {
            $table->dropColumn('transaction_id');
            $table->dropColumn('transaction_sell_line_id');
            $table->dropColumn('width');
            $table->dropColumn('height');
            $table->dropColumn('quantity');
            $table->dropColumn('total_quantity');
            $table->dropColumn('product_id');
            $table->dropColumn('variation_id');
        });
    }
}
