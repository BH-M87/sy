<?php
/**
 * 运营后台权限控制器
 * @author shenyang
 * @date 2017-05-11
 */

namespace app\modules\property\controllers;

use common\MyException;
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
        'prod' => [
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
        $dataStr = !empty($_REQUEST['data']) ? $_REQUEST['data'] : '';
        $this->communityId  = F::request('community_id');
        $this->userId = F::request('user_id');
        \Yii::info("controller:".Yii::$app->controller->id."action:".$action->id.'request:'.$dataStr.'user_id:'.$this->userId,'api');
        $this->request_params = !empty($_REQUEST['data']) ? json_decode($_REQUEST['data'], true) : [];
        $this->request_params['community_id'] = $this->communityId;
        $this->page = !empty($this->request_params['page']) ? intval($this->request_params['page']) : 1;
        $this->pageSize = !empty($this->request_params['rows']) ? intval($this->request_params['rows']) : $this->pageSize;

        //验证用户
        if (!in_array($action->controller->id, ['download', 'third-butt'])) {//下载文件不走签名
            if (!$this->userId) {
                throw new MyException('登录用户id不能为空');
            }
            //$userInfo = UserService::service()->getUserById($this->userId);
            $userInfo = \service\street\UserService::service()->getUserInfoById($this->userId);
            $userInfo['mobile'] = $userInfo['mobile_number'];
            $community_id = \service\street\UserService::service()->getCommunityList($userInfo['node_type'],$userInfo['dept_id']);
            //token验证
            $this->user_info = $userInfo;
            $communityId = $this->communityId ? $this->communityId : $community_id[0];
            $this->communityId = $communityId;
            $this->request_params['community_id'] = $communityId;
            UserService::setUser($this->user_info);
        }

        //不走token验证的接口，及download不走其他权限,小区ID 验证
        if (in_array($action->id, $this->enableAction) || $action->controller->id == 'download' || $action->controller->id == 'third-butt') {
            return true;
        }

        //物业系统必传小区ID
        if (!in_array($action->id, $this->communityNoCheck)) {
            if (!$this->communityId) {
                throw new MyException('小区ID不能为空');
            }
        }

        //重复请求过滤 TODO 1. 接口时间响应过长导致锁提前失效 2. 未执行完即取消请求，锁未主动释放，需等待30s
        if (in_array($action->id, $this->repeatAction) && F::repeatRequest()) {
            throw new MyException('请勿重复请求，30s后重试');
        }
        return true;
    }
}
