<?php
/**
 * Created by PhpStorm.
 * User: zhd
 * Date: 2019/11/15
 * Time: 13:38
 */
namespace app\modules\property\controllers;

use common\core\JavaCurl;
use service\property_basic\JavaService;
use Yii;
use yii\base\Exception;

/**
 * 控制器基类
 * @package app\base
 */
class BaseController extends \yii\web\Controller
{
    //全局关闭csrf防御，接口无需csrf防御
    public $enableCsrfValidation = false;
    //存储request请求体
    public $body = [];
    //存储data
    public $request_params = [];
    //存储用户信息
    public $user_info = [];
    //存储token
    public static $token = '';
    //当前页
    public $page;
    //分页条数，后台默认10条数据
    public $pageSize = 10;
    //当前登录用户的小区列表
    public $community_list = [];

    public function init(){
        //跨域
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept,sign,timeNumber,random,authorization, openAuthorization");
        header("Access-Control-Max-Age: 3600");
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    }

    /**
     * 接口鉴权
     * @param \yii\base\Action $action
     * @return bool
     */
    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            //请求方式，post检测
            $this->_validateMethod();
            $this->_validateBody();
            //token验证
            $this->_validateToken($action);
            $this->page = !empty($this->request_params['page']) ? intval($this->request_params['page']) : 1;
            $this->pageSize = !empty($this->request_params['rows']) ? intval($this->request_params['rows']) : $this->pageSize;
            //所有验证通过
            return true;
        }
        return false;
    }

    /**
     * 请求方法，只支持POST
     */
    private function _validateMethod()
    {
        if (!Yii::$app->request->isPost) {
            exit($this->ajaxReturn('request_method'));
        }
    }

    /**
     * 请求体，json格式检测
     */
    private function _validateBody()
    {
        $body = [];
        if (!empty(Yii::$app->request->getRawBody())) {
            error_log('[' . date('Y-m-d H:i:s', time()) . ']' . PHP_EOL . "前端请求参数前===:".Yii::$app->request->getRawBody() . PHP_EOL, 3, \Yii::$app->getRuntimePath().'/logs/front_req.log');
            $bodys = json_decode(Yii::$app->request->getRawBody(), true);
            if (!is_array($bodys)){
                exit($this->ajaxReturn('http_body',[]));
            }
            $body = $bodys;
        }
        $this->body = $body;
        $this->request_params = $body;
    }


    /**
     * Notes: token验证
     * Author: J.G.N
     * Date: 2019/11/18 15:11
     * @param $action
     */
    private function _validateToken($action)
    {
        $header = Yii::$app->request->getHeaders();
        //C端鉴权
//        if (in_array(Yii::$app->controller->module->id, ['alipay'])) {
//            if (in_array(Yii::$app->controller->action->id, ['login-auth'])) {
//                return ;
//            }
//            if (!isset($header['OpenAuthorization']) || empty($header['OpenAuthorization'])) {
//                exit($this->ajaxReturn('authorization',[]));
//            }
//            $this->data['token'] = $header['OpenAuthorization'];
//            //todo::测试目前写死企业id
////            $this->data['corp_id'] = 1;
//            return ;
//        }
        //B端鉴权
        if (!isset($header['authorization']) || empty($header['authorization'])) {
            exit($this->ajaxReturn('authorization',[]));
        }
        //todo::调用java token鉴权接口,并拿到用户信息
        $params = [
            'route' => '/user/validate-token',
            'token' => $header['authorization'],
        ];
        //todo::调用java接口
        $result = JavaCurl::getInstance()->pullHandler($params);
        $this->user_info = $result;
        $this->request_params['token'] = $header['authorization'];
        $this->request_params['create_id'] = $result['id'];
        $this->request_params['create_name'] = $result['trueName'];
        $this->request_params['corp_id'] = $result['corpId'];
    }

    /**
     * 统一JSON返回
     * @param $errIndex
     * @param array $data
     * @return array
     * @throws Exception
     */
    protected function ajaxReturn($errIndex, $data = [])
    {
        $errorConfig = Yii::$app->params['error'];
        if (empty($errorConfig[$errIndex])) {
            $err = ['errorMsg' => "未找到返回信息索引[{$errIndex}]"];
            $errorMsg = json_encode([
                'code' => $errorConfig['params_error']['code'],
                'message' => $err,
                'data' => (object)[],
            ],JSON_UNESCAPED_UNICODE);
            throw new Exception($errorMsg);
        }
        $error = $errorConfig[$errIndex];
        return json_encode([
            'code' => $error['code'],
            'message' => $error['info'],
            'data' => (object)$data,
        ]);
    }

    /***
     * Excel文件导入URL
     * @return array
     */
    protected function _specialUrl(){
        $data = [
        ];
        return $data;
    }

}

