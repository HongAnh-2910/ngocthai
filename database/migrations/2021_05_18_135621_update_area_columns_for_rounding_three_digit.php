<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAreaColumnsForRoundingThreeDigit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // plate_stock_drafts
        DB::statement("ALTER TABLE plate_stock_drafts MODIFY COLUMN width DECIMAL(10, 3) NOT NULL");
        DB::statement("ALTER TABLE plate_stock_drafts MODIFY COLUMN height DECIMAL(10, 3) NOT NULL");

        // plate_stocks
        DB::statement("ALTER TABLE plate_stocks MODIFY COLUMN width DECIMAL(10, 3) NOT NULL");
        DB::statement("ALTER TABLE plate_stocks MODIFY COLUMN height DECIMAL(10, 3) NOT NULL");

        // purchase_lines
        DB::statement("ALTER TABLE purchase_lines MODIFY COLUMN width DECIMAL(10, 3) NOT NULL");
        DB::statement("ALTER TABLE purchase_lines MODIFY COLUMN height DECIMAL(10, 3) NOT NULL");
        DB::statement("ALTER TABLE purchase_lines MODIFY COLUMN quantity DECIMAL(10, 3) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE purchase_lines MODIFY COLUMN quantity_sold DECIMAL(10, 3) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE purchase_lines MODIFY COLUMN quantity_adjusted DECIMAL(10, 3) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE purchase_lines MODIFY COLUMN quantity_returned DECIMAL(10, 3) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE purchase_lines MODIFY COLUMN mfg_quantity_used DECIMAL(10, 3) NOT NULL DEFAULT 0");

        // remaining_plate_lines
        DB::statement("ALTER TABLE remaining_plate_lines MODIFY COLUMN width DECIMAL(10, 3) NOT NULL");
        DB::statement("ALTER TABLE remaining_plate_lines MODIFY COLUMN height DECIMAL(10, 3) NOT NULL");
        DB::statement("ALTER TABLE remaining_plate_lines MODIFY COLUMN total_quantity DECIMAL(10, 3) NOT NULL DEFAULT 0");

        // stock_adjustment_lines
        DB::statement("ALTER TABLE stock_adjustment_lines MODIFY COLUMN width DECIMAL(10, 3) NOT NULL");
        DB::statement("ALTER TABLE stock_adjustment_lines MODIFY COLUMN height DECIMAL(10, 3) NOT NULL");
        DB::statement("ALTER TABLE stock_adjustment_lines MODIFY COLUMN quantity DECIMAL(10, 3) NOT NULL DEFAULT 0");

        // transaction_plate_lines
        DB::statement("ALTER TABLE transaction_plate_lines MODIFY COLUMN width DECIMAL(10, 3) NOT NULL");
        DB::statement("ALTER TABLE transaction_plate_lines MODIFY COLUMN height DECIMAL(10, 3) NOT NULL");
        DB::statement("ALTER TABLE transaction_plate_lines MODIFY COLUMN total_quantity DECIMAL(10, 3) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE transaction_plate_lines MODIFY COLUMN selected_width DECIMAL(10, 3) NOT NULL");
        DB::statement("ALTER TABLE transaction_plate_lines MODIFY COLUMN selected_height DECIMAL(10, 3) NOT NULL");

        // transaction_plate_lines_return
        DB::statement("ALTER TABLE transaction_plate_lines_return MODIFY COLUMN width DECIMAL(10, 3) NOT NULL");
        DB::statement("ALTER TABLE transaction_plate_lines_return MODIFY COLUMN height DECIMAL(10, 3) NOT NULL");

        // transaction_sell_lines
        DB::statement("ALTER TABLE transaction_sell_lines MODIFY COLUMN width DECIMAL(10, 3) NOT NULL");
        DB::statement("ALTER TABLE transaction_sell_lines MODIFY COLUMN height DECIMAL(10, 3) NOT NULL");
        DB::statement("ALTER TABLE transaction_sell_lines MODIFY COLUMN quantity DECIMAL(10, 3) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE transaction_sell_lines MODIFY COLUMN quantity_returned DECIMAL(10, 3) NOT NULL DEFAULT 0");

        DB::statement("ALTER TABLE transaction_sell_lines MODIFY COLUMN quantity_line INT NOT NULL");

        // transaction_sell_lines_change
        DB::statement("ALTER TABLE transaction_sell_lines_change MODIFY COLUMN width DECIMAL(10, 3) NOT NULL");
        DB::statement("ALTER TABLE transaction_sell_lines_change MODIFY COLUMN height DECIMAL(10, 3) NOT NULL");
        DB::statement("ALTER TABLE transaction_sell_lines_change MODIFY COLUMN quantity DECIMAL(10, 3) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE transaction_sell_lines_change MODIFY COLUMN quantity_returned DECIMAL(10, 3) NOT NULL DEFAULT 0");

        // units
        DB::statement("ALTER TABLE units MODIFY COLUMN width DECIMAL(10, 3)");
        DB::statement("ALTER TABLE units MODIFY COLUMN height DECIMAL(10, 3)");
        DB::statement("ALTER TABLE units MODIFY COLUMN base_unit_multiplier DECIMAL(10, 3)");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
