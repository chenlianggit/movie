<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
    <style media="screen">
        body, ul, li, p, img, div, dt, dl, dd {margin:0;padding:0;}
        html{overflow-y:scroll;}
        body{background:#fff;font:28px "微软雅黑","宋体","Arial";color:#000;}
        .page{ font-family: '微软雅黑';
            background-color: #000;
            width:750px;
            min-height:1334px;
            overflow: hidden;
            /*background-image: url("img.png");*/
            background-size:cover;
            background-position: center center;
            background-repeat: no-repeat;


        }

        .share-box{width:750px;height:1334px;overflow:hidden;}
        .share-code{
            background:#fff;
            width:400px;
            height:480px;
            margin:700px auto 0 auto;
            text-align:center;
        }
        .share-vCode{width:355px;height:355px;margin-top: 30px}
        .share-state{color:#BABABA;font-size:24px;margin-top: 20px}
    </style>
</head>
<body>
<div class="page">
    <div class="share-box">
        <div class="share-code">
            <img src="<?php echo $_GET['code']; ?>" align="center" alt="" class="share-vCode">
            <div class="share-state">长按识别二维码</div>
        </div>


    </div>
</div>
</body>
</html>