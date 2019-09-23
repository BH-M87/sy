<?php
/**
 * 供应商相关操作
 * User: wenchao.feng
 * Date: 2019/1/31
 * Time: 11:19
 */
namespace app\modules\property\modules\v1\controllers;
use app\models\PsPropertyAlipay;
use Yii;
use yii\web\Controller;
use yii\data\Pagination;
use yii\data\ActiveDataProvider;


class CompanyH5Controller extends Controller
{
    public $layout = "main";
    public function actionIndex()
    {
        $model = new PsPropertyAlipay();
        $dataProvider = new ActiveDataProvider([
            'query' => $model::find()->orderBy('id desc'),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
        return $this->render('index', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }



}