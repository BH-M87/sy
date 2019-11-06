<?php
namespace app\modules\ali_small_door\controllers;

use Yii;

use yii\web\Controller;

use common\core\F;
use common\core\PsCommon;

class BaseController extends Controller
{
    public $enableCsrfValidation = false;
    public $params;//请求参数
    public $user;//当前用户
    public $uid;
    public $page;
    public $rows;

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
        $params = F::request();
        $this->params = !empty($params['data']) ? json_decode($params['data'],true) : [];
        $this->page = !empty($params['page']) ? $params['page'] : 1;
        $this->rows = !empty($params['rows']) ? $params['rows'] : 20;

        return true;
    }
    
    public function dealReturnResult($result)
    {
        if($result['code'] == 1){
            return PsCommon::responseAppSuccess($result['data']);
        } else {
            if (!empty($result['code'])) {
                return PsCommon::responseAppFailed($result['msg'], $result['code']);
            }
            return PsCommon::responseAppFailed($result['msg']);
        }
    }
}
