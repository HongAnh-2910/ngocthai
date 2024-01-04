<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddApprovePermissionColumnsToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->boolean('is_approved_by_cashier')->default(false);
            $table->boolean('is_approved_by_storekeeper')->default(false);
            $table->boolean('is_approved_by_admin')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('is_approved_by_cashier');
            $table->dropColumn('is_approved_by_storekeeper');
            $table->dropColumn('is_approved_by_admin');
        });
    }
}
