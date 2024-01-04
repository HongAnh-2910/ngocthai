<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\PlateStock;

class AddIsOriginColumnToPlateStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('plate_stocks', function (Blueprint $table) {
            $table->boolean('is_origin')->default(false);
        });

        $plate_stocks = PlateStock::with('product.sub_unit')->get();
        foreach ($plate_stocks as $plate_stock){
            if($plate_stock->product->sub_unit && $plate_stock->width == $plate_stock->product->sub_unit->width){
                $plate_stock->is_origin = 1;
                $plate_stock->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('plate_stocks', function (Blueprint $table) {
            $table->dropColumn('is_origin');
        });
    }
}
