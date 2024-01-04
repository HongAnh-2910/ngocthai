<?php

namespace App\Console\Commands;

use App\Transaction;
use App\TransactionShip;
use Illuminate\Console\Command;

class SyncDataShipper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:shipper';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $transactions = Transaction::query()
            ->whereNotNull('res_waiter_id')
            ->select(['transactions.id', 'transactions.res_waiter_id'])
            ->get();

        $dataInsert = [];

        foreach ($transactions as $transaction){
            $dataInsert[] = [
                'transaction_id' => $transaction->id,
                'ship_id'        => $transaction->res_waiter_id,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ];
        }

        if (!empty($dataInsert)) {
            TransactionShip::query()->insert($dataInsert);
        }

        return 0;
    }
}
