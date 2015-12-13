<?php
/**
* 
*/
use \Illuminate\Filesystem\Filesystem;

class EmergencyController extends \BaseController
{
    public function login()
    {
        $k = Input::get('k', '1');
        if ($k != 'qnck') {
            echo '非法访问, 系统退出...';
            exit();
        }
    }

    public function sendOrders()
    {
        set_time_limit(60000);
        try {
            $user = User::find(5);
            if ($user->u_priase_count == 0) {
                throw new Exception("已经执行过了", 30001);
            } else {
                $user->u_priase_count = 0;
                $user->save();
            }
            $str_text = '恭喜“双11不怕剁手”众筹活动已成功，您被众筹发起者选中，请于12日18时前凭此信息到零栋铺子领取众筹回报。4006680550';
            $str_push = '恭喜“双11不怕剁手”众筹活动已成功，您被众筹发起者选中，请于12日18时前凭此信息到零栋铺子领取众筹回报。4006680550';

            $orders = Order::selectRaw('right(`t_orders`.`o_number`, 4) AS seed, `t_orders`.*')
            ->join('carts', function ($q) {
                $q->on('orders.o_id', '=', 'carts.o_id');
            })->where('carts.c_type', '=', 2)->where('carts.p_id', '=', 4)->orderBy('seed', 'DESC')->limit(111)->get();
            
            foreach ($orders as $key => $order) {
                if (empty($order)) {
                    continue;
                }
                $phones = $order->o_shipping_phone;
                $pushObj = new PushMessage($order->u_id);
                $pushObj->pushMessage($str_push);
                echo 'pushed to '.$order->u_id.' </br>';
                $phoneObj = new Phone($order->o_shipping_phone);
                $phoneObj->sendText($str_text);
                echo 'texted to '.$order->o_shipping_phone.' </br>';
            }

            File::put('/var/www/qingnianchuangke/phones', implode(',', $phones));

            $re = Tools::reTrue('发送中奖信息成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '发送中奖信息失败:'.$e->getMessage());
        }

        return Response::json($re);
    }

    public function countUsers()
    {
        $count = DB::table('users')->where('u_mobile', '>', 12000000000)->count();
        echo "当前注册人数:".$count.'人';
        exit();
    }

    public function fakeUser()
    {
        $this->login();
        $batch = Input::get('batch', 0);
        if (!$batch) {
            echo "need batch number";
            die();
        }
        $mobile = DB::table('users')->select('u_mobile')->where('u_mobile', '<', '11000000000')->orderBy('u_mobile', 'DESC')->first();
        if (empty($mobile->u_mobile)) {
            $mobile = 10000000000;
        } else {
            $mobile = $mobile->u_mobile;
        }
        echo "mobile start at ".$mobile."</br>";
        echo "batch number is ".$batch."</br>";
        set_time_limit(0);
        $file = new Filesystem();
        $re = $file->files('/var/www/qingnianchuangke/head_img');
        foreach ($re as $key => $path) {
            $user = new User;
            $user->u_mobile = ++$mobile;
            $user->u_name = $user->u_nickname = $file->name($path);
            $ext = $file->extension($path);
            echo "add:".$user->u_name."</br>";
            $user->u_head_img = 'http://qnck001.oss-cn-hangzhou.aliyuncs.com/user_head/'.$batch.'/'.($key+1).'.'.$ext;
            $user->fakeUser();
        }
        echo 'done';
    }

    public function rename()
    {
        $this->login();
        set_time_limit(0);
        $file = new Filesystem();
        $re = $file->files('/Users/tingliu/Downloads/head_img');
        $count = 1;
        foreach ($re as $key => $path) {
            $new_name = $count.'.'.$file->extension($path);
            $path_seg = explode('/', $path);
            array_pop($path_seg);
            $new_path = implode('/', $path_seg);
            $new_path = '/'.$new_path.'/'.$new_name;
            $file->move($path, $new_path);
            $count++;
        }
        echo "DONE";
    }

    public function fakeCrowdFundingPurches($id)
    {
        set_time_limit(0);
        $this->login();
        $bottom = Input::get('bottom', '');
        $top = Input::get('top', '');
        $p_id = Input::get('p_id', '');

        try {
            if (!$p_id || !$top || !$bottom) {
                throw new Exception("需要关键数据", 1);
            }
            $funding = CrowdFunding::find($id);
            $funding->load(['eventItem']);

            $product = CrowdFundingProduct::find($p_id);
            $quantity = 1;

            $users = User::where('u_mobile', '>=', $bottom)->where('u_mobile', '<=', $top)->get();
            foreach ($users as $key => $user) {
                $u_id = $user->u_id;
                // sku need to be calulated before cart generated
                $product->loadProduct($quantity);
                // add cart
                $cart = new Cart();
                $cart->p_id = $p_id;
                $cart->p_name = $product->p_title;
                $cart->u_id = $u_id;
                $cart->b_id = $product->b_id;
                $cart->created_at = Tools::getNow();
                $cart->c_quantity = $quantity;
                $cart->c_price = $product->p_price;
                $cart->c_amount = $product->p_price * $quantity;
                $cart->c_discount = 100;
                $cart->c_price_origin = $product->p_price;
                $cart->c_amount_origin = $product->p_price * $quantity;
                $cart->c_status = 2;
                $cart->c_type = 2;
                $re = $cart->save();
                if (!$re) {
                    throw new Exception("提交库存失败", 7006);
                }
                $shipping_address = 'Fake Purches';
                $shipping_name = $user->u_name;
                $shipping_phone = $user->u_mobile;

                $date_obj = new DateTime($funding->eventItem->e_start_at);
                $delivery_time_obj = $date_obj->modify('+'.($funding->c_time+$funding->c_yield_time).'days');

                // add order
                $order_group_no = Order::generateOrderGroupNo($u_id);
                $rnd_str = rand(10, 99);
                $order_no = $order_group_no.$cart->b_id.$rnd_str;
                $order = new Order();
                $order->u_id = $u_id;
                $order->b_id = $cart->b_id;
                $order->o_amount_origin = $cart->c_amount_origin;
                $order->o_amount = $cart->c_amount;
                $order->o_shipping_fee = $funding->c_shipping_fee;
                $order->o_shipping_name = $shipping_name;
                $order->o_shipping_phone = $shipping_phone;
                $order->o_shipping_address = $shipping_address;
                $order->o_delivery_time = $delivery_time_obj->format('Y-m-d H:i:s');
                $order->o_shipping = $funding->c_shipping;
                $order->o_comment = 'Fake Order';
                $order->o_number = $order_no;
                $order->o_group_number = $order_group_no;
                $o_id = $order->addOrder();

                Cart::bindOrder([$order->o_id => [$cart->c_id]]);

                $cart->checkout();
                $order->o_status = 2;
                $order->o_shipping_status = 10;
                $order->paied_at = Tools::getNow();
                $order->save();
            }
            
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        echo "done";
    }
}
