<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Migrate2015112701 extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $list = User::whereNull('u_invite_code')->orWhere('u_invite_code', '=', '')->get();
        foreach ($list as $key => $user) {
            $user->u_invite_code = $user->getInviteCode();
            $user->save();
        }

        Schema::create('log_user_invite_codes', function ($table) {
            $table->increments('id');
            $table->integer('inviter_id');
            $table->integer('u_id');
            $table->decimal('amount');
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
