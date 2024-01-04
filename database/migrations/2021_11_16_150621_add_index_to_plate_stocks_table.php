<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexToPlateStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('plate_stocks', function (Blueprint $table) {
            $table->index('location_id');
            $table->index('product_id');
            $table->index('variation_id');
            $table->index('warehouse_id');
            $table->index('is_origin');
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
            $table->dropIndex(['location_id']);
            $table->dropIndex(['product_id']);
            $table->dropIndex(['variation_id']);
            $table->dropIndex(['warehouse_id']);
            $table->dropIndex(['is_origin']);
        });
    }
}
