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
        <?php echo $this->render('_search', ['company' => $company, 'company_name'=>$company_name,
            'agent_id' => $agent_id]); ?>
    </div>
    <div class="box">
        <?=
        GridView::widget([
            'dataProvider' => $dataProvider,
            'layout'=>"{items}\n{pager}",
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                [
                    'attribute' => 'corp_name',
                    'header' => '企业名称',
                    'headerOptions' => ['style'=>'max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;'],
                    'contentOptions' => ['style'=>'max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;'],
                ],
                [
                    'attribute' => 'corp_id',
                    'header' => '企业corp_id',
                    'headerOptions' => ['style'=>'max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;'],
                    'contentOptions' => ['style'=>'max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;'],
                ],
                [
                    'attribute' => 'agent_id',
                    'header' => '微应用id',
                ],
                [
                    'attribute' => 'app_key',
                    'header' => '微应用app_key',
                ],
                [
                    'attribute' => 'app_secret',
                    'header' => '微应用app_secret',
                    'headerOptions' => ['style'=>'max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;'],
                    'contentOptions' => ['style'=>'max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;'],
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'header' => '操作',
                    'headerOptions' => ['width' => '128', 'class' => 'padding-left-5px','style'=>'color:red'],
                    'contentOptions' => ['class' => 'padding-left-5px'],
                    'buttons' => [
                        'update' => function ($url, $model, $key) {
                            return Html::a('编辑', '/backend/application-update?id='.$model->id, ['title' => '编辑'] );
                        },
                        'delete' => function ($url, $model, $key) {
                            return Html::a('删除', '/backend/application-delete?id='.$model->id,
                                ['title' => '删除', 'data' => ['confirm' => '你确定要删除此微应用吗？']]);
                        }
                    ]
                ],
            ],
        ]); ?>
    </div>
    <div class="control-sidebar-bg"></div>
    <!-- /.box -->
</section>
<!-- /.content -->