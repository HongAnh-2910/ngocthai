<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReturnTypeToTransactionPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE transaction_payments CHANGE `type` `type` enum('normal','deposit','cod', 'return') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal';");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE transaction_payments CHANGE `type` `type` enum('normal','deposit','cod') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal';");
    }
}
