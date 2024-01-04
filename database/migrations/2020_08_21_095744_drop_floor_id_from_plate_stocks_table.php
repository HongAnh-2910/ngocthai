<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropFloorIdFromPlateStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('plate_stocks', function (Blueprint $table) {
            $table->dropColumn('floor_id');
            $table->dropColumn('business_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('plate_stocks', function (Blueprint $table) {
            //
        });
    }
}
