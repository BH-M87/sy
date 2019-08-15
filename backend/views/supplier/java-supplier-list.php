<?php
use yii\widgets\LinkPager;
use yii\helpers\Html;
use yii\grid\GridView;
?>
<!-- Main content -->
<section class="content">
    <!-- Default box -->
    <div class="box">
        <?=
        GridView::widget([
            'dataProvider' => $dataProvider,
            'layout'=>"{items}\n{pager}",
            'columns' => [
                'id',
                [
                    'attribute' => 'productSn',
                    'header' => '产品SN'
                ],
                [
                    'attribute' => 'productName',
                    'header' => '产品名称'
                ],
                [
                    'attribute' => 'functionFace',
                    'header' => '人脸功能',
                    'value' => function($model) {
                        return !empty($model['functionFace']) ? "支持" : "不支持";
                    }
                ],
                [
                    'attribute' => 'functionBluetooth',
                    'header' => '蓝牙',
                    'value' => function($model) {
                        return  !empty($model['functionBluetooth']) ? "支持" : "不支持";
                    }
                ],
                [
                    'attribute' => 'functionCode',
                    'header' => '二维码',
                    'value' => function($model) {
                        return !empty($model['functionCode']) ? "支持" : "不支持";
                    }
                ],
                [
                    'attribute' => 'functionPassword',
                    'header' => '密码',
                    'value' => function($model) {
                        return !empty($model['functionPassword']) ? "支持" : "不支持";
                    }
                ],
                [
                    'attribute' => 'functionCard',
                    'header' => '门卡',
                    'value' => function($model) {
                        return !empty($model['functionCard']) ? "支持" : "不支持";
                    }
                ],
                [
                    'attribute' => 'type',
                    'header' => '供应商类型',
                    'value' => function($model) {
                     return $model['deviceType'] == 1 ? "道闸" : "门禁";
                    }
                ]
            ],
        ]); ?>
    </div>
    <!-- /.box -->
</section>
<!-- /.content -->