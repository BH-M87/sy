<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2019/8/19
 * Time: 13:57
 */

namespace app\modules\ali_small_lyl\controllers;


use common\core\F;
use yii\web\Controller;
use Yii;

class BaseController extends Controller
{
    public $enableCsrfValidation = false;
    public $params;//请求参数
    public $user;//当前用户
    public $uid;

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
        return true;
    }
}