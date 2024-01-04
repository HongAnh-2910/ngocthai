<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

class AddPermisstionsForStock extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::create(['name' => 'stock.create_stock_transfer_bill']);
        Permission::create(['name' => 'stock.create_stock_adjustment_bill']);
        Permission::create(['name' => 'sell.create_exchange_bill']);
        Permission::create(['name' => 'sell.create_return_bill']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::where('name', 'stock.create_stock_transfer_bill')->delete();
        Permission::where('name', 'stock.create_stock_adjustment_bill')->delete();
        Permission::where('name', 'sell.create_exchange_bill')->delete();
        Permission::where('name', 'sell.create_return_bill')->delete();
    }
}
