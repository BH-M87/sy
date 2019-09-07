<?php
/**
 * User: ZQ
 * Date: 2019/9/4
 * Time: 10:57
 * For: ****
 */

namespace app\modules\street\controllers;


use common\core\F;
use common\core\PsCommon;
use common\MyException;
use service\street\UserService;
use yii\base\Controller;

class BaseController extends Controller
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
    public $user_id = '';//当前请求的用户id
    public $request_params;//请求参数
    public $page;//当前页
    public $pageSize = 10;//分页条数，后台默认10条数据
    public $systemType = 3;//当前系统类型
    public $repeatAction = [];//验证重复请求的方法数组
    public $addLogAction = [];//验证是否需要记录日志的数据

    public function init()
    {
        parent::init();
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        $this->user_id  = F::request('user_id');
        if (!$this->user_id) {
            throw new MyException("用户id不存在！");
        }
        $this->request_params = !empty($_REQUEST['data']) ? json_decode($_REQUEST['data'], true) : [];
        $this->request_params['user_id'] = $this->user_id;
        $this->page = !empty($this->request_params['page']) ? intval($this->request_params['page']) : 1;
        $this->pageSize = !empty($this->request_params['rows']) ? intval($this->request_params['rows']) : $this->pageSize;
        $this->user_info = UserService::service()->getUserInfoById($this->user_id);
        //UserService::setUser($this->user_info);
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

        //物业系统必传小区ID
//        if (!in_array($action->id, $this->communityNoCheck)) {
//            if (!$this->user_id) {
//                echo PsCommon::responseFailed('用户不能为空');
//                return false;
//            }
//        }

        //重复请求过滤 TODO 1. 接口时间响应过长导致锁提前失效 2. 未执行完即取消请求，锁未主动释放，需等待30s
        if (in_array($action->id, $this->repeatAction) && F::repeatRequest()) {
            echo PsCommon::responseFailed('请勿重复请求，30s后重试');
            return false;
        }
        return true;
    }
}