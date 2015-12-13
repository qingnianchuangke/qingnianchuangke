<?php
/**
*
*/
class OfficeAdController extends \BaseController
{
    public function listAds()
    {
        $per_page = Input::get('per_page', 10000000);

        try {
            $list = Advertisement::with(['eventItem'])->paginate($per_page);
            $data = [];
            $data['rows'] = [];
            foreach ($list as $key => $ad) {
                $data['rows'][] = $ad->showDetail();
            }
            $data['total'] = $list->getTotal();
            $re = Tools::reTrue('获取广告成功', $data, $list);
        } catch (Exception $e) {
            $re = Tools::reTrue($e->getCode(), '获取广告失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function postAd()
    {
        $title = Input::get('title', '');
        $url = Input::get('url', '');
        $brief = Input::get('brief', '');
        $range = Input::get('range', '');
        $cities = Input::get('cities', '');
        $schools = Input::get('schools', '');
        $positions = Input::get('positions', '');
        $start_at = Input::get('start_at', '');
        $end_at = Input::get('end_at', '');

        $img_token = Input::get('img_token', '');

        try {
            $ad_event = new EventItem();
            $ad_event->e_title = $title;
            $ad_event->e_brief = $brief;
            $ad_event->url = $url;
            $ad_event->e_range = $range;
            $ad_event->e_start_at = $start_at;
            $ad_event->e_end_at = $end_at;
            $ad_event->addEvent();
            $e_id = $ad_event->e_id;

            if ($range == 1) {
                $event_range = new EventRange(['c_id' => 0, 'p_id' => 0, 's_id' => 0]);
                $ad_event->ranges()->save($event_range);
            }

            if ($cities && $range == 2) {
                $city_sets = explode(',', $cities);
                foreach ($city_sets as $set) {
                    $array = explode('|', $set);
                    $event_range = new EventRange(['c_id' => $array[0], 'p_id' => $array[1]]);
                    $ad_event->ranges()->save($event_range);
                }
            }

            if ($schools && $range == 3) {
                $schools = explode(',', $schools);
                foreach ($schools as $school) {
                    $event_range = new EventRange(['s_id' => $school]);
                    $ad_event->ranges()->save($event_range);
                }
            }

            if ($positions) {
                $positions = explode(',', $positions);
                foreach ($positions as $key => $position) {
                    $event_position = new EventPosition(['position' => $position]);
                    $ad_event->positions()->save($event_position);
                }
            }

            if ($img_token) {
                $imgObj = new Img('event', $img_token);
                $ad_event->cover_img = $imgObj->getSavedImg($e_id);
                $ad_event->save();
            }

            $ad = new Advertisement();
            $ad->e_id = $e_id;
            $ad->o_id = Tools::getOfficialOrgnizationId();
            $ad->addAd();
            $re = Tools::reTrue('添加广告成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '添加广告失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function getAd($id)
    {
        try {
            $ad = Advertisement::find($id);
            $data = $ad->showDetail();
            $re = Tools::reTrue('获取广告成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取广告失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function delAd($id)
    {
        DB::beginTransaction();

        try {
            $ad = Advertisement::find($id);
            $ad->delAd();
            $re = Tools::reTrue('删除成功');
            DB::commit();
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '删除失败:'.$e->getMessage());
            DB::rollback();
        }
        return Response::json($re);
    }
}
