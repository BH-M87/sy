<?php
/**
 * 七牛上传基类控制器
 * User: yshen
 * Date: 2018/5/9
 * Time: 22:20
 */

namespace app\modules\qiniu\controllers;

use Yii;
use common\core\F;
use common\core\PsCommon;
use common\CoreController;
use service\BaseService;

Class BaseController extends CoreController
{
    public $enableCsrfValidation = false;

    //允许跨域访问的域名
    public static $allowOrigins = [];

    public $requestParams = [];//当前请求参数

    public function init()
    {
        parent::init();
        //图片上传作为公共组件，需要调用的域名太多，这里暂时不限制跨域域名
        $origins = [];
        PsCommon::corsFilter($origins, true);
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        $data = F::request('data');
        $this->requestParams = $data ? json_decode($data, true) : [];
        return true;
    }
}
