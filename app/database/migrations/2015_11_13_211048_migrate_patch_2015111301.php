<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MigratePatch2015111301 extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function ($table) {
            $table->tinyInteger('u_is_club_verified');      // 社团是否认证
            $table->tinyInteger('u_is_verified');           // 是否认校园证用户
        });

        Schema::create('organizations', function ($table) {
            $table->increments('o_id');
            $table->integer('u_id');   // 寄生用户
            $table->string('o_titile', 63);
            $table->text('o_brief')->nullable();
            $table->string('o_official_url')->nullable();
            $table->tinyInteger('o_status');    // 组织状态 1-待审核, 2-审核通过, 3-审核不通过
            $table->string('o_imgs', 2047)->nullable();
            $table->timestamp('created_at');
            $table->tinyInteger('o_type');      //组织类型, 1-普通组织 2-青创官方
        });

        Schema::create('clubs', function ($table) {
            $table->increments('c_id');
            $table->integer('u_id');    // 寄生用户
            $table->string('c_title', 63);
            $table->text('c_brief')->nullable();
            $table->string('c_official_url')->nullable();
            $table->tinyInteger('c_status');    // 组织状态 1-待审核, 2-审核通过, 3-审核不通过
            $table->integer('s_id');    // 学校id
            $table->string('c_imgs', 2047)->nullable();
            $table->timestamp('created_at');
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
