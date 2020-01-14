<?php
/**
 * Created by PhpStorm.
 * User: zhd
 * Date: 2019/11/15
 * Time: 13:38
 */
namespace app\modules\property\controllers;

use common\core\F;
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
    //存储下载参数
    public $request_params = [];
    //存储body请求参数
    public $down_request_params = [];
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
    public $repeatAction = [];//验证重复请求的方法数组

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
            $this->down_request_params = !empty(F::request('data')) ? json_decode(F::request('data'), true) : [];
            if (!in_array($action->controller->id, ['download'])) {//下载文件不走签名
                //请求方式，post检测
                $this->_validateMethod();
                $this->_validateBody();
                //token验证
                $this->_validateToken($action);
                $this->page = !empty($this->request_params['page']) ? intval($this->request_params['page']) : 1;
                $this->pageSize = !empty($this->request_params['rows']) ? intval($this->request_params['rows']) : $this->pageSize;

                //重复请求过滤 TODO 1. 接口时间响应过长导致锁提前失效 2. 未执行完即取消请求，锁未主动释放，需等待30s
                if (in_array($action->id, $this->repeatAction) && F::repeatRequest()) {
                    exit($this->ajaxReturn('请勿重复请求，30s后重试'));
                }
            }
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
        $body = !empty($body)?$body:F::request();   //如果raw没有值 默认取form-data
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
            exit($this->ajaxReturn('authorization不能为空'));
        }
        //todo::调用java token鉴权接口,并拿到用户信息
        $params = [
            'route' => '/user/validate-token',
            'token' => $header['authorization'],
        ];
        //todo::调用java接口
        $result = JavaCurl::getInstance()->pullHandler($params);
        $this->user_info = $result;
        $this->user_info['propertyMark'] = 1; // 后台标记需要添加操作日志
        $this->user_info['truename'] = $result['trueName'];
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
    protected function ajaxReturn($msg='',$code=50001)
    {
        return json_encode(['code' => $code,'message' => $msg,]);
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

