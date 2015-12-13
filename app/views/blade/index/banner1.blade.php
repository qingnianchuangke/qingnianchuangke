<!DOCTYPE html>
<html>
<head>
    <title>青年创</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <script src="http://libs.baidu.com/jquery/2.0.0/jquery.min.js"></script>
    <script type="text/javascript" src="/js/bannerlayout.js"></script>
    <style type="text/css">

    
    .bg {
        background-size: cover;
        background-image: url('http://qnck001.oss-cn-hangzhou.aliyuncs.com/banner/1_inner.jpg');
    }
    .btn {
        width: 142px;
        height: 30px;
        background-size: cover;
    }
    .share {
        background-image: url('http://qnck001.oss-cn-hangzhou.aliyuncs.com/banner/btn_share_click.png');
    }
    .download {
        background-image: url('http://qnck001.oss-cn-hangzhou.aliyuncs.com/banner/btn_download.png');
    }
    .left {
        float: left;
    }
    .right {
        float: right;
    }
    </style>
    <script type="text/javascript">
    $(document).ready(function (){
        $(document).on('click', '#btn_share', function () {
            javascript:appObject.shareOnAndroid(1);
        });
        var margin_top = 0.05;
        var height_ratio = 2.97;
        layout(margin_top, height_ratio);
    });
    </script>
</head>
<body>
    <div class="bg" id="bg"></div>
    <div class="menu" id="menu">
        <a href="{{$link}}" target="_blank"><div id="btn_download" class="download btn center"></div></a>
    </div>
</body>
</html>