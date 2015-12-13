<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MigratePatch20151107 extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_transaction_orders', function ($table) {
            $table->integer('l_id');
            $table->string('o_group_number');
            $table->primary('l_id');
        });

        Schema::table('log_transactions', function ($table) {
            $table->string('transaction_id');
        });
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
