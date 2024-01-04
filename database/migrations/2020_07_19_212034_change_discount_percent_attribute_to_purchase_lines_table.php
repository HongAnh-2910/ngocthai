<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeDiscountPercentAttributeToPurchaseLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->dropColumn('discount_percent');
//            $table->decimal('discount_percent', 22, 0)->after('pp_without_discount')->default(0)->comment('Inline discount amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
//            $table->dropColumn('discount_percent');
            $table->decimal('discount_percent', 5, 2)->after('pp_without_discount')->default(0)->comment('Inline discount percentage');
        });
    }
}
