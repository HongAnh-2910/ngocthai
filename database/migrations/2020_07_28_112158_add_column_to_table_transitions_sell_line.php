<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToTableTransitionsSellLine extends Migration
{

    public function up()
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->tinyInteger('flag_changed')->default(0);
        });
    }

    public function down()
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->dropColumn('flag_changed');
        });
    }
}
