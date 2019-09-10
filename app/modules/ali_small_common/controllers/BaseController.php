<?php
/**
 * Created by PhpStorm.
 * User: ZQ
 * Date: 2018/3/22
 * Time: 11:14
 */

namespace app\modules\ali_small_common\controllers;

use Yii;
use yii\web\Controller;
use common\core\F;

class BaseController extends Controller
{
    public $enableCsrfValidation = false;
    public $params;//请求参数
    public $user;//当前用户
    public $uid;

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) return false;
        //允许跨域
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header('Access-Control-Allow-Methods: GET, POST');
        //过滤除GET，POST外的其他请求
        $method = strtoupper($_SERVER['REQUEST_METHOD']);
        if (!in_array($method, ['GET', 'POST'])) {
            return false;
        }
        $params = F::request();
        $this->params = !empty($params['data']) ? json_decode($params['data'], true) : [];
        \Yii::info("system:small-app"."controller:".\Yii::$app->controller->id."action:".$action->id.'request:'.json_encode($this->params),'api');
        return true;
    }
}