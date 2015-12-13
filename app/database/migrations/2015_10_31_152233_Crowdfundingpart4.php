<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Crowdfundingpart4 extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crowd_fundings', function ($table) {
            $table->tinyInteger('c_open_file')->default(0);
            $table->integer('c_praise_count')->default(0);
        });

        Schema::table('users', function ($table) {
            $table->integer('u_priase_count')->default(0);
        });

        Schema::table('booths', function ($table) {
            $table->integer('b_praise_count')->default(0);
        });

        Schema::table('products', function ($table) {
            $table->integer('p_praise_count')->default(0);
        });

        Schema::table('user_profile_bases', function ($table) {
            $table->string('u_apartment_no')->nullable();
        });

        Schema::table('tmp_user_profile_bases', function ($table) {
            $table->string('u_apartment_no')->nullable();
        });

        Schema::table('promotion_infos', function ($table) {
            $table->timestamp('updated_at');
            $table->tinyInteger('p_push_count')->default(0);
        });

        Schema::create('praises', function ($table) {
            $table->increments('id');
            $table->integer('u_id');
            $table->timestamp('created_at');
            $table->string('u_name', 31);
        });

        Schema::create('praisables', function ($table) {
            $table->increments('praise_id');
            $table->integer('praisable_id');
            $table->string('praisable_type');
        });

        Schema::create('favorites', function ($table) {
            $table->increments('id');
            $table->integer('u_id');
            $table->timestamp('created_at');
            $table->string('u_name', 31);
        });

        Schema::create('favoriables', function ($table) {
            $table->increments('favorite_id');
            $table->integer('favoriable_id');
            $table->string('favoriable_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
    }
}
