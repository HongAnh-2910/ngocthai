<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeQuantityTypeColumnToTarget extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('target_sale_lines', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
        Schema::table('target_sale_lines', function (Blueprint $table) {
            $table->float('quantity')->after('product_id');
        });

        Schema::table('target_category_lines', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });

        Schema::table('target_category_lines', function (Blueprint $table) {
            $table->float('quantity')->after('sub_category_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('target_sale_lines', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });

        Schema::table('target_sale_lines', function (Blueprint $table) {
            $table->integer('quantity')->default(0);
        });

        Schema::table('target_category_lines', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });

        Schema::table('target_category_lines', function (Blueprint $table) {
            $table->integer('quantity');
        });
    }
}
