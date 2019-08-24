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
//        $origins = PsCommon::get(self::$allowOrigins, YII_ENV, []);
//        PsCommon::corsFilter($origins);
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
        $this->user_info = [];
        $this->userId = PsCommon::get($this->user_info, 'id', 0);
        return true;
    }
}
