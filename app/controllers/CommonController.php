<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;

/**
 * Site controller
 */
class CommonController extends Controller
{
    public $enableCsrfValidation = false;
    public $params; // 请求参数

    public function beforeAction($action)
    {
        if(!parent::beforeAction($action)) return false;
        //允许跨域
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header('Access-Control-Allow-Methods: GET, POST');

        $this->_validateMethod(); // 请求方式，post检测
        $this->_validateBody();

        //钉钉专用3s重复请求过滤
        if (in_array($action->id, $this->repeatAction) && F::repeatRequestDingApp()) {
            echo PsCommon::responseFailed('请勿重复请求，3s后重试');
            return false;
        }

        return true;
    }

    // 请求方法，只支持POST
    private function _validateMethod()
    {
        if (!Yii::$app->request->isPost) {
            exit($this->ajaxReturn('只支持POST请求'));
        }
    }

    // 请求体，json格式检测
    private function _validateBody()
    {
        $body = [];
        if (!empty(Yii::$app->request->getRawBody())) {
            error_log('[' . date('Y-m-d H:i:s', time()) . ']' . PHP_EOL . "前端请求参数前===:".Yii::$app->request->getRawBody() . PHP_EOL, 3, \Yii::$app->getRuntimePath().'/logs/front_req.log');
            $bodys = json_decode(Yii::$app->request->getRawBody(), true);
            if (!is_array($bodys)){
                exit($this->ajaxReturn('参数错误'));
            }
            $body = $bodys;
        }
        $this->params = $body;
    }

    //java透传接口
    public function actionPhpGateway()
    {
        \Yii::info("PhpGateway-params:" . json_encode($this->params, JSON_UNESCAPED_UNICODE), 'api');

    }
}
