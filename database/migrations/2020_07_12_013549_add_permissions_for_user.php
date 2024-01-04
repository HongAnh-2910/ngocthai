<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

class AddPermissionsForUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::create(['name' => 'approve_bill_to_deliver']);
        Permission::create(['name' => 'stock.to_deliver']);
        Permission::create(['name' => 'stock.to_receive']);
        Permission::create(['name' => 'report.revenue_date']);
        Permission::create(['name' => 'report.reporting_date']);
        Permission::create(['name' => 'report.transfer']);
        Permission::create(['name' => 'report.input_output_inventory']);
        Permission::create(['name' => 'sell.accept_received_money_to_custom']);
        Permission::create(['name' => 'sell.accept_received_money_to_cashier']);
        Permission::create(['name' => 'sell.create_bill_transfer']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::where('name', 'approve_bill_to_deliver')->delete();
        Permission::where('name', 'stock.to_deliver')->delete();
        Permission::where('name', 'stock.to_receive')->delete();
        Permission::where('name', 'report.revenue_date')->delete();
        Permission::where('name', 'report.reporting_date')->delete();
        Permission::where('name', 'report.transfer')->delete();
        Permission::where('name', 'report.input_output_inventory')->delete();
        Permission::where('name', 'sell.accept_received_money_to_custom')->delete();
        Permission::where('name', 'sell.accept_received_money_to_cashier')->delete();
        Permission::where('name', 'sell.create_bill_transfer')->delete();
    }
}
