<?php
/**
 * Created by PhpStorm.
 * User: yanghaoliang
 * Date: 2018/10/19
 * Time: 11:31 AM
 */

namespace alisa\modules\small\controllers;


use app\common\core\PsCommon;
use common\libs\F;
use common\services\small\ComplaintServer;

class ComplaintController extends BaseController
{
    //投诉建议列表
    public function actionList()
    {
        $params['community_id'] = F::value($this->params, 'community_id', '');
        $params['type'] = F::value($this->params, 'type', '');
        $params['room_id'] = F::value($this->params, 'room_id', '');
        $params['app_user_id'] = F::value($this->params, 'user_id', '');
        if (!$params['app_user_id']) {
            return F::apiFailed('用户id不能为空！');
        }
        if (!$params['community_id']) {
            return F::apiFailed('小区id不能为空！');
        }
        if (!$params['room_id']) {
            return F::apiFailed('房屋id不能为空！');
        }

        if(!$params['type']) {
            return F::apiFailed('类型不能为空！');
        }

        $result = ComplaintServer::service()->getList($params);
        return $this->dealResult($result);
    }

    //投诉建议详情
    public function actionShow()
    {
        $params['id'] = F::value($this->params, 'id', '');
        if (!$params['id']) {
            return F::apiFailed('id不能为空！');
        }
        $result = ComplaintServer::service()->show($params);
        return $this->dealResult($result);
    }

    //投诉建议新增
    public function actionAdd()
    {
        $params['community_id'] = F::value($this->params, 'community_id', '');
        $params['room_id'] = F::value($this->params, 'room_id', '');
        $params['type'] = F::value($this->params, 'type', '');
        $params['app_user_id'] = F::value($this->params, 'user_id', '');
        $params['content'] = F::value($this->params, 'content', '');
        $params['images'] = F::value($this->params, 'images', '');

        if (!$params['app_user_id']) {
            return F::apiFailed('用户id不能为空！');
        }
        if (!$params['community_id']) {
            return F::apiFailed('小区id不能为空！');
        }
        if (!$params['room_id']) {
            return F::apiFailed('房屋id不能为空！');
        }
        
        if(!$params['type']) {
            return F::apiFailed('类型不能为空！');
        }

        if(!$params['content']) {
            return F::apiFailed('内容不能为空！');
        }

        $result = ComplaintServer::service()->add($params);
        return $this->dealResult($result);
    }

    //取消投诉建议
    public function actionCancel()
    {
        $params['community_id'] = F::value($this->params, 'community_id', '');
        $params['id'] = F::value($this->params, 'id', '');
        $params['app_user_id'] = F::value($this->params, 'user_id', '');
        if (!$params['app_user_id']) {
            return F::apiFailed('用户id不能为空！');
        }
        if (!$params['community_id']) {
            return F::apiFailed('小区id不能为空！');
        }

        if(!$params['id']) {
            return F::apiFailed('id不能为空！');
        }

        $result = ComplaintServer::service()->cancel($params);
        return $this->dealResult($result);
    }


    //获取管家评价列表
    public function actionStewardList(){
        $params = $this->params;
        $params['app_user_id'] = F::value($this->params, 'user_id', '');
        $result = ComplaintServer::service()->stewardList($params);
        return $this->dealResult($result);
    }

    //获取管家详情
    public function actionStewardInfo(){
        $params = $this->params;
        $params['app_user_id'] = F::value($this->params, 'user_id', '');
        $result = ComplaintServer::service()->stewardInfo($params);
        return $this->dealResult($result);
    }

    //添加管家评价
    public function actionAddSteward(){
        $params = $this->params;
        $params['app_user_id'] = F::value($this->params, 'user_id', '');
        $result = ComplaintServer::service()->addSteward($params);
        return $this->dealResult($result);
    }

}