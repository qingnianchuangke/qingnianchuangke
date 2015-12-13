var count = 0;
$("#left_menu").click(
    function () {
            $("#nav").removeClass("nav-left-0");
            $("#nav").removeClass("nav-right-to-left");
            $("#left").removeClass("left-content");
            $("#right").removeClass("right-content");
            $("#left").removeClass("left-to-left");
            $("#right").removeClass("right-to-left");
            $("#nav").removeClass("nav-up-to-down");
            $("#rightMask").removeClass("rightMask-0");
            $("#rightMask").removeClass("rightMask-to-right");
            $("#rightMask").css("display", "block");
            $("#rightMask").addClass("rightMask-to-right");
            $("#nav").addClass("nav-left-to-right");
            $("#left").addClass("left-to-right");
            $("#right").addClass("right-to-right");
            $("#rightMask").css("display", "block");
            $("#right").css("overflow", "hidden");
            $("#nav").css("display", "none");
        }
);
$("#rightMask,#leftClose").click(function () {
        $("#nav").removeClass("nav-left-0");
        $("#nav").removeClass("nav-left-to-right");
        $("#left").removeClass("left-content");
        $("#right").removeClass("right-content");
        $("#left").removeClass("left-to-right");
        $("#right").removeClass("right-to-right");
        $("#nav").removeClass("nav-up-to-down");
        $("#nav").addClass("nav-right-to-left");
        $("#left").addClass("left-to-left");
        $("#right").addClass("right-to-left");
        $("#rightMask").css("display", "none");
        $("#right").css("overflow", "visible");
        $("#nav").css("display", "block");
});

function moveMask(){
    $("#nav").removeClass("nav-left-0");
    $("#nav").removeClass("nav-left-to-right");
    $("#left").removeClass("left-content");
    $("#right").removeClass("right-content");
    $("#left").removeClass("left-to-right");
    $("#right").removeClass("right-to-right");
    $("#nav").removeClass("nav-up-to-down");
    $("#left").addClass("left-content");
    $("#right").addClass("right-content");
    $("#rightMask").css("display", "none");
    $("#right").css("overflow", "visible");
    $("#nav").css("display", "block");
}

$(".fun-list ul li div").mouseenter(function () {
    var this_id = $(this).attr("id");
    var mask_id = "#mask" + this_id;
    $(mask_id).removeClass("up-to-down");
    $(mask_id).addClass("down-to-up");
});
$(".fun-list ul li div").mouseleave(function () {
    var this_id = $(this).attr("id");
    var mask_id = "#mask" + this_id;
    $(mask_id).removeClass("down-to-up");
    $(mask_id).addClass("up-to-down");
});

//鼠标滚轮事件onmousewheel处理
var scrollFunc = function(e) {
    var direct = 0;
    e = e || window.event;
    var t1 = document.getElementById("wheelDelta");
    var t2 = document.getElementById("detail");
    if (e.wheelDelta) {
        if (e.wheelDelta > 0) {
            $("#nav").removeClass("nav-00");
            $("#nav").removeClass("nav-70");
            $("#nav").addClass("nav-up-to-down");
        }
        else if (e.wheelDelta < 0) {
            $("#nav").removeClass("nav-00");
            $("#nav").removeClass("nav-up-to-down");
            $("#nav").addClass("nav-70");
        }
    } else if (e.detail) {  
        if (e.detail > 0) {
            $("#nav").removeClass("nav-00");
            $("#nav").removeClass("nav-up-to-down");
            $("#nav").addClass("nav-70");
        }
        else if (e.detail < 0) {          
            $("#nav").removeClass("nav-00");
            $("#nav").removeClass("nav-70");
            $("#nav").addClass("nav-up-to-down");
        }
    }
};
if (document.addEventListener) {
    document.addEventListener('DOMMouseScroll', scrollFunc, false);
}
window.onmousewheel = document.onmousewheel = scrollFunc;


//---------轮播图片、左侧栏----------------
function secondShow(this_id) {
    if (this_id == "01") {
        $("#secondFont").css("background", "#070815");
        $("#secondFont").css("color", "gray");
        $("#secondTitel").html("青年创---全网首家大学生综合类服务平台");
        $("#secondContent").html("<p>青年创以移动互联网作为服务平台，通过资源整合，为大学生提供在校创业、生活、娱乐、兼职找工作等服务；产品体系下搭建：自助创业、校园乐购、娱乐社交、兼职／招聘等核心板块。青年创希望通过自身平台搭建，提供一个满足在校大学生需求的综合类服务应用平台。</p>");
    }
    else if (this_id == "02") {
        $("#secondFont").css("background", "#070815");
        $("#secondFont").css("color", "gray");
        $("#secondContent").html("<p>2015年6月3日，智联招聘发布了《2015年应届毕业生就业力调研报告》，报告称，本次报告调研对象覆盖全国各地区各级高校的各专业学生，参与调查的2015届应届毕业生中，七成选择就业，比例略有下降；选择创业的比例为6.3%，与4014年的3.2%相比，比例上升明显，应届大学生创业热潮兴起，而创业成功率全国平均水平仅为2%；应届毕业生缺乏工作经验，人脉关系薄弱，对市场的把握度低，对运营管理、财务管理缺乏经验，是造成首次创业成功率低的主要原因；</p>"+
        "<p>【青年创】希望通过自身平台的搭建，帮助大学生通过在校微型创业积累创业经验，学习运营管理及财务管理，为日后创业打好基础；【青年创】以校内电商平台的形势存在，针对有创业需求的在校生，提供启动资金资助，帮助其在平台开店。平台以参股投资形式与创业者进行合作，不参与任何管理。创业者计划好创业项目以后，通过APP直接在平台上申请所需创业费用（暂定5000-15000元），平台获得20%的原始股份，合作时间为3个月，占有创业者股份时间也仅为3个月，3个月以后平台收回资金，并获得创业者经营利润的20%，此后平台将不收取任何创业者经营费用；</p>"+
       "<p>【青年创】目前属于启动阶段，现针对全国范围高校招募创客，限额200名，在经过项目筛选后及团队面试后，平台将以直接支助创业资金，不参与分红的形式与创业者进行合作，项目报名时间截止日为2015-12-31日!</p>"+
       "<p>只要你是在校大学生，并且有创业的梦想与激情，快来我们一起创业！</p>");
    }
    else if (this_id == "03") {
        $("#secondFont").css("background", "#070815");
        $("#secondFont").css("color", "gray");
        $("#secondTitel").html("【青年创】官方微信平台开通");
        $("#secondContent").html("【年创】官方微信平台为青年创客公司运营，不定时发布更新各类官方信息及校园兼职招聘资讯。旨在跨平台全方位服务于在校大学生。");
    }
    else if (this_id == "04") {
        $("#secondFont").css("background", "none");
        $("#secondFont").css("color", "black");
        $("#secondTitel").html("");
        $("#secondContent").html(faq);
    }
    else if (this_id == "05") {
        $("#secondFont").css("background", "none");
        $("#secondFont").css("color", "black");
        $("#secondTitel").html("");
        $("#secondContent").html(law);
    }

    var _id = "./images/bg" + this_id +this_id+ ".jpg";
    $("#secondPhoto").attr("src", _id);
    $(".js-flickity,.fun,.union,.develop").css("display", "none");
    $(".second-content").css("display", "block");
    var fontHeight = $("#secondFont").height();
    $(".link").css("top", 570 + fontHeight + "px");
    $(".footer").css("top", 570 + fontHeight + 180 + "px");
};
$("#about,#one,#aboutUs").click(function () {
    moveMask();
    secondShow("01");
});
$("#recruit,#two,#recruit2").click(function () {
    moveMask();
    secondShow("02");
});
$("#three").click(function () {
    moveMask();
    secondShow("03");
});
$("#faq,#faq2").click(function () {
    moveMask();
    secondShow("04");
});
$("#law,#aboutLaw").click(function () {
    moveMask();
    secondShow("05");
});
//---------"首页"、"功能"、"联盟"点击事件----------------
$("#syLink,#syLink2").click(function () {
    $(".js-flickity,.fun,.union,.develop").css("display", "block");
    $(".link").css("top", imgHeight + 800 + 900 + 860 + "px");
    $(".footer").css("top", imgHeight + 800 + 900 + 860 + 180 + "px");
    $(".second-content").css("display", "none");
    $('html,body').animate({ scrollTop: 0 }, 800);
});
$("#funLink,#funLink2").click(function () {
    $(".js-flickity,.fun,.union,.develop").css("display", "block");
    $(".second-content").css("display", "none");
    $('html,body').animate({ scrollTop: $('.fun').offset().top - 42 }, 800);
    $(".link").css("top", imgHeight + 800 + 900 + 860 + "px");
    $(".footer").css("top", imgHeight + 800 + 900 + 860 + 180 + "px");
});
$("#unionLink,#unionLink2").click(function () {
    $(".js-flickity,.fun,.union,.develop").css("display", "block");
    $(".link").css("top", imgHeight + 800 + 900 + 860 + "px");
    $(".footer").css("top", imgHeight + 800 + 900 + 860 + 180 + "px");
    $(".second-content").css("display", "none");
    $('html,body').animate({ scrollTop: $('.union').offset().top-42 }, 800);
});

//-----------功能分类点击事件---------------
$(".fun-list ul li div").click(function () {
    var _id = $(this).attr("id");
    var str = "./images/" + _id + ".png";
    $(".fun-list").css("display", "none");
    $(".fun-content").css("display", "block");
    $("#funContent").attr("src", str);
    $(".union").css("top", imgHeight + 850 + "px");
    $(".develop").css("top", imgHeight + 850 + 900 + "px");
    $(".link").css("top", imgHeight + 850 + 900 + 770 + "px");
    $(".footer").css("top", imgHeight + 850 + 900 + 770 + 180 + "px");
});
$("#funClose").click(function () {
    $(".fun-content").css("display", "none");
    $(".fun-list").css("display", "block");
    $(".union").css("top", imgHeight + 800 + "px");
    $(".develop").css("top", imgHeight + 800 + 900 + "px");
    $(".link").css("top", imgHeight + 800 + 900 + 770 + "px");
    $(".footer").css("top", imgHeight + 800 + 900 + 770 + 180 + "px");
});
$("#funLeft").click(function() {
    var a = $("#funContent").attr("src"),b;
    var arr = a.split(".png");
    b = arr[0].split("./images/");
    if (b[1] <=1) {
        b[1] = 4;
        $("#funContent").attr("src","./images/4.png");
    }
    else if (b[1] >1) {
        --b[1];
        a = "./images/" + b[1] + ".png";
        $("#funContent").attr("src", a);
    }
});
$("#funRight").click(function () {
    var a = $("#funContent").attr("src"), b;
    var arr = a.split(".png");
    b= arr[0].split("./images/");
    if (b[1] >= 4) {
        b[1] = 1;
        $("#funContent").attr("src", "./images/1.png");
    }
    else if (b[1] <4) {
        ++b[1];
        a = "./images/" + b[1] + ".png";
        $("#funContent").attr("src", a);
    }
});
//-----------联盟点击事件---------------
$("#unionWhite").mousedown(function (event) {
    var x, y, value;
    x = this.offsetLeft;
    value = event.clientX - this.offsetLeft;
    if ((0 <= value) && (value < 75)) {
        $("#drag").css("margin-left", "-245px");
        $("#photo").attr("src", "./images/change1.png");
        $(".union-ul li").css("color", "gray");
        $("#cy").css("color", "black");
        $("#funFont").text("我们为每一位校园创客提供最完备的创业支持");
    }
    else if ((75 <= value) && (value < 225)) {
        $("#drag").css("margin-left", "-105px");
        $("#photo").attr("src", "./images/change2.png");
        $(".union-ul li").css("color", "gray");
        $("#fw").css("color", "black");
        $("#funFont").text("我们用100%的信任坚定你100%的创业信念");
    }
    else if ((225 <= value) && (value < 375)) {
        $("#drag").css("margin-left", "35px");
        $("#photo").attr("src", "./images/change3.png");
        $(".union-ul li").css("color", "gray");
        $("#cl").css("color", "black");
        $("#funFont").text("我们的初衷就是把创业的乐趣分享给更多人");
    }
    else if ((375 <= value) && (value < 525)) {
        $("#drag").css("margin-left", "175px");
        $("#photo").attr("src", "./images/change4.png");
        $(".union-ul li").css("color", "gray");
        $("#tz").css("color", "black");
        $("#funFont").text("我们是一群对未来有美好憧憬的理想主义者");
    }
});
var imgHeight;
function winResize() {
    var broWidth;
    location.reload(true);
    broWidth = document.body.clientWidth;
    imgHeight = 1135 * broWidth / 2396 ;
    $(".js-flickity").css("height", imgHeight + "px");
    $(".fun").css("top", imgHeight);
    $(".union").css("top", imgHeight + 800 + "px");
    $(".develop").css("top", imgHeight + 800 + 900 + "px");
    $(".link").css("top", imgHeight + 800 + 900 + 770 + "px");
    $(".footer").css("top", imgHeight + 800 + 900 + 770 + 180 + "px");
}