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
    public $request_params;//请求参数
    public $page = 1;
    public $pageSize = 20;
    public $repeatAction = [];//验证重复请求的方法数组
    public $userId;

    //允许访问的域名
    //TODO 验证请求域名
    public $allowDomains = [
        'dev' => [],
        'test' => [],
        'release' => [],
        'prod' => []
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
            if (YII_ENV == 'prod' && !in_array($host, $this->allowDomains[YII_ENV])) {
                echo "非法请求".$host;
                return false;
            }
        }

        $params = F::request();
        //配置基本参数
        $this->request_params = $params ? $params : [];
        $this->page = (integer)F::value($params, 'page', $this->page);
        $this->pageSize = (integer)F::value($params, 'rows', $this->pageSize);
        $this->userId = F::value($params, 'user_id', 0);

        \Yii::info("controller:".Yii::$app->controller->id."action:".$action->id.'request:'.json_encode($this->request_params),'api');

        //钉钉专用3s重复请求过滤
        if (in_array($action->id, $this->repeatAction) && F::repeatRequestDingApp()) {
            echo PsCommon::responseFailed('请勿重复请求，3s后重试');
            return false;
        }
        return true;
    }
}