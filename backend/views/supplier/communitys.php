<?php
use yii\widgets\LinkPager;
use yii\helpers\Html;
use yii\grid\GridView;
?>
<!-- Select2 -->
<link rel="stylesheet" href="/bower_components/select2/dist/css/select2.min.css">
<!-- Google Font -->
<link rel="stylesheet"
      href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">

<!-- jQuery 3 -->
<script src="/bower_components/jquery/dist/jquery.min.js"></script>
<!-- Select2 -->
<script src="/bower_components/select2/dist/js/select2.full.min.js"></script>
<!-- Main content -->
<section class="content">
    <!-- Default box -->
    <div class="box-footer">
        <?php echo $this->render('_search', ['suppliers' => $suppliers, 'communitys' => $communitys,
            'supplierName' => $supplierName, 'communityName' => $communityName, 'authCode' => $authCode,
            'supplierType' => $supplierType]); ?>
    </div>
    <div class="box">
        <?=
        GridView::widget([
            'dataProvider' => $dataProvider,
            'layout'=>"{items}\n{pager}",
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                [
                    'attribute' => 'supplier_name',
                    'header' => '供应商名称',
                ],
                [
                    'attribute' => 'community_name',
                    'header' => '小区名称',
                ],
                [
                    'attribute' => 'auth_code',
                    'header' => '授权码'
                ],
                [
                    'attribute' => 'auth_at',
                    'header' => '授权时间',
                    'format' =>  ['date', 'php:Y-m-d H:i:s'],
                ],
                [
                    'attribute' => 'supplier_type',
                    'header' => '接入类型',
                    'value' => function($model) {
                     return $model->supplier_type == 1 ? "道闸" : "门禁";
                    }
                ],
                [
                    'attribute' => 'created_at',
                    'header' => '添加时间',
                    'format' =>  ['date', 'php:Y-m-d H:i:s'],
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'header' => '操作',
                    'template' => '{comm-init}&nbsp;&nbsp;{build-init}<br>{room-init}&nbsp;&nbsp;{user-init}<br>{delete}&nbsp;&nbsp;{register-url}',
                    'headerOptions' => ['width' => '128', 'class' => 'padding-left-5px',],
                    'contentOptions' => ['class' => 'padding-left-5px'],
                    'buttons' => [
                        'register-url' => function ($url, $model, $key) {
                            return $model->interface_type == 2 ?  Html::a('推送配置', '/supplier/push-register?id='.$model->id,
                                ['title' => '推送配置']) : '';
                        },
                        'delete' => function ($url, $model, $key) {
                            return Html::a('删除', '/supplier/community-delete?id='.$model->id,
                                ['title' => '删除', 'data' => ['confirm' => '你确定要删除此小区吗？']]);
                        },
                        'comm-init' => function ($url, $model, $key) {
                            return Html::a('初始化小区', '/supplier/community-init?id='.$model->id,
                                ['title' => '初始化小区']);
                        },
                        'build-init' => function ($url, $model, $key) {
                            return Html::a('楼宇推送', '/supplier/build-init?id='.$model->id,
                                ['title' => '楼宇推送']);
                        },
                        'room-init' => function ($url, $model, $key) {
                            return Html::a('房屋推送', '/supplier/room-init?id='.$model->id,
                                ['title' => '房屋推送']);
                        },
                        'user-init' => function ($url, $model, $key) {
                            return Html::a('住户推送', '/supplier/user-init?id='.$model->id,
                                ['title' => '住户推送']);
                        },
                    ]
                ],
            ],
        ]); ?>
    </div>
    <div class="control-sidebar-bg"></div>
    <!-- /.box -->
</section>
<!-- /.content -->