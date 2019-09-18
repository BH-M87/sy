<?php
use yii\widgets\LinkPager;
use yii\helpers\Html;
use yii\grid\GridView;
?>
<!-- Main content -->
<section class="content">
    <!-- Default box -->
    <div class="box-footer">
        <a href="/supplier/add"><button type="button"  class="btn btn-primary">添加供应商</button></a>

    </div>
    <div class="box">
        <?=
        GridView::widget([
            'dataProvider' => $dataProvider,
            'layout'=>"{items}\n{pager}",
            'columns' => [
                'id',
                [
                    'attribute' => 'name',
                    'header' => '供应商名称'
                ],
                [
                    'attribute' => 'contactor',
                    'header' => '联系人'
                ],
                [
                    'attribute' => 'mobile',
                    'header' => '联系电话'
                ],
                [
                    'attribute' => 'type',
                    'header' => '供应商类型',
                    'value' => function($model) {
                     return $model->type == 1 ? "道闸" : "门禁";
                    }
                ],
                [
                    'attribute' => 'supplier_name',
                    'header' => '供应商标识'
                ],
                [
                    'attribute' => 'created_at',
                    'header' => '添加时间',
                    'format' =>  ['date', 'php:Y-m-d H:i:s'],
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'header' => '操作',
                    'template' => '{update}&nbsp;&nbsp;{delete}',
                    'headerOptions' => ['width' => '128', 'class' => 'padding-left-5px',],
                    'contentOptions' => ['class' => 'padding-left-5px'],
                    'buttons' => [
                        'update' => function ($url, $model, $key) {
                            return Html::a('编辑', $url, ['title' => '编辑'] );
                        },
                        'delete' => function ($url, $model, $key) {
                            return Html::a('删除', $url, ['title' => '删除', 'data' => ['confirm' => '你确定要删除此供应商吗？']]);
                        },
                    ]
                ],
            ],
        ]); ?>
    </div>
    <!-- /.box -->
</section>
<!-- /.content -->