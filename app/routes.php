<?php
// play ground

//
Route::post('/v1', 'HomeController@v1');
Route::get('/v1', 'HomeController@v1');
Route::get('/', function () {
    $url = Config::get('app.url');
    $url = $url.'www/index.html';
    return Redirect::to($url);
});
Route::get('/banner/1', 'HomeController@banner1');
Route::get('/banner/2', 'HomeController@banner2');
Route::get('about', 'HomeController@about');
Route::get('/test', 'HomeController@test');

/* handling files */
Route::pattern('any', '(.*)');
Route::get('css/{any}', 'MiscFileController@getCSS');
Route::get('js/{any}', 'MiscFileController@getJS');
Route::get('images/{any}', 'MiscFileController@getImg');
Route::get('addons/{any}', 'MiscFileController@getAddOn');

/*********** FILTER ***********/
Route::filter('office', function () {
    $path = Request::path();
    if ($path != 'office/login') {
        if (!SysUser::chkLogin()) {
            $re = Tools::reFalse(10003, '请先登录');
            return Response::json($re);
        }
    }
});
Route::when('office/*', 'office');
/*********** FILTER ***********/

/*********** IMG ***********/
Route::post('img', 'ImgController@postImg');
/*********** IMG ***********/


/*********** API ***********/


/* V1 */
Route::group(['domain' => Config::get('app.subdomain.api')], function () {

    /* APP START*/
    Route::get('app/config', 'AppController@getConfig');
    Route::get('app/download', 'AppController@getDownloadLink');

    Route::post('v0/app/feedback', 'AppController@postFeedback');
    /* APP END*/

    /* ME START*/
    Route::get('v0/user/me', 'MeController@me');
    Route::get('v0/user/me/notification', 'MeNotificationController@listNots');
    Route::get('v0/user/me/notification/{id}', 'MeNotificationController@getNot')->where('id', '[0-9]+');
    Route::get('v0/user/me/notification/count', 'MeNotificationController@countNots');
    Route::get('v0/user/me/posts', 'MeController@myPosts');
    Route::get('v0/user/me/reply', 'MeController@myReply');
    Route::get('v0/user/me/praise', 'MeController@myPraise');
    Route::post('v0/user/me/resetpass', 'MeController@resetPass');
    Route::get('v0/user/me/followers', 'MeController@myFollowers');
    Route::get('v0/user/me/followings', 'MeController@myFollowings');
    Route::delete('v0/user/me/homeimg', 'MeController@delHomeImg');
    Route::put('v0/user/me/headimg', 'MeController@putHeadImg');
    Route::post('v0/user/me/import/bind/mobie', 'UserController@bindMobile');
    /* ME END*/

    /* ME PROFILE START*/
    Route::get('v0/user/me/profile/check', 'MeProfileController@profileCheck');
    Route::post('v0/user/me/profile/bank', 'MeProfileController@postBank');
    Route::get('v0/user/me/profile/bank', 'MeProfileController@getBank');
    Route::post('v0/user/me/profile/base', 'MeProfileController@postUserBase');
    Route::put('v0/user/me/profile/base', 'MeProfileController@putUserBase');
    Route::get('v0/user/me/profile/base', 'MeProfileController@getUserBase');
    
    Route::post('v0/user/me/profile/detail', 'MeController@postDetail');
    Route::post('v0/user/me/profile/contact', 'MeController@postContact');
    Route::get('v0/user/me/profile/detail', 'MeController@getDetail');
    Route::get('v0/user/me/profile/contact', 'MeController@getContact');
    /* ME PROFILE END*/

    /* ME FAVORITE START*/
    Route::get('v0/user/me/favorite/booth', 'MeController@listFavoriteBooth');
    Route::get('v0/user/me/favorite/user', 'MeController@listFavoriteUser');
    Route::get('v0/user/me/favorite/product', 'MeController@listFavoriteProduct');
    Route::get('v0/user/me/favorite/crowd', 'MeController@listFavoriteCrowd');
    /* ME FAVORITE END*/

    /* ME MARKET START*/
    Route::post('v0/user/me/booth', 'MeController@postBooth');
    Route::get('v0/user/me/booth', 'MeController@listBooth');
    Route::get('v0/user/me/booth/{id}', 'MeBoothController@getBooth');
    Route::put('v0/user/me/booth/{id}/desc', 'MeBoothController@putBoothDesc');
    Route::put('v0/user/me/booth/{id}/status', 'MeController@putBoothStatus');
    Route::get('v0/user/me/booth/{id}/status', 'MeController@getBoothStatus');
    
    Route::post('v0/user/me/product', 'MeController@postProduct');
    Route::get('v0/user/me/product', 'MeController@getProducts');
    Route::get('v0/user/me/product/{id}', 'MeController@getProduct');
    Route::put('v0/user/me/product/{id}', 'MeController@putProduct');
    Route::put('v0/user/me/product/{id}/atop', 'MeProductController@putAtop');
    Route::get('v0/user/me/product/{id}/on', 'MeController@productOn');
    Route::delete('v0/user/me/product/{id}/img', 'MeController@delProductImg');
    Route::post('v0/user/me/product/sort', 'MeController@updateProductSort');
    Route::post('v0/user/me/product/discount', 'MeController@updateProductDiscount');

    Route::post('v0/user/me/crowd', 'MeCrowdFundingController@postCrowdFunding');
    Route::put('v0/user/me/crowd/{id}', 'MeCrowdFundingController@putCrowdFunding');
    Route::delete('v0/user/me/crowd/{id}', 'MeCrowdFundingController@delCrowdFunding');
    Route::get('v0/user/me/crowd/sell', 'MeCrowdFundingController@listSellCrowdFunding');
    Route::get('v0/user/me/crowd/buy', 'MeCrowdFundingController@listBuyCrowdFunding');

    Route::get('v0/user/me/flea', 'MeProductController@listFlea');
    Route::post('v0/user/me/flea', 'MeProductController@postFlea');
    Route::put('v0/user/me/flea/{id}', 'MeProductController@putFlea');
    Route::get('v0/user/me/flea/{id}', 'MeProductController@getFlea');
    Route::delete('v0/user/me/flea/{id}', 'MeProductController@delFlea');
    
    Route::get('v0/user/me/orders', 'MeController@listOrders');
    Route::get('v0/user/me/order/{id}', 'MeController@getOrder');
    Route::put('v0/user/me/order/{id}/cancel', 'MeController@cancelOrder');
    Route::get('v0/user/me/orders/count', 'MeController@countOrders');
    Route::post('v0/user/me/order/deliver', 'MeController@deliverOrder');
    Route::post('v0/user/me/order/confirm', 'MeController@confirmOrder');
    
    Route::get('v0/user/me/sells', 'MeController@listSellOrders');
    Route::get('v0/user/me/sells/{id}', 'MeController@getSellOrder');
    Route::put('v0/user/me/sells/{id}/cancel', 'MeController@cancelSellOrder');
    Route::get('v0/user/me/sells/count', 'MeController@countSellOrders');
    
    Route::get('v0/user/me/praise/promo', 'MeController@listPraisePromo');
    Route::get('v0/user/me/following/booth', 'MeController@listFollowingBooth');
    
    Route::get('v0/user/me/wallet', 'MeController@showWallet');
    Route::post('v0/user/me/wallet/draw', 'MeController@postWalletDraw');
    Route::get('v0/user/me/wallet/draw', 'MeController@listWalletDraw');
    Route::get('v0/user/me/wallet/draw/{id}', 'MeController@getWalletDraw');
    
    Route::post('v0/user/me/payment/wechat', 'MeController@postPaymentWechat');
    Route::post('v0/user/me/payment/alipay', 'MeController@postPaymentAlipay');
    Route::post('v0/user/me/payment/bank', 'MeController@postPaymentBank');
    
    Route::get('v0/user/me/financial/report', 'MeController@financialReport');
    Route::post('v0/user/me/financial/report/confirm', 'MeController@confirmFinancialReport');
    /* ME MARKET END*/

    /* ME FRIEND START*/
    Route::get('v0/user/me/friend', 'MeFriendController@index');
    Route::get('v0/user/me/friend/remove', 'MeFriendController@remove');
    Route::get('v0/user/me/friend/confirm', 'MeFriendController@confirm');
    Route::post('v0/user/me/friend/invite', 'MeFriendController@invite');
    Route::get('v0/user/me/friend/invite', 'MeFriendController@indexInvite');
    Route::delete('v0/user/me/friend/invite/{id}', 'MeFriendController@removeInvite');
    Route::get('v0/user/me/friend/check', 'MeFriendController@check');
    /* ME FRIEND END*/

    /* ME AUCTION START*/
    Route::get('v0/user/me/auction', 'MeAuctionController@listAuctions');
    Route::get('v0/user/me/auction/{id}/bid', 'MeAuctionController@listBids');
    Route::post('v0/user/me/auction/{id}/order', 'MeAuctionController@postOrder');
    /* ME AUCTION END*/

    /* USER START*/
    Route::get('v0/user/search', 'UserController@search');
    Route::get('v0/user/{id}/follow', 'UserController@follow');
    Route::get('v0/user/{id}/followers', 'UserController@followers');
    Route::get('v0/user/{id}/followings', 'UserController@followings');
    Route::post('v0/user/{id}/praise', 'UserController@postPraise');
    Route::post('v0/user/{id}/favorite', 'UserController@postFavorite');
    Route::get('v0/user/type', 'UserController@getUserType');
    Route::resource('v0/user', 'UserController');
    Route::post('v0/user/import/login', 'UserController@importLogin');
    /* USER END*/

    /* POST START*/
    Route::resource('v0/post', 'PostController');
    Route::get('v0/post/{id}/praise', 'PostController@praise');
    Route::delete('v0/post/reply/{id}', 'PostController@disableReply');
    /* POST END*/

    /* IM START*/
    Route::get('v0/im/user/{id}', 'ImController@getUser');
    Route::post('v0/im/user', 'ImController@listUser');
    /* IM END*/

    /* TRADE START*/
    Route::resource('v0/trade', 'TradeController');
    /* TRADE END*/

    /* MAKER START*/
    Route::get('v0/market/cate/booth', 'MarketController@getBoothCate');
    Route::get('v0/market/cate/product', 'MarketController@getProductCate');
    Route::get('v0/market/cate/flea', 'MarketController@getFleaCate');
    Route::get('v0/market/hot', 'MarketController@hot');
    Route::get('v0/market/flea', 'MarketController@flea');
    Route::get('v0/market/convenient', 'MarketController@convenient');
    Route::get('v0/market/maker', 'MarketController@maker');
    Route::get('v0/market/booth', 'MarketController@listBooth');
    Route::get('v0/market/booth/{id}', 'MarketController@getBooth');
    Route::post('v0/market/booth/{id}/follow', 'MarketController@postBoothFollow');
    Route::get('v0/market/booth/{id}/follow', 'MarketController@listBoothFollow');
    Route::get('v0/market/product', 'MarketController@listProduct');
    Route::get('v0/market/product/{id}', 'MarketController@getProduct');
    Route::post('v0/market/product/{id}/reply', 'MarketController@postProductReply');
    Route::post('v0/market/promotion/{id}/praise', 'MarketController@postPromoPraise');
    Route::put('v0/market/promotion/{id}/push', 'MeProductController@pushPromo');
    Route::get('v0/market/cart', 'MarketController@listCarts');
    Route::post('v0/market/cart', 'MarketController@postCart');
    Route::put('v0/market/cart/{id}', 'MarketController@putCart');
    Route::delete('v0/market/cart/{id}', 'MarketController@delCart');
    Route::post('v0/market/order', 'MarketController@postOrder');
    Route::post('v0/market/pay/wechat', 'PayController@wechatPayPreOrder');
    Route::post('v0/market/pay/cancel', 'PayController@payFailed');
    /* MAKER END*/

    /* ACTIVITIES START*/
    Route::get('v0/activity/{id}/follow', 'ActivitiesController@follow');
    Route::post('v0/activity/{id}/sign', 'ActivitiesController@sign');
    Route::get('v0/activity/{id}/signers', 'ActivitiesController@signers');
    Route::get('v0/activity/{id}/signer/{sid}/confirm', 'ActivitiesController@confirmSignedUser');
    Route::resource('v0/activity', 'ActivitiesController');
    /* ACTIVITIES END*/

    /* VERIFY START*/
    Route::get('v0/verify/code', 'VerificationController@getVCode');
    /* VERIFY END*/

    /* DATA DICTIONARY START*/
    Route::get('v0/dic/school', 'Api\DicController@getSchools');
    Route::get('v0/dic/province', 'Api\DicController@getProvinces');
    Route::get('v0/dic/city', 'Api\DicController@getCities');
    Route::get('v0/dic/bank', 'Api\DicController@getBanks');
    /* DATA DICTIONARY END*/

    /* LBS START*/
    Route::get('v0/lbs/nearby/user', 'LocationController@getNearbyUsers');
    Route::get('v0/lbs/nearby/store', 'LocationController@getNearbyStores');
    Route::get('v0/lbs/nearby/act', 'LocationController@getNearbyActivities');
    Route::post('v0/lbs/user/{id}', 'LocationController@updateUser');
    Route::post('v0/lbs/booth/{id}', 'LocationController@updateBooth');
    /* LBS END*/

    /* AD START*/
    Route::get('v0/ad/index/top', 'AdController@listIndexTop');
    /* AD END*/

    /* PAYMENT START*/
    Route::post('v0/pay/alipay/watchdog', 'PayController@callbackAlipay');
    Route::post('v0/pay/wechat/watchdog', 'PayController@callbackWechat');
    /* PAYMENT END*/

    /* CROWDINGFUDING START*/
    Route::get('v0/crowd', 'CrowdFundingController@listCrowdFunding');
    Route::get('v0/crowd/cate', 'CrowdFundingController@getCate');
    Route::get('v0/crowd/{id}', 'CrowdFundingController@getCrowdFunding');
    Route::post('v0/crowd/{id}/reply', 'CrowdFundingController@postReply');
    Route::post('v0/crowd/{id}/order', 'CrowdFundingController@postOrder');
    Route::post('v0/crowd/{id}/praise', 'CrowdFundingController@postPraise');
    Route::post('v0/crowd/{id}/favorite', 'CrowdFundingController@postFavorite');
    Route::get('v0/crowd/{id}/participates', 'CrowdFundingController@listParticipates');
    /* CROWDINGFUDING END*/

    /* PRODUCT START*/
    Route::get('v0/product/{id}', 'ProductController@getProduct');
    Route::post('v0/product/{id}/reply', 'ProductController@postReply');
    Route::post('v0/product/{id}/praise', 'ProductController@postPraise');
    Route::post('v0/product/{id}/favorite', 'ProductController@postFavorite');
    /* PRODUCT END*/

    /* AUCTION START*/
    Route::get('v0/auction/show', 'AuctionController@show');
    Route::get('v0/auction', 'AuctionController@listAuctions');
    Route::get('v0/auction/{id}', 'AuctionController@getAuction');
    Route::post('v0/auction/{id}/bid', 'AuctionController@bid');
    /* AUCTION END*/

    /* REPLY START*/
    Route::get('v0/reply/related', 'ReplyController@getRelatedRelpies');
    /* REPLY END*/

    /* BOOTH START*/
    Route::post('v0/booth/{id}/praise', 'BoothController@postPraise');
    Route::post('v0/booth/{id}/favorite', 'BoothController@postFavorite');
    /* BOOTH END*/

});

/*********** BACK ***********/

/* SYS USER START*/
Route::get('office/sys/user', 'OfficeSysUserController@listUsers');
Route::post('office/sys/user', 'OfficeSysUserController@postUser');
Route::put('office/sys/user/{id}', 'OfficeSysUserController@putUser');
Route::delete('office/sys/user/{id}', 'OfficeSysUserController@delUser');
Route::put('office/sys/user/{id}/enable', 'OfficeSysUserController@enableUser');

Route::get('office/sys/user/{id}/menu', 'OfficeMenuController@listUserMenu');
Route::get('office/sys/user/{id}/role', 'OfficeSysUserController@listUserRole');
Route::post('office/sys/user/{id}/role', 'OfficeSysUserController@addUserRole');
Route::delete('office/sys/user/{id}/role', 'OfficeSysUserController@delUserRole');

Route::get('office/sys/role/{id}/user', 'OfficeMenuController@listRoleUser');
Route::get('office/sys/role/{id}/menu', 'OfficeMenuController@listRoleMenu');
Route::post('office/sys/role/{id}/menu', 'OfficeMenuController@addRoleMenu');
Route::delete('office/sys/role/{id}/menu', 'OfficeMenuController@delRoleMenu');
Route::post('office/sys/role/{id}/user', 'OfficeMenuController@postRoleUser');

Route::get('office/sys/role', 'OfficeMenuController@listRole');
Route::post('office/sys/role', 'OfficeMenuController@postRole');
Route::put('office/sys/role/{id}', 'OfficeMenuController@putRole');
Route::delete('office/sys/role/{id}', 'OfficeMenuController@delRole');
Route::get('office/sys/menu', 'OfficeMenuController@listMenu');
Route::post('office/sys/menu', 'OfficeMenuController@postMenu');
Route::put('office/sys/menu/{id}', 'OfficeMenuController@putMenu');
Route::delete('office/sys/menu/{id}', 'OfficeMenuController@delMenu');
/* SYS USER END*/

/* OFFICE START*/
Route::post('office/login', 'OfficeController@login');
Route::get('office/logout', 'OfficeController@logout');
Route::get('office/menu', 'OfficeController@getMenu');
/* OFFICE END*/

/* WEB USER START*/
Route::get('office/user/profile', 'OfficeWebUserController@listUserProfiles');
Route::get('office/user/profile/{id}', 'OfficeWebUserController@getUserProfile');
Route::put('office/user/profile/{id}/censor/bank', 'OfficeWebUserController@censorUserProfileBank');
Route::put('office/user/profile/{id}/censor/base', 'OfficeWebUserController@censorUserProfileBase');
Route::put('office/user/profile/{id}/censor/identity', 'OfficeWebUserController@censorUserProfileIdentity');
Route::put('office/user/profile/{id}/censor/student', 'OfficeWebUserController@censorUserProfileStudent');
Route::put('office/user/profile/{id}/censor/club', 'OfficeWebUserController@censorUserProfileClub');
Route::put('office/user/{id}/enable', 'OfficeWebUserController@enable');
/* WEB USER END*/

/* BOOTH START*/
Route::get('office/booth', 'OfficeBoothController@listBooths');
Route::put('office/booth/{id}/censor', 'OfficeBoothController@censorBooth');
Route::get('office/booth/{id}/loans', 'OfficeBoothController@listLoans');
Route::put('office/booth/{id}/enable', 'OfficeBoothController@enable');
/* BOOTH END*/

/* PRODUCT START*/
Route::get('office/product', 'OfficeProductController@listProduct');
Route::get('office/product/{id}', 'OfficeProductController@getProduct');
Route::put('office/product/{id}/enable', 'OfficeProductController@enable');
/* PRODUCT END*/

/* FUND START*/
Route::get('office/fund/{id}/loan', 'OfficeFundController@listRepayments');
Route::get('office/fund/{id}/interview', 'OfficeFundController@interviewFund');
Route::get('office/fund/repaied', 'OfficeFundController@listRepaiedFund');
// Route::get('office/fund/');
/* FUND END*/

/* LOAN START*/
Route::get('office/loan/{id}/alloc', 'OfficeFundController@allocateRepayment');
Route::get('office/loan/{id}/retrive', 'OfficeFundController@retriveLoan');
/* LOAN END*/

/* DRAW START*/
Route::get('office/draw', 'OfficeDrawContoller@listDraw');
Route::get('office/draw/{id}', 'OfficeDrawContoller@getDraw');
Route::put('office/draw/{id}/confirm', 'OfficeDrawContoller@confirmDraw');
/* DRAW END*/

/* CROWDFUNDING START*/
Route::post('office/crowd', 'OfficeCrowdFundingController@postFunding');
Route::get('office/crowd', 'OfficeCrowdFundingController@listFunding');
Route::get('office/crowd/{id}', 'OfficeCrowdFundingController@getFunding');
Route::delete('office/crowd/{id}', 'OfficeCrowdFundingController@delFunding');
Route::put('office/crowd/{id}/censor', 'OfficeCrowdFundingController@censorFunding');
/* CROWDFUNDING END*/

/* DIC START*/
Route::get('office/dic/school', 'Api\DicController@getSchools');
Route::get('office/dic/province', 'Api\DicController@getProvinces');
Route::get('office/dic/city', 'Api\DicController@getCities');
Route::get('office/dic/bank', 'Api\DicController@getBanks');
/* DIC END*/

/* AD START*/
Route::get('office/ad', 'OfficeAdController@listAds');
Route::post('office/ad', 'OfficeAdController@postAd');
Route::get('office/ad/{id}', 'OfficeAdController@getAd');
Route::delete('office/ad/{id}', 'OfficeAdController@delAd');
/* AD END*/

/*********** PROTAL **********/

/* WECHAT START*/
Route::get('wechat/hengda/user', 'WechatController@getHengdaUsers');

Route::get('wechat/market/flea', 'MarketController@flea');
Route::get('wechat/product/{id}', 'ProductController@getProduct');
Route::get('wechat/market/hot', 'MarketController@hot');
Route::get('wechat/crowd', 'CrowdFundingController@listCrowdFunding');
Route::get('wechat/crowd/{id}', 'CrowdFundingController@getCrowdFunding');
Route::post('wechat/crowd/{id}/order', 'CrowdFundingController@postOrder');
Route::get('wechat/dic/school', 'Api\DicController@getSchoolWthCity');
Route::post('wechat/user/login', 'UserController@loginFromWechat');
Route::post('wechat/user', 'UserController@postUserFromWechat');
Route::get('wechat/verify/code', 'VerificationController@getVCode');

Route::post('wechat/user/me/resetpass', 'MeController@resetPassForWechat');
Route::get('wechat/user/me/profile/base', 'MeProfileController@getUserBase');
Route::get('wechat/user/me/crowd', 'MeCrowdFundingController@listSellCrowdFunding');
Route::post('wechat/user/me/crowd', 'MeCrowdFundingController@postCrowdFunding');
Route::delete('wechat/user/me/crowd/{id}', 'MeCrowdFundingController@delCrowdFunding');

Route::get('wechat/auction/show', 'AuctionController@show');
Route::post('wechat/auction/{id}/bid', 'AuctionController@bid');
Route::get('wechat/auction', 'AuctionController@listAuctions');
Route::get('wechat/auction/{id}', 'AuctionController@getAuction');

Route::get('wechat/sign', 'WechatController@getSign');
/* WECHAT END*/

/* SO CALLED EMERGENCY START*/
Route::get('so/called/emergency/countUser', 'EmergencyController@countUsers');
Route::get('so/called/emergency/rename', 'EmergencyController@rename');

// facker
Route::get('so/called/emergency/fake/user', 'EmergencyController@fakeUser');
Route::get('so/called/emergency/fake/fundingPurches/{id}', 'EmergencyController@fakeCrowdFundingPurches');
/* SO CALLED EMERGENCY END*/
