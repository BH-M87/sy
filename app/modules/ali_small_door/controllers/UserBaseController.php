<?php
/**
 * 需要验证用户信息的基类控制器
 * User: fengwenchao
 * Date: 2019/8/19
 * Time: 13:57
 */

namespace app\modules\ali_small_door\controllers;


use common\core\F;

class UserBaseController extends BaseController
{
    public $appUserId;

    public function beforeAction($action)
    {
        if(!parent::beforeAction($action)) return false;
        $this->appUserId = F::value($this->params, 'app_user_id');
        if (!$this->appUserId) {
            return F::apiFailed('用户id不能为空！');
        }
        return true;
    }

    public function dealReturnResult($result)
    {
        if($result['code'] == 1){
            return F::apiSuccess($result['data']);
        } else {
            if (!empty($result['code'])) {
                return F::apiFailed($result['msg'], $result['code']);

            }
            return F::apiFailed($result['msg']);
        }
    }

    public function dealResult($result)
    {
        if(is_array($result)){
            if($result['errCode'] == 0){
                return F::apiSuccess($result['data']);
            } else {
                return F::apiFailed($result['errMsg'], $result['errCode']);
            }
        }else{
            $res = json_decode($result,true);
            if($res['errCode'] == 0){
                return F::apiSuccess($res['data']);
            } else {
                return F::apiFailed($res['errMsg'], $res['errCode']);
            }
        }
    }

}