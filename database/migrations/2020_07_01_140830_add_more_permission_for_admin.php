<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

class AddMorePermissionForAdmin extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::create(['name' => 'sell.approve_transfer_money_bill']);
        Permission::create(['name' => 'sell.approve_return_bill']);
        Permission::create(['name' => 'sell.approve_exchange_bill']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::where('name', 'sell.approve_transfer_money_bill')->delete();
        Permission::where('name', 'sell.approve_return_bill')->delete();
        Permission::where('name', 'sell.approve_exchange_bill')->delete();
    }
}
