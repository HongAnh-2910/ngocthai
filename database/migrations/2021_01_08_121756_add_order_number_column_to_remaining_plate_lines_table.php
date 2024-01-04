<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\RemainingPlateLine;
use App\TransactionPlateLine;

class AddOrderNumberColumnToRemainingPlateLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('remaining_plate_lines', function (Blueprint $table) {
            $table->string('row_id', 30)->nullable();
            $table->string('row_next_id', 30)->nullable();
            $table->string('row_prev_id', 30)->nullable();
            $table->integer('order_number')->nullable()->default(1);
            $table->integer('next_id')->nullable();
            $table->integer('prev_id')->nullable();
        });

        RemainingPlateLine::truncate();

        $plate_lines = TransactionPlateLine::with('selected_plate_stock')
            ->whereNotNull('remaining_plates')
            ->where('remaining_plates', '<>', '[]')
            ->get();

        $sort_order_remaining_plates = [];
        $remaining_plate_lines = [];

        foreach ($plate_lines as $plate_line){
            $old_plate_stock = $plate_line->selected_plate_stock;
            $remaining_plates = json_decode($plate_line->remaining_plates, true);

            foreach ($remaining_plates as $remaining_plate){
                $remaining_plate_line = RemainingPlateLine::create([
                    'transaction_id' => $plate_line->transaction_id,
                    'transaction_sell_line_id' => $plate_line->transaction_sell_line_id,
                    'transaction_plate_line_id' => $plate_line->id,
                    'plate_stock_id' => null,
                    'width' => $remaining_plate['width'],
                    'height' => $old_plate_stock->height,
                    'quantity' => $remaining_plate['quantity'],
                    'total_quantity' => $remaining_plate['width'] * $old_plate_stock->height * $remaining_plate['quantity'],
                    'product_id' => $old_plate_stock->product_id,
                    'variation_id' => $old_plate_stock->variation_id,
                    'warehouse_id' => $old_plate_stock->warehouse_id,
                    'order_number' => $remaining_plate['order_number'],
                    'row_id' => $remaining_plate['next_id'],
                    'row_next_id' => $remaining_plate['next_id'],
                    'row_prev_id' => $remaining_plate['prev_id'],
                ]);

                $remaining_plate_lines[] = $remaining_plate_line;
                $sort_order_remaining_plates[$remaining_plate['id']] = $remaining_plate_line->id;
            }
        }

        foreach ($remaining_plate_lines as $remaining_plate_line){
            $is_update = false;

            if (!empty($remaining_plate_line->row_next_id) && isset($sort_order_remaining_plates[$remaining_plate_line->row_next_id])){
                $remaining_plate_line->next_id = $sort_order_remaining_plates[$remaining_plate_line->row_next_id];
                $is_update = true;
            }

            if (!empty($remaining_plate_line->row_prev_id) && isset($sort_order_remaining_plates[$remaining_plate_line->row_prev_id])){
                $remaining_plate_line->prev_id = $sort_order_remaining_plates[$remaining_plate_line->row_prev_id];
                $is_update = true;
            }

            if ($is_update){
                $remaining_plate_line->save();
            }

            $test_results['remaining_plates'][] = $remaining_plate_line;
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('remaining_plate_lines', function (Blueprint $table) {
            $table->dropColumn('row_id');
            $table->dropColumn('row_next_id');
            $table->dropColumn('row_prev_id');
            $table->dropColumn('order_number');
            $table->dropColumn('next_id');
            $table->dropColumn('prev_id');
        });
    }
}
