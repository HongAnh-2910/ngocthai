<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\PurchaseLine;

class AddIsOriginColumnToPurchaseLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->boolean('is_origin')->default(false);
        });

        $purchase_lines = PurchaseLine::with('product.sub_unit')->get();
        foreach ($purchase_lines as $purchase_line){
            if($purchase_line->product->sub_unit && $purchase_line->width == $purchase_line->product->sub_unit->width){
                $purchase_line->is_origin = 1;
                $purchase_line->save();
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
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->dropColumn('is_origin');
        });
    }
}
