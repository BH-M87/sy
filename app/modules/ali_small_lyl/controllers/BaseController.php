<?php
/**
 * Created by PhpStorm.
 * User: zhd
 * Date: 2019/11/15
 * Time: 13:38
 */
namespace app\modules\ali_small_lyl\controllers;

use common\core\JavaCurl;
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
    public $params = [];
    //存储用户信息
    public $user_info = [];
    //当前页
    public $page;
    //分页条数，后台默认10条数据
    public $rows = 10;

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
            $this->page = !empty($this->params['page']) ? intval($this->params['page']) : 1;
            $this->rows = !empty($this->params['rows']) ? intval($this->params['rows']) : $this->rows;
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
            exit($this->ajaxReturn('请求错误'));
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
                exit($this->ajaxReturn('参数错误'));
            }
            $body = $bodys;
        } else if ($_FILES) {
            $body = $_FILES;
        }
        
        $this->body = $body;
        $this->params = $body;
    }


    /**
     * Notes: token验证
     * Author: J.G.N
     * Date: 2019/11/18 15:11
     * @param $action
     */
    private function _validateToken()
    {
        $header = Yii::$app->request->getHeaders();

        if (!isset($header['AppKey']) || empty($header['AppKey'])) {
            exit($this->ajaxReturn('AppKey不能为空'));
        }
        $this->params['appKey'] = $header['AppKey'];

        //C端鉴权
        if (in_array(Yii::$app->controller->action->id, ['login-auth'])) {
            return ;
        }
        if (!isset($header['OpenAuthorization']) || empty($header['OpenAuthorization'])) {
            exit($this->ajaxReturn('OpenAuthorization不能为空'));
        }


        $this->params['token'] = $header['OpenAuthorization'];
        $this->params['appKey'] = $header['AppKey'];
    }

    /**
     * 统一JSON返回
     * @param $errIndex
     * @param array $data
     * @return array
     * @throws Exception
     */
    protected function ajaxReturn($msg = '',$code=50001)
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

