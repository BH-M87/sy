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
use services\dingding\UserService;
use yii\base\Controller;
use Yii;

class BaseController extends Controller
{
    public $enableCsrfValidation = false;
    public $params;//请求参数
    public $page = 1;
    public $pageSize = 20;

    public $userInfo = [];
    public $token;
    public $userId;
    public $userPhone;

    public $allowDomains = [
        'dev' => ['localhost', 'dev-web.elive99.com','dingdinglyl.vaiwan.com','ddweb-company.elive99.com','live.vaiwan.com'],
        'yuanyuan' => ['localhost', 'dev-web.elive99.com','dingdinglyl.vaiwan.com','ddweb-company.elive99.com','live.vaiwan.com'],
        'master' => ['ddweb.elive99.com'],
    ];

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
        //判断referer
        $referer = Yii::$app->request->getReferrer();
        if ($referer) {
            $host = parse_url($referer, PHP_URL_HOST);
            $urlArray = explode('.',$host);
            if($urlArray[1] != 'elive99'){
                if (!in_array($host, $this->allowDomains[YII_OWNER])) {
                    echo "非法请求".$host;
                    return false;
                }
            }
        }

        $params = F::request();
        //配置基本参数
        $this->params = $params ? $params : [];
        $this->page = (integer)F::value($this->params, 'page', $this->page);
        $this->pageSize = (integer)F::value($this->params, 'rows', $this->pageSize);

        //是否要验证token
        //验证token
        $re = UserService::service()->refreshToken($this->token);
        if ($re === false) {
            die(PsCommon::responseFailed('token已过期!', 50002));
        }
        $this->userId = $re;
        $userPhone = UserService::service()->getUserPhoneById($this->userId);
        $this->userPhone = $userPhone;
        $userInfo = UserService::service()->getUserByPhone($this->userPhone);
        if (is_array($userInfo)) {
            $userInfo['operator_id'] = $userInfo['id'];
            $this->userInfo = $userInfo;
        } else {
            die(PsCommon::responseFailed($userInfo, 50004));
        }
        //钉钉专用3s重复请求过滤 TODO 1. 接口时间响应过长导致锁提前失效 2. 未执行完即取消请求，锁未主动释放，需等待30s
        if (in_array($action->id, $this->repeatAction) && F::repeatRequest2()) {
            echo PsCommon::responseFailed('请勿重复请求，3s后重试');
            return false;
        }

        return true;
    }
}