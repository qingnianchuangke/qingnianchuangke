<?php
/**
*
*/

use Illuminate\Support\Collection;

class MarketController extends \BaseController
{
    public function hot()
    {
        $city = Input::get('city', 0);
        $province = Input::get('province', 0);
        $school = Input::get('school', 0);
        $key = Input::get('key', '');
        $range = Input::get('range', 3);
        $u_id = Input::get('u_id');
        $is_follow = Input::get('is_follow', 0);
        $cate = Input::get('cate', 0);

        $perPage = Input::get('per_page', 30);
        $page = Input::get('page', 1);

        try {
            if (!$u_id) {
                throw new Exception("请传入有效的用户id", 2001);
            }
            $user = User::find($u_id);
            $user->load('school');

            $query = PromotionInfo::with([
                'city',
                'school',
                'booth' => function ($q) {
                    $q->with(['user']);
                },
                'product' => function ($q) {
                    $q->with([
                        'promo',
                        'quantity',
                        'praises' => function ($qq) {
                            $qq->where('praises.u_id', '=', $this->u_id);
                        }]);
                }]);
            $query = $query->select('promotion_infos.*');
            $query = $query->leftJoin('products', function ($q) {
                $q->on('products.p_id', '=', 'promotion_infos.p_id')
                ->where('products.p_status', '=', 1)
                ->where('products.p_type', '=', 1);
            })->leftJoin('booths', function ($q) {
                $q->on('booths.b_id', '=', 'promotion_infos.b_id')
                ->where('booths.b_status', '=', 1);
            });

            if ($is_follow) {
                $query = $query->rightJoin('booth_follows', function ($q) use ($u_id) {
                    $q->on('booths.b_id', '=', 'booth_follows.b_id')
                    ->where('booth_follows.u_id', '=', $u_id);
                });
                $school = 0;
                $city = 0;
                $range = 1;
            }
            $query = $query->where('promotion_infos.p_range', '<=', $range);
            if ($school && $range == 3) {
                $query = $query->where('promotion_infos.s_id', '=', $school);
            }
            if ($city && $province && $range == 2) {
                $query = $query->where('promotion_infos.c_id', '=', $city)->where('promotion_infos.pv_id', '=', $province);
            }
            if ($cate) {
                $query = $query->where('products.p_cate', '=', $cate);
            }
            if ($key) {
                $query = $query->where(function ($q) use ($key) {
                    $q->where('promotion_infos.p_content', 'LIKE', '%'.$key.'%')
                    ->orWhere('booths.b_product_source', 'LIKE', '%'.$key.'%')
                    ->orWhere('booths.b_product_category', 'LIKE', '%'.$key.'%')
                    ->orWhere('booths.b_desc', 'LIKE', '%'.$key.'%')
                    ->orWhere('booths.b_title', 'LIKE', '%'.$key.'%')
                    ->orWhere('products.p_title', 'LIKE', '%'.$key.'%')
                    ->orWhere('products.p_desc', 'LIKE', '%'.$key.'%');
                });
            }
            $list = $query->orderBy('promotion_infos.created_at', 'DESC')->paginate($perPage);
            $data = [];
            foreach ($list as $k => $product) {
                $tmp = $product->showInListWithProduct();
                if (!empty($product->product->praises)) {
                    $tmp['is_praised'] = 1;
                } else {
                    $tmp['is_praised'] = 0;
                }
                $tmp['item_type'] = 1;
                $data[] = $tmp;
            }
            if (!$key) {
                $ad = Advertisement::fetchAd(2, $school, $city, $province, $range);
                if ($ad && $data) {
                    $data = array_merge($data, $ad);
                    $collection = new Collection($data);
                    $data = array_values($collection->toArray());
                } elseif ($ad && !$data && $page < 2) {
                    $data = $ad;
                }
            }
            $re = Tools::reTrue('获取首页商品成功', $data, $list);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取首页商品失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function flea()
    {
        $city = Input::get('city', 0);
        $province = Input::get('province', 0);
        $school = Input::get('school', 0);
        $key = Input::get('key', '');
        $range = Input::get('range', 0);
        $u_id = Input::get('u_id');
        $is_follow = Input::get('is_follow', 0);
        $cate = Input::get('cate', 0);

        $filter_option = Input::get('filter_option', 0);        // 1-within 1 day, 2-within 3 days, 3-within 7 days

        $perPage = Input::get('per_page', 30);
        $page = Input::get('page', 1);

        try {
            if (!$school) {
                $range = 2;
            }
            if (!$u_id) {
                throw new Exception("请传入有效的用户id", 2001);
            }
            $user = User::find($u_id);
            $user->load('school');

            $query = Product::with([
                'user',
                'booth' => function ($q) {
                    $q->with(['school']);
                },
                'quantity',
                'praises' => function ($q) {
                    $q->where('praises.u_id', '=', $this->u_id);
                },
                ]);
            $query = $query->select('products.*')->where('products.p_status', '=', 1)->where('products.p_type', '=', 2);
            $query = $query->leftJoin('booths', function ($q) {
                $q->on('booths.b_id', '=', 'products.b_id');
            });

            if ($is_follow) {
                $query = $query->rightJoin('booth_follows', function ($q) use ($u_id) {
                    $q->on('booths.b_id', '=', 'booth_follows.b_id')
                    ->where('booth_follows.u_id', '=', $u_id);
                });
                $school = 0;
                $city = 0;
                $range = 1;
            }
            if ($school && $range == 3) {
                $query = $query->where('booths.s_id', '=', $school);
            }
            if ($city && $province && $range == 2) {
                $query = $query->where('booths.c_id', '=', $city)->where('booths.pv_id', '=', $province);
            }
            if ($cate) {
                $query = $query->where('products.p_cate', '=', $cate);
            }
            if ($key) {
                $query = $query->where(function ($q) use ($key) {
                    $q->where('booths.b_product_source', 'LIKE', '%'.$key.'%')
                    ->orWhere('booths.b_product_category', 'LIKE', '%'.$key.'%')
                    ->orWhere('booths.b_desc', 'LIKE', '%'.$key.'%')
                    ->orWhere('booths.b_title', 'LIKE', '%'.$key.'%')
                    ->orWhere('products.p_title', 'LIKE', '%'.$key.'%')
                    ->orWhere('products.p_desc', 'LIKE', '%'.$key.'%');
                });
            }

            if ($filter_option) {
                if ($filter_option == 1) {
                    $days = 1;
                } elseif ($filter_option == 2) {
                    $days = 3;
                } else {
                    $days = 7;
                }
                $now = Tools::getNow(false);
                $now->modify('-'.$days.' days');
                $date = $now->format('Y-m-d H:i:s');
                $query = $query->where('products.active_at', '>', $date);
            }

            $list = $query->orderBy('products.created_at', 'DESC')->paginate($perPage);
            $data = [];
            $start = 0;
            $end = 0;
            foreach ($list as $k => $product) {
                $tmp = $product->showInList();
                if ($k == 0) {
                    $start = $end = $tmp['created_at_timestamps'];
                } else {
                    $start = min($start, $tmp['created_at_timestamps']);
                    $end = max($end, $tmp['created_at_timestamps']);
                }
                if (empty($tmp['booth']['school'])) {
                    $tmp['school'] = [];
                } else {
                    $tmp['school'] = $tmp['booth']['school'];
                }
                if (empty($tmp['booth']['city'])) {
                    $tmp['city'] = [];
                } else {
                    $tmp['city'] = $tmp['booth']['city'];
                }
                unset($tmp['booth']);
                if (count($product->praises) > 0) {
                    $tmp['is_praised'] = 1;
                } else {
                    $tmp['is_praised'] = 0;
                }
                $tmp['item_type'] = 1;
                $data[] = $tmp;
            }
            if (!$key) {
                $start = $start > 0 ? date('Y-m-d H:i:s', $start) : null;
                $end = ($end > 0 && $page != 1) ? date('Y-m-d H:i:s', $end) : null;
                if ($page == 1 && $list->getTotal() < $per_page) {
                    $start = null;
                }
                $ad = Advertisement::fetchAd(3, $start, $end, $school, $city, $province, $range);
                if ($ad && $data) {
                    $data = Advertisement::mergeArray($data, $ad);
                } elseif ($ad && !$data && $page < 2) {
                    $data = $ad;
                }
            }
            $re = Tools::reTrue('获取跳蚤市场商品成功', $data, $list);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取跳蚤市场商品失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function convenient()
    {
        $school = Input::get('school', 0);
        $key = Input::get('key', '');

        $perPage = Input::get('per_page', 30);

        try {
            $query = Booth::where('b_type', '=', 1)->where('b_status', '=', 1)->with(['user']);
            if ($key) {
                $query = $query->where(function ($q) use ($key) {
                    $q->where('b_product_source', 'LIKE', '%'.$key.'%')
                    ->orWhere('b_product_category', 'LIKE', '%'.$key.'%')
                    ->orWhere('b_desc', 'LIKE', '%'.$key.'%')
                    ->orWhere('b_title', 'LIKE', '%'.$key.'%');
                });
            }
            if ($school) {
                $query = $query->where('s_id', '=', $school);
            }
            $list = $query->paginate($perPage);
            $data = [];
            foreach ($list as $key => $booth) {
                $data[] = $booth->showDetail();
            }
            $re = Tools::reTrue('获取便利店成功', $data, $list);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取便利店失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function maker()
    {
        $school = Input::get('school', 0);
        $province = Input::get('province', 0);
        $city = Input::get('city', 0);
        $range = Input::get('range', 3);
        $key = Input::get('key', '');
        $cate = Input::get('cate', 0);
        $u_id = Input::get('u_id', 0);

        $perPage = Input::get('per_page', 30);

        try {
            if (!$u_id) {
                throw new Exception("需要传入用户ID", 7001);
            }
            $query = Booth::where('b_type', '=', 2)->where('b_status', '=', 1)->with([
                'user',
                'school',
                'city',
                'praises' => function ($q) {
                    $q->where('praises.u_id', '=', $this->u_id);
                },
                'favorites' => function ($q) {
                    $q->where('favorites.u_id', '=', $this->u_id);
                }
                ]);
            if ($key) {
                $query = $query->where(function ($q) use ($key) {
                    $q->where('b_product_source', 'LIKE', '%'.$key.'%')
                    ->orWhere('b_product_category', 'LIKE', '%'.$key.'%')
                    ->orWhere('b_desc', 'LIKE', '%'.$key.'%')
                    ->orWhere('b_title', 'LIKE', '%'.$key.'%');
                });
            }

            if ($school && $range == 3) {
                $query = $query->where('s_id', '=', $school);
            }
            if ($city && $province && $range == 2) {
                $query = $query->where('c_id', '=', $city)->where('pv_id', '=', $province);
            }
            if ($cate) {
                $query = $query->where('b_cate', '=', $cate);
            }

            $list = $query->paginate($perPage);
            $data = [];
            foreach ($list as $key => $booth) {
                $tmp = $booth->showDetail();
                $tmp['is_praised'] = 0;
                $tmp['is_favorited'] = 0;
                if (count($booth->praises) > 0) {
                    $tmp['is_praised'] = 1;
                }
                if (count($booth->favorites) > 0) {
                    $tmp['is_favorited'] = 1;
                }
                $data[] = $tmp;
            }
            $re = Tools::reTrue('获取创的店成功', $data, $list);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取创的店失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function listBooth()
    {
        $owner_id = Input::get('owner', 0);
        $u_id = Input::get('u_id', 0);

        try {
            if (!$u_id) {
                throw new Exception("请传入有效的用户id", 7001);
            }
            $data = Booth::with(['user'])->where('u_id', '=', $owner_id)->where('b_status', '=', 1)->get();
            $list = [];
            foreach ($data as $key => $booth) {
                $tmp = $booth->showDetail();
                $products_count = Product::where('b_id', '=', $booth->b_id)->where('p_status', '=', 1)->count();
                $tmp['prodct_count'] = $products_count;
                $chk_follow = BoothFollow::where('u_id', '=', $u_id)->where('b_id', '=', $booth->b_id)->first();
                if (empty($chk_follow)) {
                    $tmp['is_follow'] = 0;
                } else {
                    $tmp['is_follow'] = 1;
                }
                $list[] = $tmp;
            }
            $re = Tools::reTrue('获取我的所有店铺成功', $list);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取他的所有店铺失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function getBooth($id)
    {
        $u_id = Input::get('u_id', 0);

        try {
            if (!$u_id) {
                throw new Exception("需要传入用户ID", 2001);
            }
            $booth = Booth::find($id);
            if (empty($booth->b_id)) {
                throw new Exception("无法获取到请求的店铺", 7001);
            }
            if ($booth->b_status != 1) {
                throw new Exception("店铺当前不可用", 7001);
            }
            $booth->load([
                'user',
                'praises' => function ($q) {
                    $q->where('praise.u_id', '=', $this->u_id);
                },
                'favorites' => function ($q) {
                    $q->where('favorites.u_id', '=', $this->u_id);
                }
                ]);
            $boothInfo = $booth->showDetail();
            $products_count = Product::where('b_id', '=', $booth->b_id)->where('p_status', '=', 1)->count();
            $chk = BoothFollow::where('b_id', '=', $booth->b_id)->where('u_id', '=', $u_id)->first();
            if (empty($chk)) {
                $is_follow = 0;
            } else {
                $is_follow = 1;
            }

            $boothInfo['prodct_count'] = (int)$products_count;
            $boothInfo['is_follow'] = $is_follow;
            $boothInfo['is_praised'] = 0;
            $boothInfo['is_favorited'] = 0;
            if (count($booth->praises) > 0) {
                $boothInfo['is_praised'] = 1;
            }
            if (count($booth->favorites) > 0) {
                $boothInfo['is_favorited'] = 1;
            }
            $data = ['booth' => $boothInfo];
            $re = Tools::reTrue('获取他的店铺成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取他的店铺失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function listProduct()
    {
        $b_id = Input::get('booth', 0);
        $key = Input::get('key', '');

        $perPage = Input::get('per_page', 30);

        try {
            if (!$b_id) {
                throw new Exception("请传入店铺ID", 7001);
            }
            $query = Product::with(['promo', 'quantity'])->where('b_id', '=', $b_id)->where('p_status', '=', 1);
            if ($key) {
                $query = $query->where(function ($q) use ($key) {
                    $q->where('p_title', 'LIKE', '%'.$key.'%')
                    ->orWhere('p_desc', 'LIKE', '%'.$key.'%');
                });
            }
            $list = $query->paginate($perPage);
            $data = [];
            foreach ($list as $key => $product) {
                $data[] = $product->showInList();
            }
            $re = Tools::reTrue('获取产品列表成功', $data, $list);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取产品列表失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function getProduct($id)
    {
        try {
            $product = Product::with(['quantity',
                'promo',
                'replies' => function ($q) {
                    $q->with(['user', 'children' => function ($qq) {
                        $qq->where('r_status', '=', 1)->orderBy('created_at', 'DESC');
                    }])->where('to_r_id', '=', 0)->where('r_status', '=', 1)->where('to_u_id', '=', 0)->orderBy('created_at', 'DESC');
                }])->find($id);
            if (!empty($product->promo)) {
                $product->promo->load('praises');
            }
            if (empty($product->p_id)) {
                throw new Exception("无法找到请求的产品", 7001);
            }
            $data = $product->showDetail();
            $re = Tools::reTrue('获取产品成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取产品失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function postPromoPraise($id)
    {
        $token = Input::get('token');
        $u_id = Input::get('u_id');
        $type = Input::get('type');
        $promo = PromotionInfo::find($id);
        try {
            if (empty($promo)) {
                throw new Exception("请求的促销信息不存在", 7001);
            }
            $user = User::chkUserByToken($token, $u_id);
            $result = 2000;
            if ($type == 1) {
                $promo->p_praise_count += 1;
                $praise = new PromotionPraise();
                $praise->prom_id = $id;
                $praise->u_id = $user->u_id;
                $praise->u_name = $user->u_nickname;
                $praise->addPraise();
                $info = '点赞成功';
            } elseif ($type == 2) {
                if ($promo->p_praise_count > 0) {
                    $promo->p_praise_count -= 1;
                }
                $praise = PromotionPraise::where('prom_id', '=', $id)->where('u_id', '=', $user->u_id)->first();
                if (!empty($praise->t_id)) {
                    $praise->delete();
                }
                $info = '取消赞成功';
            } else {
                throw new Exception("请求的操作不存在", 7001);
            }
            $promo->save();
            $re = Tools::reTrue($info);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '点赞失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function postProductReply($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        $content = Input::get('content', '');
        $to = Input::get('to', 0);
        $parent = Input::get('parent', 0);

        try {
            $product = Product::find($id);
            $product->p_reply_count += 1;

            $user = User::chkUserByToken($token, $u_id);
            $reply = new ProductReply();
            $reply->p_id = $id;
            $reply->r_content = $content;
            $reply->u_id = $u_id;
            $reply->u_name = $user->u_nickname;

            if ($to > 0) {
                $toUser = User::find($to);
                if (empty($toUser->u_id)) {
                    throw new Exception("回复用户不存在", 7001);
                }
                $reply->to_u_name = $toUser->u_nickname;
            }
            $reply->to_u_id = $to;

            if ($parent > 0) {
                $parentReply = ProductReply::find($parent);
                if (empty($parentReply->r_id)) {
                    throw new Exception("回复对象不存在", 7001);
                }
            }
            $reply->to_r_id = $parent;
            $reply->addReply();
            $product->save();
            $re = Tools::reTrue('回复成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '恢复失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function postBoothFollow($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        $type = Input::get('type', 1);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $follow = new BoothFollow();
            $follow->b_id = $id;
            $follow->u_id = $u_id;
            if ($type == 1) {
                $msg = '关注成功';
                $follow->follow();
            } elseif ($type == 2) {
                $msg = '取消关注成功';
                $follow->unfollow();
            }
            $re = Tools::reTrue($msg);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '关注操作失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function listCarts()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        
        try {
            $user = User::chkUserByToken($token, $u_id);
            $list = Cart::with(['product', 'booth'])->where('u_id', '=', $u_id)->whereIn('c_status', [0,1])->get();
            $data = [];
            foreach ($list as $key => $cart) {
                $data[] = $cart->showInList();
            }
            $re = Tools::reTrue('获取购物车列表成功', $data);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取购物车列表失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function postCart()
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        $p_id = Input::get('product');
        $b_id = Input::get('booth');
        $quantity = Input::get('quantity');

        try {
            $user = User::chkUserByToken($token, $u_id);
            $cart = Cart::where('p_id', '=', $p_id)->where('u_id', '=', $u_id)->where('c_status', '=', 1)->first();
            // add new when there is not any cart before
            if (empty($cart->c_id)) {
                $cart = new Cart();
                $cart->b_id = $b_id;
                $cart->u_id = $u_id;
                $cart->p_id = $p_id;
                $cart->c_quantity = $quantity;
                $cart->c_type = 1;
                $cart->addCart();
            // cumilate quantity
            } else {
                $quantityOri = $cart->c_quantity;
                $cart->c_quantity += $quantity;
                $cart->updateCart($quantityOri);
            }

            $re = Tools::reTrue('添加购物车成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), $e->getMessage());
        }
        return Response::json($re);
    }

    public function putCart($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        $quantity = Input::get('quantity');

        try {
            if ($quantity == 0) {
                throw new Exception("请传入有效的库存", 7001);
            }
            $user = User::chkUserByToken($token, $u_id);
            $cart = Cart::find($id);
            if (empty($cart->c_id)) {
                throw new Exception("无法获取到购物车", 7001);
            }
            if ($cart->c_status != 1 || $cart->u_id != $u_id) {
                throw new Exception("该购物车已经失效", 7001);
            }
            $quantityOri = $cart->c_quantity;
            $cart->c_quantity = $quantity;
            $cart->updateCart($quantityOri);
            $re = Tools::reTrue('添加购物车成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '添加购物车失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function delCart($id)
    {
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);

        try {
            $user = User::chkUserByToken($token, $u_id);
            $cart = Cart::find($id);
            if (empty($cart->c_id)) {
                throw new Exception("无法获取到购物车", 7001);
            }
            if ($cart->c_status != 1 || $cart->u_id != $u_id) {
                throw new Exception("该购物车已经失效", 7001);
            }
            $cart->removeCart();
            $re = Tools::reTrue('删除购物车成功');
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '删除购物车失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function postOrder()
    {
        $now = new DateTime();
        $token = Input::get('token', '');
        $u_id = Input::get('u_id', 0);
        $amount_origin = Input::get('amount_origin', 0);
        $amount = Input::get('amount', 0);
        $shipping_fee = Input::get('shipping_fee', 0);
        $shipping_name = Input::get('shipping_name', '');
        $shipping_phone = Input::get('shipping_phone', '');
        $shipping_address = Input::get('shipping_address', '');
        $shipping = Input::get('shipping', 1);
        $delivery_time = Input::get('delivery_time', $now->format('Y-m-d H:i:s'));
        $comment = Input::get('comment', '');

        $carts = Input::get('carts', null);
        DB::beginTransaction();
        try {
            if (empty($carts)) {
                throw new Exception("请传入有效的购物车", 1);
            }
            $carts = explode(',', $carts);
            $user = User::chkUserByToken($token, $u_id);
            $list = Cart::whereIn('c_id', $carts)->get();
            $total_amount = 0;
            $total_amount_origin = 0;
            $b_ids = [];
            $amount_origin_sum = 0;
            $amount_sum = 0;
            $groups = [];
            foreach ($list as $key => $cart) {
                if ($cart->u_id != $u_id) {
                    throw new Exception("没有权限操作该购物车", 7001);
                }
                if ($cart->c_status > 1) {
                    throw new Exception("购物车无效", 7005);
                }
                $cart->updateCart($cart->c_quantity);
                if (empty($groups[$cart->b_id]['amount_origin'])) {
                    $groups[$cart->b_id]['amount_origin'] = 0;
                    $groups[$cart->b_id]['amount'] = 0;
                    $groups[$cart->b_id]['carts_ids'] = [];
                }
                $groups[$cart->b_id]['amount_origin'] += $cart->c_amount_origin;
                $groups[$cart->b_id]['amount'] += $cart->c_amount;
                $groups[$cart->b_id]['carts_ids'][] = $cart->c_id;
                $b_ids[] = $cart->b_id;

                $amount_sum += $groups[$cart->b_id]['amount'];
                $amount_origin_sum += $groups[$cart->b_id]['amount_origin'];
            }

            if (($amount_origin_sum != $amount_origin) || ($amount_sum != $amount)) {
                throw new Exception("支付金额已刷新, 请重新提交订单", 9003);
            }

            $order_group_no = Order::generateOrderGroupNo($u_id);
            foreach ($groups as $b_id => $group) {
                $rnd_str = rand(10, 99);
                $order_no = $order_group_no.$b_id.$rnd_str;
                $order = new Order();
                $order->u_id = $u_id;
                $order->b_id = $b_id;
                $order->o_amount_origin = $group['amount_origin'];
                $order->o_amount = $group['amount'];
                $order->o_shipping_fee = $shipping_fee;
                $order->o_shipping_name = $shipping_name;
                $order->o_shipping_phone = $shipping_phone;
                $order->o_shipping_address = $shipping_address;
                $order->o_delivery_time = $delivery_time;
                $order->o_shipping = $shipping;
                $order->o_comment = $comment;
                $order->o_number = $order_no;
                $order->o_group_number = $order_group_no;
                $o_id = $order->addOrder();
                Cart::bindOrder([$order->o_id => $group['carts_ids']]);
            }

            // push msg to seller
            $list = Booth::whereIn('b_id', $b_ids)->get();
            foreach ($list as $key => $booth) {
                $obj = new MessageDispatcher($booth->u_id);
                $obj->fireTextToUser('您有新的订单, 请及时发货');
            }
            $re = Tools::reTrue('提交订单成功', ['order_no' => $order_group_no]);
            DB::commit();
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), $e->getMessage());
            DB::rollback();
        }
        return Response::json($re);
    }

    public function listBoothFollow($id)
    {
        $u_id = Input::get('u_id', 0);
        $school = Input::get('school', 0);
        $profession = Input::get('prof', 0);
        $entry_year = Input::get('entry_year', 0);
        $gender = Input::get('gender', 0);
        $key = Input::get('key', '');

        $per_page = Input::get('per_page', 30);

        try {
            $user = User::find($u_id);
            $user_contact = UserProfileBase::find($u_id);
            $query = BoothFollow::with(['follower', 'follower.school'])->where('b_id', '=', $id)->select('booth_follows.*')
            ->leftJoin('users', function ($q) {
                $q->on('users.u_id', '=', 'booth_follows.u_id');
            })->leftJoin('users_contact_peoples', function ($q) {
                $q->on('users_contact_peoples.u_id', '=', 'booth_follows.u_id');
            })->leftJoin('dic_schools', function ($q) {
                $q->on('users.u_school_id', '=', 'dic_schools.t_id');
            });

            if ($school) {
                $query = $query->where('users.u_school_id', '=', $user->u_school_id);
            }
            if (!empty($user_contact)) {
                if ($profession) {
                    $query = $query->where('users_contact_peoples.u_prof', '=', $user_contact->u_prof);
                }
                if ($entry_year) {
                    $query = $query->where('users_contact_peoples.u_entry_year', '=', $user_contact->u_entry_year);
                }
            }
            if ($gender) {
                $query = $query->where('users.u_sex', '=', $gender);
            }
            if ($key) {
                $query = $query->where('users.u_name', 'LIKE', '%'.$key.'%')->orWhere('users.u_nickname', 'LIKE', '%'.$key.'%')->orWhere('dic_schools.t_name', 'LIKE', '%'.$key.'%');
            }
            $list = $query->groupBy('users.u_id')->paginate($per_page);
            $data = [];
            foreach ($list as $key => $follow) {
                $data[] = $follow->follower->showInList();
            }
            $re = Tools::reTrue('获取粉丝成功', $data, $list);
        } catch (Exception $e) {
            $re = Tools::reFalse($e->getCode(), '获取粉丝失败:'.$e->getMessage());
        }
        return Response::json($re);
    }

    public function getBoothCate()
    {
        $data = Product::getProductCate(1);
        $re = Tools::reTrue('获取分类成功', $data);
        return Response::json($re);
    }

    public function getProductCate()
    {
        $data = Product::getProductCate(1);
        $re = Tools::reTrue('获取分类成功', $data);
        return Response::json($re);
    }

    public function getFleaCate()
    {
        $data = Product::getProductCate(2);
        $re = Tools::reTrue('获取分类成功', $data);
        return Response::json($re);
    }
}
