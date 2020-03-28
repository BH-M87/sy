<?php
/**
 * User: ZQ
 * Date: 2019/8/14
 * Time: 10:40
 * For: ****
 */

namespace app\modules\ding_property_app\controllers;

use common\core\F;
use common\core\PsCommon;
use yii\base\Controller;
use Yii;

class BaseController extends Controller
{
    public $enableCsrfValidation = false;
    public $params; // 请求参数
    public $request_params; // 请求参数
    public $body;
    public $page = 1;
    public $pageSize = 20;
    public $repeatAction = []; // 验证重复请求的方法数组
    public $userId;
    public $token;

    public function beforeAction($action)
    {
        if(!parent::beforeAction($action)) return false;
        //允许跨域
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header('Access-Control-Allow-Methods: GET, POST');

        $this->_validateMethod(); // 请求方式，post检测
        $this->_validateBody();
        $this->_validateToken($action); // token验证

        // 配置基本参数
        $this->page = (integer)F::value($this->params, 'page', $this->page);
        $this->pageSize = (integer)F::value($this->params, 'rows', $this->pageSize);
        $this->userId = F::value($this->params, 'user_id', 0);
        $this->token = F::value($this->params, 'token', '');

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
        
        $this->body = $body;
        $this->params = $body;
        $this->request_params = $body;
    }

    // token验证
    private function _validateToken()
    {
        $header = Yii::$app->request->getHeaders();

        if (!isset($header['AppKey']) || empty($header['AppKey'])) {
            //exit($this->ajaxReturn('AppKey不能为空'));
        }

        // C端鉴权
        if (in_array(Yii::$app->controller->action->id, ['login-auth'])) {
            return ;
        }

        if (!isset($header['OpenAuthorization']) || empty($header['OpenAuthorization'])) {
            //exit($this->ajaxReturn('OpenAuthorization不能为空'));
        }

        $this->params['token'] = $header['OpenAuthorization'];
        $this->params['appKey'] = $header['AppKey'];
        $this->request_params['token'] = $header['OpenAuthorization'];
        $this->request_params['appKey'] = $header['AppKey'];
    }

    // 统一JSON返回
    protected function ajaxReturn($msg = '', $code = 50001)
    {
        return json_encode(['code' => $code, 'message' => $msg]);
    }
}