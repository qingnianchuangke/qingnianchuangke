<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ProductCate extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function ($table) {
            $table->tinyInteger('p_cate');  //普通产品-1:校创精品 2:精神世界 3:品质生活 4:娱乐科技 5:运动健康 6:校创服务 7:其他分类, 二手商品-1:图文影音 2:体育用品 3:创意手工 4:虚拟商品 5:数码科技 6:其他分类
            $table->tinyInteger('p_type');   //1:普通产品 2:二手商品
        });

        Schema::table('booths', function ($table) {
            $table->tinyInteger('b_cate')->default(7); //1:校创精品 2:精神世界 3:品质生活 4:娱乐科技 5:运动健康 6:校创服务 7:其他分类
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
