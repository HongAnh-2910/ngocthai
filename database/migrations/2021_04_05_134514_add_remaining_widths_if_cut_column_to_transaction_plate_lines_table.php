<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRemainingWidthsIfCutColumnToTransactionPlateLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_plate_lines', function (Blueprint $table) {
            $table->text('remaining_widths')->nullable();
            $table->text('remaining_widths_if_cut')->nullable();
            $table->text('remaining_widths_if_not_cut')->nullable();
            $table->text('plates_if_not_cut')->nullable();
            $table->boolean('enabled_not_cut')->nullable();
            $table->string('row_id')->nullable();
            $table->integer('row_index')->nullable();
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
            $table->dropColumn('remaining_widths');
            $table->dropColumn('remaining_widths_if_cut');
            $table->dropColumn('remaining_widths_if_not_cut');
            $table->dropColumn('plates_if_not_cut');
            $table->dropColumn('enabled_not_cut');
            $table->dropColumn('row_id');
            $table->dropColumn('row_index');
        });
    }
}
