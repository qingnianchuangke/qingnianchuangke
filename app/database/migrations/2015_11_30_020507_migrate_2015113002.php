<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Migrate2015113002 extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        set_time_limit(0);
        Schema::table('crowd_fundings', function ($table) {
            $table->integer('e_id');
        });
        $list = CrowdFunding::get();
        foreach ($list as $key => $funding) {
            // handle event
            $event = new EventItem();
            $event->e_title = $funding->c_title;
            $imgs = Img::toArray($funding->c_imgs);
            if (empty($imgs['cover_img'])) {
                $cover = '';
            } else {
                $cover = $imgs['cover_img'];
                unset($imgs['cover_img']);
            }
            $event->cover_img = $cover;
            $event->e_brief = $funding->c_brief;
            $event->e_range = 0;
            $event->e_start_at = $funding->active_at;
            $event->e_end_at = $funding->end_at;
            $event->created_at = $funding->created_at;
            $event->e_status = 1;
            $event->save();
            // handle ranges
            $range = new EventRange();
            $range->e_id = $event->e_id;
            $range->s_id = $funding->s_id;
            $range->c_id = $funding->c_id;
            $range->p_id = $funding->pv_id;
            $range->save();
            // handle imgs
            $funding->c_imgs = implode(',', $imgs);
            $funding->e_id = $event->e_id;
            $funding->save();
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
