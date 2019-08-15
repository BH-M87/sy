<?php
/**
 * 物业后台基础类，处理入参、出参
 * User: fengwenchao
 * Date: 2019/8/12
 * Time: 10:34
 */

namespace app\modules\property\controllers;

use common\core\F;
use common\core\PsCommon;
use yii\web\Controller;

class BaseController extends Controller {
    public $communityId = '';//当前请求的小区ID
    public $request_params;//请求参数
    public $page;//当前页
    public $pageSize = 10;//分页条数，后台默认10条数据
    public $systemType = 2;//当前系统类型
    public $userId = '';//当前用户ID
    public $user_info = []; //当前操作用户信息
    public $enableAction = [];//绕开token验证(登录，发送短信验证码，验证短信验证码，重置密码)
    public $repeatAction = [];//验证重复请求的方法数组
    public $communityIdNoCheck = [];//物业系统不需要验证小区ID的方法

    //允许的访问域名，做不同环境区分
    public static $allowOrigins = [
        'test' => [
        ],
        'release' => [
        ],
        'master' => [
        ],
    ];

    public function init()
    {
        parent::init();
        $origins = PsCommon::get(self::$allowOrigins, YII_ENV, []);
        PsCommon::corsFilter($origins);
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        $this->communityId  = F::request('community_id');
        $this->request_params = !empty($_REQUEST['data']) ? json_decode($_REQUEST['data'], true) : [];
        $this->request_params['community_id'] = $this->request_params['community_id'] ?? $this->communityId;
        $this->page = $this->request_params['page'] = PsCommon::get($this->request_params, 'page', 1);
        $this->pageSize = $this->request_params['rows'] = PsCommon::get($this->request_params, 'rows', $this->pageSize);

        //签名验证，物业后台存在下载方法，下载文件不走签名校验
        if ($action->controller->id != 'download') {
            $checkMsg = PsCommon::validSign($this->systemType);
            if ($checkMsg !== true) {
                echo PsCommon::responseFailed($checkMsg);
                return false;
            }
        }

        //token验证
        $token = F::request('token', '');
        //TODO 查询token，给user_info,userId赋值
        $this->user_info = [
            'id' => 1,
            'username' => 'test123',
            'truename' => 'test123',
            'property_company_id' => 1
        ];
        $this->userId = 1;

        //权限校验
        $verifyAction = $action->getUniqueId();
        //TODO 权限校验，先默认为true
        $result = true;
        if (!$result) {
            echo PsCommon::responseFailed('权限不足');
            return false;
        }

        //物业系统必传小区ID
        if ($action->controller->id != 'download' && $this->systemType == 2 && !in_array($action->id, $this->communityIdNoCheck)) {
            if (!$this->communityId) {
                echo PsCommon::responseFailed('小区ID不能为空');
                return false;
            }
        }

        //重复请求过滤，有些邻易联小程序或者钉钉应用需要验证重复请求
        if (in_array($action->id, $this->repeatAction) && F::repeatRequest()) {
            echo PsCommon::responseFailed('请勿重复请求，30s后重试');
            return false;
        }

        return true;
    }

}