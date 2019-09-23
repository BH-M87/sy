<?php
use yii\widgets\LinkPager;
use yii\helpers\Html;
use yii\grid\GridView;
?>
<!-- Main content -->
<section class="content">
    <!-- Default box -->
    <div class="box-footer">
        <a href="/company-h5/add"><button type="button"  class="btn btn-primary">添加公司</button></a>
        <a href="/company-h5/bound"><button type="button"  class="btn btn-primary">绑定小区</button></a>
    </div>
    <div class="box">
        <?=
        GridView::widget([
            'dataProvider' => $dataProvider,
            'layout'=>"{items}\n{pager}",
            'columns' => [
                'id',
                [
                    'attribute' => 'enterprise_name',
                    'header' => '公司名称'
                ],
                [
                    'attribute' => 'link_name',
                    'header' => '联系人'
                ],
                [
                    'attribute' => 'link_mobile',
                    'header' => '联系电话'
                ],
//                [
//                    'attribute' => 'status',
//                    'header' => '状态',
//                    'value' => function($model) {
//                     return $model->status == 2 ? "已签约" : Html::a('签约', $model->nonce ? \Yii::$app->params['auth_to_us_url'] . "&nonce=" . $model->nonce : '', ['title' => '签约']);
//                    }
//                ],
                [
                    'attribute' => 'created_at',
                    'header' => '添加时间',
                    'format' =>  ['date', 'php:Y-m-d H:i:s'],
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'header' => '状态',
                    'template' => '{update}',
                    'headerOptions' => ['width' => '128', 'class' => 'padding-left-5px',],
                    'contentOptions' => ['class' => 'padding-left-5px'],
                    'buttons' => [
                        'update' => function ($url, $model, $key) {
                            return $model->status == 2 ? "已签约" : Html::a('去签约', $model->nonce ? \Yii::$app->params['auth_to_us_url'] . "&nonce=" . $model->nonce : '', ['title' => '签约']);

                        },
                    ]
                ],
            ],
        ]); ?>
    </div>
    <!-- /.box -->
</section>
<!-- /.content -->