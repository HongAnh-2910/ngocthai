<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTableTransitionSellLineChange extends Migration
{
    public function up()
    {
        $nameFile = basename(realpath(__FILE__), '.php');
        $sql = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . $nameFile . '.sql');
        DB::connection()->getPdo()->exec($sql);
    }
}
