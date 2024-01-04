<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMaxWeightDontNeedConfirmColumnToBusinessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business', function (Blueprint $table) {
            $table->decimal('max_weight_dont_need_confirm', 22, 2)->default(0);
            $table->integer('max_pcs_dont_need_confirm')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business', function (Blueprint $table) {
            $table->dropColumn('max_weight_dont_need_confirm');
            $table->dropColumn('max_pcs_dont_need_confirm');
        });
    }
}
