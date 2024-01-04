<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWarehouseIdColumnToRemainingPlateLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('remaining_plate_lines', function (Blueprint $table) {
            $table->integer('warehouse_id');
        });

        $remaining_plate_lines = \App\RemainingPlateLine::with('plate_stock')
            ->where('warehouse_id', 0)
            ->whereNotNull('plate_stock_id')
            ->get();
        foreach ($remaining_plate_lines as $remaining_plate_line){
            $remaining_plate_line->warehouse_id = $remaining_plate_line->plate_stock->warehouse_id;
            $remaining_plate_line->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('remaining_plate_lines', function (Blueprint $table) {
            $table->dropColumn('warehouse_id');
        });
    }
}
