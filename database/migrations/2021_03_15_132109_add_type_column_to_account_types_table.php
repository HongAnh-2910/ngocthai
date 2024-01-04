<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\AccountType;
use App\Business;

class AddTypeColumnToAccountTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_types', function (Blueprint $table) {
            $table->enum('type', ['cash', 'bank'])->default('bank');
        });

//        $business = Business::orderBy('id', 'asc')->first();

        AccountType::create([
            'name' => 'Tiền mặt',
<<<<<<< Updated upstream
            'business_id' => 1,
=======
            'business_id' => $business->id ?? '',
>>>>>>> Stashed changes
            'type' => 'cash'
        ]);

        AccountType::create([
            'name' => 'Tài khoản ngân hàng',
<<<<<<< Updated upstream
            'business_id' => 1,
=======
            'business_id' => $business->id ?? '',
>>>>>>> Stashed changes
            'type' => 'bank'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('account_types', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
