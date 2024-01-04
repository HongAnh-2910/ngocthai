<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCashierApprovedColumnToTransactionCodTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_cod', function (Blueprint $table) {
            $table->boolean('bank_transfer_approved')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_cod', function (Blueprint $table) {
            $table->dropColumn('bank_transfer_approved');
        });
    }
}
