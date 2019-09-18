<?php
use yii\widgets\LinkPager;
use yii\helpers\Html;
use yii\grid\GridView;
?>
<!-- Main content -->
<section class="content">
    <!-- Default box -->
    <div class="box-footer">
        <a href="/backend/add"><button type="button"  class="btn btn-primary">添加企业配置信息</button></a>

    </div>
    <div class="box">
        <?=
        GridView::widget([
            'dataProvider' => $dataProvider,
            'layout'=>"{items}\n{pager}",
            'columns' => [
                'id',
                [
                    'attribute' => 'corp_name',
                    'header' => '企业名称'
                ],
                [
                    'attribute' => 'corp_id',
                    'header' => '企业corp_id'
                ],
                [
                    'attribute' => 'company_id',
                    'header' => '关联物业公司ID'
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'header' => '操作',
                    'template' => '{delete}',
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