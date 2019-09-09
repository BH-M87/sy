<?php
/**
 * User: ZQ
 * Date: 2019/8/29
 * Time: 14:02
 * For: 门禁小程序相关
 */

namespace app\modules\ali_small_lyl\modules\v1\controllers;


use app\modules\ali_small_lyl\controllers\UserBaseController;
use common\core\F;
use service\common\AlipaySmallApp;
use service\small\MemberService;
use service\alipay\MemberCardService;

class HomeController extends UserBaseController
{

    //小程序首页数据
    public function actionIndexData()
    {
        $r['app_user_id']  = F::value($this->params, 'user_id');
        $r['room_id'] = F::value($this->params, 'room_id');
        $r['community_id']  = F::value($this->params, 'community_id');

        if (!$r['app_user_id']) {
            return F::apiFailed("用户id不能为空！");
        }
        $r['system_type'] = 'fczl';
        $result = MemberService::service()->getHomeData($r);
        return $this->dealReturnResult($result);
    }

    // 开卡
    public function actionOpenCard()
    {
        $data['user_id'] = F::value($this->params, 'user_id');
        $data['system_type'] = F::value($this->params, 'system_type','small');

        $result = MemberCardService::service()->cardOpen($data);

        return self::dealReturnResult($result);
    }
}