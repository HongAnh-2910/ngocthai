<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnQuantityTotalToTransactionPlateLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_plate_lines', function (Blueprint $table) {
            $table->float('total_quantity')->after('height')->default(0);
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
            $table->dropColumn('total_quantity');
        });
    }
}
