<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

class AddMorePermissionToPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::create(['name' => 'report.revenue_by_month']);
        Permission::create(['name' => 'adjustment.delete']);
        Permission::create(['name' => 'transfer.delete']);
        Permission::create(['name' => 'return.list']);
        Permission::create(['name' => 'return.update']);
        Permission::create(['name' => 'return.cancel']);
        Permission::create(['name' => 'shipping.create']);
        Permission::create(['name' => 'shipping.update']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::where('name', 'report.revenue_by_month')->delete();
        Permission::where('name', 'adjustment.delete')->delete();
        Permission::where('name', 'transfer.delete')->delete();
        Permission::where('name', 'return.list')->delete();
        Permission::where('name', 'return.update')->delete();
        Permission::where('name', 'return.cancel')->delete();
        Permission::where('name', 'shipping.create')->delete();
        Permission::where('name', 'shipping.update')->delete();
    }
}
