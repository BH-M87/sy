<?php
namespace app\modules\property\modules\v1\controllers;

use Yii;

use common\core\F;
use common\core\PsCommon;

use service\alipay\MemberCardService;

use app\modules\property\controllers\BaseController;

Class MemberCardController extends BaseController
{
    // 会员卡 开卡 表单模板配置
    public function actionCardFormtemplateSet()
    {
        $result = MemberCardService::service()->cardFormtemplateSet($this->request_params);

        return PsCommon::responseSuccess($result);
    }

    // 获取会员卡领卡投放链接
    public function actionCardActivateurlApply()
    {
        $result = MemberCardService::service()->cardActivateurlApply($this->request_params);

        return PsCommon::responseSuccess($result);
    }

    // 查询用户提交的会员卡表单信息
    public function actionCardActivateformQuery()
    {
        $result = MemberCardService::service()->cardActivateformQuery($this->request_params);

        return PsCommon::responseSuccess($result);
    }

    // 换取授权访问令牌
    public function actionOauthToken()
    {
        $result = MemberCardService::service()->oauthToken($this->request_params);

        return PsCommon::responseSuccess($result);
    }

    // 会员卡 开卡
    public function actionCardOpen()
    {
        $result = MemberCardService::service()->cardOpen($this->request_params);

        return PsCommon::responseSuccess($result);
    }

    // 会员卡 更新
    public function actionCardUpdate()
    {
        $result = MemberCardService::service()->cardUpdate($this->request_params);

        return PsCommon::responseSuccess($result);
    }

    // 会员卡 查询
    public function actionCardQuery()
    {
        $result = MemberCardService::service()->cardQuery($this->request_params);

        return PsCommon::responseSuccess($result);
    }

    // 会员卡 删卡
    public function actionCardDelete()
    {
        $result = MemberCardService::service()->cardDelete($this->request_params);

        return PsCommon::responseSuccess($result);
    }

    // 会员卡模板 修改
    public function actionCardTemplateModify()
    {
        $result = MemberCardService::service()->cardTemplateModify($this->request_params);
print_r($result);die;
        return PsCommon::responseSuccess($result);
    }

    // 会员卡模板 查询
    public function actionCardTemplateQuery()
    {
        $result = MemberCardService::service()->cardTemplateQuery($this->request_params);

        return PsCommon::responseSuccess($result);
    }

    // 会员卡模板 创建
    public function actionCardTemplateCreate()
    {
        $result = MemberCardService::service()->cardTemplateCreate($this->request_params);

        return PsCommon::responseSuccess($result);
    }

    // 上传图片
    public function actionCreateImg()
    {
        $result = MemberCardService::service()->createImg($this->request_params);

        return PsCommon::responseSuccess($result);
    }
}