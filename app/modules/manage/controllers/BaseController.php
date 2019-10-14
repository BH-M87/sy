<?php
/**
 * 运营系统基类控制器
 * @author shenyang
 * @date 2018-05-02
 */

namespace app\modules\manage\controllers;

use app\models\ZjyUserRole;
use Yii;
use common\core\F;
use common\core\PsCommon;
use common\CoreController;
use service\rbac\GroupService;
use service\rbac\MenuService;
use service\rbac\UserService;
use service\BaseService;


Class BaseController extends CoreController
{
    public $enableCsrfValidation = false;
    //允许跨域访问的域名
    public static $allowOrigins = [
        'test' => [
            'dev-web.elive99.com',//前端测试环境
        ],
        'release' => [
            'dev-web.elive99.com',//前端测试环境
        ],
        'prod' => [
            'hd-wuyeyy.zje.com'
        ],
    ];

    public $user_info = [];//当前用户信息
    public $userId = 0;//当前用户ID
    public $enableAction = [];//绕开权限的验证
    public $request_params = [];//请求参数
    public $page = 1;//当前查询页
    public $pageSize = 20;//默认分页条数
    public $systemType = 1;//当前系统类型

    //跨域判断
    public function init()
    {
        parent::init();
        //$origins = PsCommon::get(self::$allowOrigins, YII_ENV, []);
        //PsCommon::corsFilter($origins);
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        //公共变量
        $data = F::request('data');
        $this->request_params = $data ? json_decode($data, true) : [];
        $this->page = !empty($this->request_params['page']) ? intval($this->request_params['page']) : 1;
        $this->pageSize = !empty($this->request_params['rows']) ? intval($this->request_params['rows']) : $this->pageSize;

        //token验证
        //$token = F::request('token');
        //$result = UserService::service()->getInfoByToken($token);
        $this->userId = F::request('user_id');
        $this->user_info =  \service\street\UserService::service()->getManageUserInfoById($this->userId);
        //$this->user_info = !empty($result['data']) ? $result['data'] : [];
        //$this->userId = PsCommon::get($this->user_info, 'id', 0);
        UserService::setUser($this->user_info);
        if (!in_array($action->id, $this->enableAction)) {//先验证token
            /*if (!$result['code']) {
                echo PsCommon::responseFailed($result['msg'], 50002);
                return false;
            }*/

            /*if ($this->request_params['system_type'] != UserService::SYSTEM_OM) {//判断系统
                echo PsCommon::responseFailed('token错误', 50002);
                return false;
            }*/
        }
        //验证签名
        /*if ($action->controller->id != 'download') {//下载文件不走签名
            $checkMsg = PsCommon::validSign($this->systemType);
            if ($checkMsg !== true) {
                echo PsCommon::responseFailed($checkMsg);
                return false;
            }
        }*/

        //login等个别无需token验证的接口及download不走权限验证
        if (in_array($action->id, $this->enableAction) || $action->controller->id == 'download') {
            return true;
        }

        //权限验证
        /*$verifyAction = $action->getUniqueId();
        $systemMenus = MenuService::service()->getMenuCache(!empty($this->user_info["system_type"]) ? $this->user_info["system_type"] : '');
        $menu_ids = !empty($systemMenus[$verifyAction]) ? $systemMenus[$verifyAction] : [];
        if ($menu_ids) {
            //分组是否有路由权限
            if (!GroupService::service()->menuCheck($menu_ids, $this->user_info['group_id'])) {
                echo PsCommon::responseFailed('权限不足');
                return false;
            }
        }*/

        return true;
    }
}
