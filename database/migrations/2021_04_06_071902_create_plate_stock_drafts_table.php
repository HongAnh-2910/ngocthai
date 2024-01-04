<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlateStockDraftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plate_stock_drafts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('location_id');
            $table->integer('product_id');
            $table->integer('variation_id');
            $table->decimal('width', 10, 4);
            $table->decimal('height', 10, 4);
            $table->integer('warehouse_id');
            $table->boolean('is_origin');
            $table->integer('qty_available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plate_stock_drafts');
    }
}
