<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexForRemainingPlateLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('remaining_plate_lines', function (Blueprint $table) {
            $table->index('transaction_id');
            $table->index('variation_id');
            $table->index('warehouse_id');
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
            $table->dropIndex('transaction_id');
            $table->dropIndex('variation_id');
            $table->dropIndex('warehouse_id');
        });
    }
}
