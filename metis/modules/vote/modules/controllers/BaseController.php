<?php
/**
 * 基础
 * @author wenchao.feng
 * @date 2017/11/23
 */
namespace alisa\modules\vote\modules\controllers;

use common\libs\F;
use common\services\park\UserService;
use Yii;
use yii\web\Controller;

Class BaseController extends Controller
{
    public $enableCsrfValidation = false;
    public $params;//请求参数
    public $page = 1;
    public $pageSize = 20;
    public $token;//用户身份标识
    public $user;//当前用户
    public $uid;

    public function beforeAction($action)
    {
        if(!parent::beforeAction($action)) return false;
        //允许跨域
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header('Access-Control-Allow-Methods: GET, POST');
        //过滤除GET，POST外的其他请求
        $method = strtoupper($_SERVER['REQUEST_METHOD']);
        if (!in_array($method, ['GET', 'POST'])) {
            return false;
        }

        $params = F::request('data', "");
        //配置基本参数
        $this->params = $params ? json_decode($params, true) : [];
        $this->page = (integer)F::value($this->params, 'page', $this->page);
        $this->pageSize = (integer)F::value($this->params, 'rows', $this->pageSize);
        $this->token = F::value($this->params, 'token','');
        $this->uid = F::request('uid');//支付宝UID
        //当前用户相关
//        $this->user = UserService::service()->getUserByToken($this->token);//当前用户信息
        $this->user = UserService::service()->getUserByAlipayId($this->uid);
        return true;
    }
}