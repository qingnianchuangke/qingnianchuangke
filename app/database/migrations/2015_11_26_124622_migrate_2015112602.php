<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Migrate2015112602 extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crowd_fundings', function ($table) {
            $table->tinyInteger('c_local_only')->nullable();    // 限制本校购买 0-否 1-是
        });

        Schema::create('auctions', function ($table) {
            $table->increments('a_id');
            $table->integer('e_id');        // 对应event item id
            $table->string('a_sub_title')->nullable();  // 附标题
            $table->decimal('a_cost', 10, 2)->nullable();   // 成本价
            $table->decimal('a_margin', 10, 2)->nullable(); // 保证金
            $table->integer('a_win_id')->nullable();    // 获胜标 id
            $table->string('a_win_username')->nullable();   // 获胜用户名称
            $table->decimal('a_win_price')->nullable(); // 获胜标价格
            $table->tinyInteger('a_status');    // 状态 1-开启 2-待支付 3-已支付 4-流拍
            $table->timestamp('created_at');
        });

        Schema::create('auction_bids', function ($table) {
            $table->increments('b_id');     // 出标 id
            $table->integer('a_id');        // 拍卖 id
            $table->integer('u_id');        // 用户 id
            $table->timestamp('created_at');    // 出标时间
            $table->decimal('b_price', 10, 2);  // 出标价格
            $table->tinyInteger('is_win')->default(0);  // 是否胜出
            $table->tinyInteger('is_pay')->default(0);  // 是否支付
        });

        Schema::create('auction_blacklists', function ($table) {
            $table->increments('id');
            $table->integer('u_id');
            $table->integer('a_id');
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->string('remark')->nullable();
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
