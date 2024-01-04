<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditTypeColumnToTransactionExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_expenses', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropColumn('ref_transaction_id');
        });

        Schema::table('transaction_expenses', function (Blueprint $table) {
            $table->string('type')->after('id')->comment('return_customer: trả lại cho khách, expense: chi phí');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_expenses', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('transaction_expenses', function (Blueprint $table) {
            $table->tinyInteger('type')->comment('1: Thu, 2: Chi');
            $table->integer('ref_transaction_id')->index();
        });
    }
}
