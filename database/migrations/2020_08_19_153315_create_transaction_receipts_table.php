<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_receipts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type')->comment('recovery_dues: Thu nợ khách, receipt: Phiếu thu');
            $table->integer('transaction_id')->index();
            $table->integer('contact_id')->nullable()->index();
            $table->float('total_money', 22, 4)->default(0);
            $table->string('note')->nullable();
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
        Schema::dropIfExists('transaction_receipts');
    }
}
