<?php
namespace app\modules\ding_property_app\modules\v2\controllers;
use common\core\F;
use common\core\PsCommon;
use common\MyException;
use service\street\UserService;
use yii\base\Controller;

/**
 * User: ZQ
 * Date: 2019/9/6
 * Time: 13:43
 * For: ****
 */
class BaseController extends Controller
{
    public $enableCsrfValidation = false;
    public $page = 1;
    public $pageSize = 20;
    public $request_params;
    public $user_id;
    public $user_info;

    public function beforeAction($action)
    {

        F::setSmallStatus();//钉钉端设置这个参数返回errCode
        $params = F::request();
        if(empty($params['user_id'])){
            throw new MyException('用户id不能为空');
        }

        $this->user_id = $params['user_id'];
        $this->user_info = UserService::service()->getUserInfoById($this->user_id);
        if(empty($this->user_info)){
            throw new MyException('用户不存在');
        }
        //配置基本参数
        $this->request_params = !empty($params['data']) ? json_decode($params['data'],true) : [];
        $this->request_params['user_id'] = $this->user_id;
        $this->page = (integer)F::value($params, 'page', $this->page);
        $this->pageSize = (integer)F::value($params, 'rows', $this->pageSize);

        \Yii::info("controller:".\Yii::$app->controller->id."action:".$action->id.'request:'.json_encode($this->request_params). "-user_id:".$this->user_id,'api');

        return true;
    }
}