<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Migrate2015112801 extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booths', function ($table) {
            $table->integer('pv_id');        // 省id
        });

        Schema::table('crowd_fundings', function ($table) {
            $table->integer('pv_id');        // 省id
        });

        Schema::table('promotion_infos', function ($table) {
            $table->integer('pv_id');
        });

        $list = Booth::with(['school'])->get();
        foreach ($list as $key => $booth) {
            $pv_id = $booth->school->t_province;
            $booth->pv_id = $pv_id;
            $booth->save();
        }

        $list = CrowdFunding::with(['school'])->get();
        foreach ($list as $key => $funding) {
            $pv_id = $funding->school->t_province;
            $funding->pv_id = $pv_id;
            $funding->save();
        }

        $list = PromotionInfo::with(['school'])->get();
        foreach ($list as $key => $promo) {
            $pv_id = $promo->school->t_province;
            $promo->pv_id = $pv_id;
            $promo->save();
        }
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
