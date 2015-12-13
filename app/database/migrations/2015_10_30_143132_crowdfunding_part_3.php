<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrowdfundingPart3 extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crowd_funding_products', function ($table) {
            $table->smallInteger('p_sold_quantity');
        });

        Schema::create('replies', function ($table) {
            $table->increments('id');
            $table->integer('to_id')->nullable();
            $table->timestamp('created_at');
            $table->string('content', 511);
            $table->integer('u_id');
            $table->string('u_name', 31);
            $table->tinyInteger('status');  //状态 1-有效 0-无效
            $table->integer('to_u_id')->nullable();
            $table->string('to_u_name', 31)->nullable();
        });

        Schema::create('repliables', function ($table) {
            $table->integer('reply_id');
            $table->integer('repliable_id');
            $table->string('repliable_type', 63);
        });

        DB::table('carts')->update(['c_type' => 1]);
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
