<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnFlagDeliverTransaction extends Migration{
    public function up(){
        Schema::table('transactions', function (Blueprint $table) {
            $table->tinyInteger('flag_deliver')->default(0);
        });
    }
}
