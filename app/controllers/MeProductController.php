<?php
/**
*
*/
class MeProductController extends \BaseController
{
    public function postFlea()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        
        $mobile = Input::get('mobile', '');
        
        $prodName = Input::get('prod_name', '');
        $prodDesc = Input::get('content', '');
        $prodBrief = Input::get('prod_brief', '');
        $price = Input::get('price', '');
        $publish = Input::get('publish', 1);
        $product_cate = Input::get('cate', 7);
        $active_at = Input::get('active_at');
        if (empty($active_at)) {
            $active_at = Tools::getNow();
        }

        $open_file = Input::get('open_file', 0);
        if (!$product_cate) {
            $product_cate = 7;
        }
        $prodDesc = urldecode($prodDesc);
        $img_token = Input::get('img_token', '');

        try {
            $user = User::chkUserByToken($token, $u_id);

            $booth = Booth::where('u_id', '=', $u_id)->first();
            if (empty($booth)) {
                $user->load('school');
                $school = $user->school;
                $booth = new Booth();
                $booth->u_id = $u_id;
                $booth->b_type = 7;
                $booth->c_id = $school->t_city;
                $booth->s_id = $school->t_id;
                $booth->pv_id = $school->t_province;
                $booth->b_with_fund = 0;
                $booth->latitude = $user->latitude;
                $booth->longitude = $user->longitude;
                $booth->save();
            }

            $product = new Product();
            $product->b_id = $booth->b_id;
            $product->p_title = $prodName;
            $product->u_id = $u_id;
            $product->p_cost = 0;
            $product->p_price_origin = $price;
            $product->p_price = $price;
            $product->p_discount = 0;
            $product->p_desc = $prodDesc;
            $product->p_brief = $prodBrief;
            $product->p_status = $publish == 1 ? 1 : 2;
            $product->p_cate = $product_cate;
            $product->active_at = $active_at;
            $product->p_type = 2;
            $product->open_file = $open_file;
            $product->p_mobile = $mobile;
            $p_id = $product->addProduct();
            $quantity = new ProductQuantity();
            $quantity->p_id = $p_id;
            $quantity->b_id = $booth->b_id;
            $quantity->u_id = $u_id;
            $quantity->q_total = 1;

            $quantity->addQuantity();

            if ($img_token) {
                $imgObj = new Img('product', $img_token);
                $imgs = $imgObj->getSavedImg($p_id, '', true);
                $product->p_imgs = implode(',', $imgs);
                $product->save();
            }

            $re = Tools::reTrue('添加产品成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '添加产品失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function putFlea($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);


        $mobile = Input::get('mobile', '');

        $prodName = Input::get('prod_name', '');
        $prodDesc = Input::get('content', '');
        $prodBrief = Input::get('prod_brief', '');
        $price = Input::get('price', '');
        $publish = Input::get('publish', 1);
        $product_cate = Input::get('cate', 7);
        $active_at = Input::get('active_at');
        $open_file = Input::get('open_file', 0);
        
        if (empty($active_at)) {
            $active_at = Tools::getNow();
        }

        $img_token = Input::get('img_token', '');

        $prodDesc = urldecode($prodDesc);

        $modified_img = Input::get('modified_img', '');
        $modified_img_index = Input::get('modified_img_index', '');

        if ($modified_img) {
            $modified_img = explode(',', $modified_img);
        }

        try {
            $user = User::chkUserByToken($token, $u_id);
            $product = Product::find($id);
            if (empty($product) || $product->u_id != $u_id) {
                throw new Exception("没有找到请求的产品", 1);
            }

            $product->p_title = $prodName;
            $product->p_desc = $prodDesc;
            $product->sort = 1;
            $product->p_cate = $product_cate;
            $product->p_brief = $prodBrief;
            $product->p_status = $publish == 1 ? 1 : 2;
            $product->p_price_origin = $price;
            $product->p_price = $price;
            $product->active_at = $active_at;
            $product->p_mobile = $mobile;
            $product->open_file = $open_file;

            $old_imgs = Img::toArray($product->p_imgs);
            if (empty($old_imgs['cover_img'])) {
                $cover_img = '';
            } else {
                $cover_img = $old_imgs['cover_img'];
                unset($old_imgs['cover_img']);
            }

            if (is_numeric($modified_img_index)) {
                $imgObj = new Img('product', $img_token);
                $new_paths = [];
                if (!empty($modified_img)) {
                    foreach ($modified_img as $old_path) {
                        $new_path = $imgObj->reindexImg($id, $modified_img_index, $old_path);
                        $new_paths[] = $new_path;
                        $modified_img_index++;
                    }
                    foreach ($old_imgs as $obj) {
                        if (!in_array($obj, $new_paths)) {
                            $imgObj->remove($id, $obj);
                        }
                    }
                    $new_paths = Img::attachHost($new_paths);
                    
                    $product->p_imgs = implode(',', $new_paths);
                }
            }
            if ($cover_img) {
                if ($product->p_imgs) {
                    $product->p_imgs .= ','.$cover_img;
                } else {
                    $product->p_imgs = $cover_img;
                }
            }
            if ($img_token) {
                $imgObj = new Img('product', $img_token);
                $imgs = $imgObj->getSavedImg($id, $product->p_imgs, true);
                if (!empty($modified_img)) {
                    foreach ($modified_img as $del) {
                        if (array_key_exists($del, $imgs)) {
                            unset($imgs[$del]);
                        }
                    }
                }
                $product->p_imgs = implode(',', $imgs);
            }
            $product->save();

            $re = Tools::reTrue('更新产品成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '更新产品失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function delFlea($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        DB::beginTransaction();
        try {
            $user = User::chkUserByToken($token, $u_id);
            $product = Product::find($id);
            if (empty($product) || $product->u_id != $u_id) {
                throw new Exception("没有找到请求的产品", 2001);
            }
            $quantity = ProductQuantity::where('p_id', '=', $id)->first();
            if (empty($quantity)) {
                throw new Exception("获取库存信息失败", 2001);
            }
            $product->delete();
            $quantity->delete();
            $re = Tools::reTrue('删除成功');
            DB::commit();
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '删除失败:'.$e->getMessage());
            DB::rollback();
        }
        return Response::json($re);
    }

    public function getFlea($id)
    {
        try {
            $product = Product::find($id);
            $data = $product->showDetail();
            $re = Tools::reTrue('获取商品成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取商品失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function putAtop($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $product = Product::find($id);
            if ($product->u_id != $u_id) {
                throw new Exception("无法操作该商品", 7001);
            }
            $max_sort = DB::table('products')->where('u_id', '=', $u_id)->where('b_id', '=', $product->b_id)->max('sort');
            $product->sort = ++$max_sort;
            $product->save();
            $re = Tools::reTrue('商品置顶成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '商品置顶失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function pushPromo($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $promo = PromotionInfo::find($id);
            $now_obj = new DateTime();
            $last_update_obj = new DateTime($promo->updated_at);
            $diff = $now_obj->diff($last_update_obj);
            if ($diff->days == 0 && $promo->p_push_count < 3) {
                $promo->p_push_count++;
                $promo->updated_at = $now_obj->format('Y-m-d H:i:s');
            } else {
                throw new Exception("每天只能推送3次", 7001);
            }
            $re = Tools::reTrue('商品置顶成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '商品置顶失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function listFlea()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        $per_page = Input::get('per_page', 30);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $query = Product::with([
                'booth' => function ($q) {
                    $q->with(['user', 'school']);
                },
                'quantity',
                'praises' => function ($q) {
                    $q->where('praises.u_id', '=', $this->u_id);
                },
                ])
            ->where('u_id', '=', $u_id)->where('p_type', '=', 2);
            $list = $query->orderBy('created_at', 'DESC')->paginate($per_page);
            $data = [];
            foreach ($list as $key => $product) {
                $tmp = $product->showInList();
                if (count($product->praises) > 0) {
                    $tmp['is_praised'] = 1;
                } else {
                    $tmp['is_praised'] = 0;
                }
                $data[] = $tmp;
            }
            $re = Tools::reTrue('获取我发布的产品成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取我发布的产品失败:'.$e->getMessage());
        }
        return Response::json($re);
    }
}
