<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

class AddTargetPermisions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::create(['name' => 'target.view']);
        Permission::create(['name' => 'target.create']);
        Permission::create(['name' => 'target.update']);
        Permission::create(['name' => 'target.delete']);
        Permission::create(['name' => 'target.report-owner-target']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::where('name', 'target.view')->delete();
        Permission::where('name', 'target.create')->delete();
        Permission::where('name', 'target.update')->delete();
        Permission::where('name', 'target.delete')->delete();
        Permission::where('name', 'target.report-owner-target')->delete();
    }
}
