<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Migrate2015112601 extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crowd_fundings', function ($table) {
            $table->timestamp('end_at');      //众筹结束时间
        });

        DB::statement('UPDATE t_crowd_fundings t1, t_crowd_fundings t2 SET t1.end_at = FROM_UNIXTIME((UNIX_TIMESTAMP(t2.active_at) + (t2.c_time * 86400)), "%Y-%m-%d %H:%i:%s")
            WHERE t1.cf_id = t2.cf_id;');

        Schema::table('users', function ($table) {
            $table->string('invite_code', 7)->default('')->nullable();
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
