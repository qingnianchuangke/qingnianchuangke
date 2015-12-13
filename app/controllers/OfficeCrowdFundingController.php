<?php
/**
*
*/
class OfficeCrowdFundingController extends \BaseController
{
    public function postFunding()
    {
        $u_id = Tools::getOfficialUserId();
        $b_id = Tools::getOfficialBoothId();

        $title = Input::get('title', '');
        $amount = Input::get('amount', 0);
        $time = Input::get('time', 0);
        $yield_time = Input::get('yield_time', 0);
        $shipping = Input::get('shipping', 0);
        $shipping_fee = Input::get('shipping_fee', 0);
        $brief = Input::get('brief', '');
        $yield_desc = Input::get('yield_desc', '');
        $content = Input::get('content', '');
        $open_file = Input::get('open_file', 0);
        $active_at = Input::get('active_at');
        $local_only = Input::get('local_only', 0);
        $range = Input::get('range', 1);
        $cities = Input::get('cities', 0);
        $schools = Input::get('schools', 0);

        if (empty($active_at)) {
            $active_at = Tools::getNow();
        }

        $mobile = Input::get('mobile', '');

        $price = Input::get('price', 0);
        $quantity = Input::get('quantity', 0);
        $is_limit = Input::get('is_limit', 0);

        $img_token = Input::get('img_token', '');

        $content = urldecode($content);

        DB::beginTransaction();
        try {
            $user = User::find($u_id);
            $user->load('profileBase', 'school');
            $booth = Booth::find($b_id);

            // add event
            $event = new EventItem();
            $event->e_title = $title;
            $event->e_brief = $brief;
            $event->e_range = $range;
            $event->e_start_at = $active_at;
            $date_obj = new DateTime($active_at);
            $date_obj->modify('+'.$time.' days');
            $event->e_end_at = $date_obj->format('Y-m-d H:i:s');
            $event->addEvent();
            $e_id = $event->e_id;

            // add funding
            $crowd_funding = new CrowdFunding();
            $crowd_funding->u_id = $u_id;
            $crowd_funding->b_id = $booth->b_id;
            $crowd_funding->c_yield_desc = $yield_desc;
            $crowd_funding->c_content = $content;
            $crowd_funding->c_yield_time = $yield_time;
            $crowd_funding->u_mobile = $mobile;
            $crowd_funding->c_time = $time;
            $crowd_funding->c_shipping = $shipping;
            $crowd_funding->c_shipping_fee = $shipping_fee;
            $crowd_funding->c_target_amount = $amount;
            $crowd_funding->c_amount = 0.00;
            $crowd_funding->c_local_only = $local_only;
            $crowd_funding->c_praise_count = 0;
            $crowd_funding->c_remark = '';
            $crowd_funding->c_open_file = $open_file;
            $crowd_funding->c_status = 4;
            $crowd_funding->c_cate = 8;
            $crowd_funding->e_id = $e_id;

            $crowd_funding->addCrowdFunding();

            if ($img_token) {
                $imgObj = new Img('crowd_funding', $img_token);
                $crowd_funding->c_imgs = $imgObj->getSavedImg($crowd_funding->cf_id);
                $crowd_funding->save();

                $imgObj = new Img('event', $img_token);
                $event->cover_img = $imgObj->getSavedImg($event->e_id);
                $event->save();
            }

            // add funding product
            $funding_product = new CrowdFundingProduct();
            $funding_product->cf_id = $crowd_funding->cf_id;
            $funding_product->u_id = $u_id;
            $funding_product->b_id = $booth->b_id;
            $funding_product->p_title = $title;
            $funding_product->p_desc = '';
            $funding_product->p_price = $price;
            $funding_product->p_target_quantity = $quantity;
            $funding_product->p_sort = 0;
            if ($is_limit) {
                $funding_product->p_max_quantity = $quantity;
            } else {
                $funding_product->p_max_quantity = 0;
            }

            $funding_product->addProduct();

            if ($range == 1) {
                $event_range = new EventRange(['c_id' => 0, 'p_id' => 0, 's_id' => 0]);
                $event->ranges()->save($event_range);
            }

            if ($cities && $range == 2) {
                $city_sets = explode(',', $cities);
                foreach ($city_sets as $key => $set) {
                    $array = explode('|', $set);
                    $event_range = new EventRange(['c_id' => $array[0], 'p_id' => $array[1]]);
                    if ($key) {
                        $new_event = $crowd_funding->cloneCrowdFunding();
                    } else {
                        $new_event = $event;
                    }
                    $new_event->ranges()->save($event_range);
                }
            }

            if ($schools && $range == 3) {
                $schools = explode(',', $schools);
                foreach ($schools as $key => $school) {
                    $event_range = new EventRange(['s_id' => $school]);
                    if ($key) {
                        $new_event = $crowd_funding->cloneCrowdFunding();
                    } else {
                        $new_event = $event;
                    }
                    $new_event->ranges()->save($event_range);
                }
            }

            $re = Tools::reTrue('添加众筹成功');
            DB::commit();
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '添加众筹失败:'.$e->getMessage());
            DB::rollback();
        }
        return Response::json($re);
    }

    public function listFunding()
    {
        $per_page = Input::get('per_page', 30);
        $filter_option = Input::get('filter_option', 0);

        try {
            $query = CrowdFunding::with('product', 'eventItem');
            if ($filter_option == 1) {
                $query = $query->where('c_cate', '=', 8);
            }

            $list = $query->paginate($per_page);
            $data['rows'] = [];
            foreach ($list as $key => $funding) {
                $data['rows'][] = $funding->showInList();
            }
            $data['total'] = $list->getTotal();
            $re = Tools::reTrue('获取众筹列表成功', $data, $list);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取众筹列表失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function getFunding($id)
    {
        try {
            $funding = CrowdFunding::find($id);
            if (empty($funding)) {
                throw new Exception("没有找到请求的数据", 10001);
            }
            $funding->load(['eventItem']);
            $data = $funding->showDetail();
            $re = Tools::reTrue('获取众筹信息成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取众筹信息失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function censorFunding($id)
    {
        $check = Input::get('check', 0);
        $remark = Input::get('remark', '');
        try {
            $funding = CrowdFunding::find($id);
            if ($check == 1) {
                $funding->c_status = 4;
            } else {
                $funding->c_status = 2;
            }
            $funding->c_remark = $remark;
            $funding->censor();
            $re = Tools::reTrue('审核众筹成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '审核众筹信息失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function delFunding($id)
    {
        $u_id = Tools::getOfficialUserId();

        DB::beginTransaction();
        try {
            $user = User::find($u_id);
            $crowd_funding = CrowdFunding::find($id);
            $crowd_funding->delCrowdFunding();
            $re = Tools::reTrue('删除众筹成功');
            DB::commit();
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '删除众筹失败:'.$e->getMessage());
            DB::rollback();
        }
        return Response::json($re);
    }
}
