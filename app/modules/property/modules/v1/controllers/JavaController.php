<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2019/11/27
 * Time: 14:49
 * Desc: 调用java接口
 */
namespace app\modules\property\modules\v1\controllers;

use service\property_basic\JavaService;
use Yii;
use app\modules\property\controllers\BaseController;
use common\core\PsCommon;

use yii\base\Exception;class JavaController extends BaseController{


    /*
     * 小区列表
     */
    public function actionCommunityNameList(){
        try{

            $data = $this->request_params;
            $result = JavaService::service()->communityNameList($data);
            return PsCommon::responseSuccess($result);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

}
