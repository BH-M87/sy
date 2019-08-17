<?php
/**
 * 运营后台权限控制器
 * @author shenyang
 * @date 2017-05-11
 */

namespace app\modules\property\controllers;

use Yii;
use common\core\F;
use common\CoreController;
use common\core\PsCommon;
use service\manage\CommunityService;
use service\rbac\GroupService;
use service\rbac\MenuService;
use service\rbac\UserService;

Class BaseController extends CoreController
{
    public $enableCsrfValidation = false;
    //允许跨域访问的域名
    public static $allowOrigins = [
        'test' => [
            'dev-web.elive99.com',
            'api.elive99.com'
        ],
        'release' => [
            'dev-web.elive99.com',
        ],
        'master' => [
            'alipay.elive99.com'
        ],
    ];

    public $user_info = [];
    public $userId = '';//当前用户ID
    public $enableAction = [];//绕开token验证(登录，发送短信验证码，验证短信验证码，重置密码)
    public $communityNoCheck = [];//不验证小区ID，小区权限
    public $communityId = '';//当前请求的小区ID
    public $request_params;//请求参数
    public $page;//当前页
    public $pageSize = 10;//分页条数，后台默认10条数据
    public $systemType = 2;//当前系统类型
    public $repeatAction = [];//验证重复请求的方法数组
    public $addLogAction = [];//验证是否需要记录日志的数据

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
        $this->request_params['community_id'] = $this->communityId;
        $this->page = !empty($this->request_params['page']) ? intval($this->request_params['page']) : 1;
        $this->pageSize = !empty($this->request_params['rows']) ? intval($this->request_params['rows']) : $this->pageSize;
        //token验证
        $token = !empty($_REQUEST['token']) ? $_REQUEST['token'] : '';
        $token = substr($token, 0, 32);
        $result = UserService::service()->getInfoByToken($token);
        $this->user_info = !empty($result['data']) ? $result['data'] : [];
        $this->userId = PsCommon::get($this->user_info, 'id', 0);
        UserService::setUser($this->user_info);
        return true;
        if (!in_array($action->id, $this->enableAction)) {//验证token
            if (!$result['code']) {
                echo PsCommon::responseFailed($result['msg'], 50002);
                return false;
            }
            if ($this->user_info['system_type'] != UserService::SYSTEM_PROPERTY) {//判断系统
                echo PsCommon::responseFailed('token错误', 50002);
                return false;
            }
        }

        //验证签名
        if ($action->controller->id != 'download') {//下载文件不走签名
            $checkMsg = PsCommon::validSign($this->systemType);
            if ($checkMsg !== true) {
                echo PsCommon::responseFailed($checkMsg);
                return false;
            }
        }

        //不走token验证的接口，及download不走其他权限,小区ID 验证
        if (in_array($action->id, $this->enableAction) || $action->controller->id == 'download') {
            return true;
        }
        //菜单权限验证
        $verifyAction = $action->getUniqueId();
        $systemMenus = MenuService::service()->getMenuCache(!empty($this->user_info["system_type"]) ? $this->user_info["system_type"] : '');
        $menu_ids = !empty($systemMenus[$verifyAction]) ? $systemMenus[$verifyAction] : [];
        if (!empty($this->user_info) && $menu_ids) {
            //分组是否有路由权限
            if (!GroupService::service()->menuCheck($menu_ids, $this->user_info['group_id'])) {
                echo PsCommon::responseFailed('权限不足');
                return false;
            }
        }

        //物业系统必传小区ID
        if (!in_array($action->id, $this->communityNoCheck)) {
            if (!$this->communityId) {
                echo PsCommon::responseFailed('小区ID不能为空');
                return false;
            }
            //小区权限判断
            if (!CommunityService::service()->communityAuth($this->userId, $this->communityId)) {
                echo PsCommon::responseFailed('没有小区权限');
                return false;
            }
        }

        //重复请求过滤 TODO 1. 接口时间响应过长导致锁提前失效 2. 未执行完即取消请求，锁未主动释放，需等待30s
        if (in_array($action->id, $this->repeatAction) && F::repeatRequest()) {
            echo PsCommon::responseFailed('请勿重复请求，30s后重试');
            return false;
        }

        return true;
    }

    public function afterAction($action, $result)
    {
        if (in_array($action->id, $this->repeatAction)) {
            F::delRepeatCache();
        }
        if (in_array($action->id, $this->addLogAction)) {
            //说明需要记录日志
            $html  = "Request time:" . date('YmdHis') . "\r\n";
            $html .= "Request url:" . $action->id . "\r\n";
            $html .= "Request params:" . var_export($this->request_params, true) . "\r\n";
            $html .= "Response content:". var_export($result, true)."\r\n";
            F::addLog("import-batch.txt",$html);
        }
        return parent::afterAction($action, $result);
    }
}
