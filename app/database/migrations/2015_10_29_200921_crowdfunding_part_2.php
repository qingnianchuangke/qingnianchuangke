<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrowdfundingPart2 extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crowd_fundings', function ($table) {
            $table->increments('cf_id');      // 自增ID
            $table->integer('u_id');
            $table->integer('b_id');
            $table->integer('s_id');
            $table->integer('c_id');
            $table->tinyInteger('c_status');    // 众筹状态 1-审核中, 2-审核未通过, 3-众筹失败, 4-众筹中, 5-众筹成功
            $table->string('c_title', 63);  // 标题
            $table->text('c_brief')->nullable();         // 描述
            $table->text('c_yield_desc')->nullable();   // 回报描述
            $table->text('c_content')->nullable();      // 图文内容-文字部分
            $table->string('c_imgs', 2047)->nullable(); // 图片
            $table->smallInteger('c_yield_time');   // 回报时长-众筹结束后XX天
            $table->smallInteger('c_time');         // 众筹时长
            $table->tinyInteger('c_shipping');      // 是否配送
            $table->decimal('c_shipping_fee', 5, 2);    // 配送费
            $table->decimal('c_target_amount', 10, 2);   // 众筹总额
            $table->tinyInteger('c_cate');  // 众筹类型 1-娱乐活动, 2-生活百事, 3-创业募资, 4-艺术创作, 5-设计发明, 6-科学研究, 7-公益事业
            $table->timestamp('created_at');
            $table->timestamp('active_at');
        });

        Schema::create('crowd_funding_products', function ($table) {
            $table->increments('p_id');
            $table->integer('cf_id');
            $table->integer('u_id');
            $table->integer('b_id');
            $table->string('p_imgs', 2047)->nullable();
            $table->string('p_title', 63);
            $table->string('p_desc')->nullable();
            $table->decimal('p_price', 10, 2);
            $table->tinyInteger('p_status');    //产品状态: -1-禁用 1-上架 2-下架
            $table->smallInteger('p_max_quantity');
            $table->smallInteger('p_target_quantity');
            $table->smallInteger('p_sort');
            $table->timestamp('created_at');
        });

        Schema::table('carts', function ($table) {
            $table->tinyInteger('c_type');  // 购物车类型, 1-普通, 2-众筹, 3-二手, 4-拍卖
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
