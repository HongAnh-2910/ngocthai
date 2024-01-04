<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use App\BusinessLocation;

class CreateWarehousePermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $locations = BusinessLocation::with('warehouses')
            ->get();
        foreach ($locations as $location){
            $is_exist = Permission::where('name', 'location.'. $location->id .'.access_all_warehouses')->first();
            if (!$is_exist){
                Permission::create(['name' => 'location.'. $location->id .'.access_all_warehouses']);
            }

            foreach ($location->warehouses as $warehouse){
                $is_exist = Permission::where('name', 'warehouse.'. $warehouse->id)->first();
                if (!$is_exist){
                    Permission::create(['name' => 'warehouse.'. $warehouse->id]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
