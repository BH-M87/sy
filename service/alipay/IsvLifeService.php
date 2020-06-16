<?php
/**
 * 支付宝服务窗实例-生活号ISV代创建
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2017/6/13
 * Time: 10:04
 */
namespace service\alipay;
use common\core\ali\AopRedirect;
use Yii;

class IsvLifeService {
    //生活号名称
    private $_name;
    //生活号logo
    private $_logo;
    //生活号头图
    private $_head_image;
    //生活号简介
    private $_intro;
    //生活号上下架状态
    private $_status;
    //商户pid
    private $_merchat_pid;
    //isv app_id
    private $_isv_app_id;
    //isv token
    private $_isv_app_auth_token;
    //isv公钥
    private $_isv_alipay_public_key;
    //isv私钥
    private $_isv_private_key;
    //apo实例
    private $_aop;

    public function __construct($_isv_app_id, $_merchat_pid, $_isv_app_auth_token,$alipay_public_key, $private_key, $isCrontab = false)
    {
        $this->_merchat_pid           = $_merchat_pid;
        $this->_isv_app_id            = $_isv_app_id;
        $this->_isv_app_auth_token    = $_isv_app_auth_token;
        $this->_isv_alipay_public_key = $alipay_public_key;
        $this->_isv_private_key       = $private_key;

        $this->_aop = new AopRedirect();
        $this->_aop->setSignType("RSA2");
        $this->_aop->setIsCrontab($isCrontab);
        $this->_aop->appId              = $this->_isv_app_id;
        $this->_aop->alipayrsaPublicKey = $this->_isv_alipay_public_key;
        $this->_aop->rsaPrivateKey      = $this->_isv_private_key;
        $this->_aop->apiVersion = "1.0";
    }

    public function setName($name)
    {
        $this->_name = $name;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function setLogo($logo)
    {
        $this->_logo = $logo;
    }

    public function getLogo()
    {
        return $this->_logo;
    }

    public function setHeadImage($image)
    {
        $this->_head_image = $image;
    }

    public function getHeadImage()
    {
        return $this->_head_image;
    }

    public function setIntro($intro)
    {
        $this->_intro = $intro;
    }

    public function getIntro()
    {
        return $this->_intro;
    }

    public function setStatus($status)
    {
        $this->_status = $status;
    }

    public function getStatus()
    {
        return $this->_status;
    }

    public function setPrivateKey($privateKey)
    {
        $this->_private_key = $privateKey;
    }

    public function getPrivateKey()
    {
        return $this->_private_key;
    }

    public function setAlipayPublicKey($publicKey)
    {
        $this->_alipay_public_key = $publicKey;
    }

    public function getAlipayPublicKey()
    {
        return $this->_alipay_public_key;
    }

    public function activeDeveloper()
    {
        //TODO 生活号ISV，怎样激活开发者模式
    }

    public function create($reqArr)
    {
        $params = $reqArr;
        return $this->_aop->execute('alipay.open.public.life.agent.create', $params, null, $this->_isv_app_auth_token);
    }

    //申请上架
    public function applyOnline($reqArr)
    {
        $params = $reqArr;
        return $this->_aop->execute('alipay.open.public.life.aboard.apply', $params, null, $this->_isv_app_auth_token);
    }

    //申请下架
    public function deadLine($reqArr)
    {
        $params = $reqArr;
        return $this->_aop->execute('alipay.open.public.life.debark.apply', $params, null, $this->_isv_app_auth_token);
    }

    //菜单配置
    public function setMenu($buttons)
    {
        $biz = [
            "button" => $buttons,
            "type"   => 'icon',
        ];
        $params['biz_content'] = json_encode($biz);
        return $this->_aop->execute('alipay.open.public.menu.create', $params, null, $this->_isv_app_auth_token);
    }

    //菜单修改配置
    public function modifyMenu($buttons)
    {

        $biz = [
            "button" => $buttons,
            "type"   => 'icon',
        ];
        $params['biz_content'] = json_encode($biz);
        return $this->_aop->execute('alipay.open.public.menu.modify', $params , null, $this->_isv_app_auth_token);
    }

    //修改生活号基础信息
    public function modifyInfo($info)
    {
        $params['biz_content'] = json_encode($info, JSON_UNESCAPED_UNICODE);
        return $this->_aop->execute('alipay.open.public.info.modify', $params, null, $this->_isv_app_auth_token);
    }
    //获取生活号基础信息
    public function lifeInfo($info)
    {
        $params['biz_content'] = json_encode($info);
        return $this->_aop->execute('alipay.open.public.info.query', $params);
    }

    //设置模板行业
    public function setMessageTemplateIndustry($data)
    {
        $params['biz_content'] = json_encode($data);
        return $this->_aop->execute('alipay.open.public.template.message.industry.modify', $params, null, $this->_isv_app_auth_token);
    }

    //查询用户是否关注生活号
    public function getIsAttentionLife($data)
    {
        $params['biz_content'] = json_encode($data);
        return $this->_aop->execute('alipay.open.public.user.follow.query', $params, null, $this->_isv_app_auth_token);
    }

    //扩展区配置
    public function setExtend()
    {
        //TODO
    }

    //生成外部链接地址
    public function getExternalLink()
    {
        //TODO
    }

    //领取模板
    public function getMessageTemplate($data)
    {
        $params['biz_content'] = json_encode($data);
        return $this->_aop->execute('alipay.open.public.template.message.get', $params, null, $this->_isv_app_auth_token);
    }
    //添加图片
    public function createImg($data)
    {
        return $this->_aop->execute('alipay.offline.material.image.upload', $data, null, $this->_isv_app_auth_token);
    }
    //生活号广告位查询
    public function selAdvert($data)
    {
        $params['biz_content'] = json_encode($data,JSON_UNESCAPED_SLASHES);
        return $this->_aop->execute('alipay.open.public.advert.batchquery', $params, null, $this->_isv_app_auth_token);
    }
    //生活号广告位删除
    public function delAdvert($data)
    {
        $params['biz_content'] = json_encode($data,JSON_UNESCAPED_SLASHES);
        return $this->_aop->execute('alipay.open.public.advert.delete', $params, null, $this->_isv_app_auth_token);
    }
    //生活号广告位添加
    public function createAdvert($data)
    {
        $params['biz_content'] = json_encode($data);
        return $this->_aop->execute('alipay.open.public.advert.create', $params, null, $this->_isv_app_auth_token);
    }
    //生活号广告位修改
    public function updateAdvert($data)
    {
        $params['biz_content'] = json_encode($data);
        return $this->_aop->execute('alipay.open.public.advert.modify', $params, null, $this->_isv_app_auth_token);
    }

    //报事报修推送消息
    public function sendRepairMsg($data)
    {
        $params['biz_content'] = json_encode($data);
        return $this->_aop->execute('alipay.open.app.mini.templatemessage.send', $params, null, $this->_isv_app_auth_token);
    }
    //生成二维码图片
    public function getCodeImg($data = [])
    {
        if (empty($data)) {
            $biz = [
                "code_info" => [
                    'goto_url' => '',
                ],
                "code_type" => "PERM",
                "expire_second" => "",
                "show_logo" => "Y"
            ];
            $params['biz_content'] = json_encode($biz);
        } else {
            $params['biz_content'] = json_encode($data);
        }

        return $this->_aop->execute('alipay.open.public.qrcode.create', $params, null, $this->_isv_app_auth_token);
    }

    //生成短链
    public function getShortLink($scene_id='life_service_1')
    {
        $biz = [
            "scene_id" => $scene_id,
        ];
        $params['biz_content'] = json_encode($biz);
        return $this->_aop->execute('alipay.open.public.shortlink.create', $params);
    }

    //获取用户列表
    public function getUsers($nextUserId=0)
    {
        $biz['next_user_id'] = $nextUserId;
        $params['biz_content'] = json_encode($biz);
        return $this->_aop->execute('alipay.open.public.follow.batchquery', $params, null, $this->_isv_app_auth_token);
    }

    //图文消息发送
    public function sendMsg($data)
    {
        $params['biz_content'] = json_encode($data);
        return $this->_aop->execute('alipay.open.public.message.total.send', $params , null, $this->_isv_app_auth_token);
    }

    //异步单发消息
    public function sendCustomMsg($data)
    {
        $params['biz_content'] = json_encode($data);
        return $this->_aop->execute('alipay.open.public.message.custom.send', $params);
    }

    //生活号模板消息发送
    public function sendTemplateMsg($data)
    {
        $params['biz_content'] = json_encode($data);
        return $this->_aop->execute('alipay.open.public.message.single.send', $params , null, $this->_isv_app_auth_token);
    }

    //生活号基础资料修改
    public function baseConfig($data)
    {
        $params['biz_content'] = json_encode($data);
        return $this->_aop->execute('alipay.open.public.info.modify', $params , null, $this->_isv_app_auth_token);
    }

    //生活号绑定收款账号
    public function bindPayeeAccount($data)
    {
        $params['biz_content'] = json_encode($data);
        return $this->_aop->execute('alipay.open.public.payee.bind.create', $params , null, $this->_isv_app_auth_token);
    }


    //生活号添加图文内容
    public function messageContentAdd($data)
    {
        $params['biz_content'] = json_encode($data);
        return $this->_aop->execute('alipay.open.public.message.content.create', $params , null, $this->_isv_app_auth_token);
    }

    // 会员卡模板 创建
    public function cardTemplateCreate($data)
    {
        $params['biz_content'] = json_encode($data);

        return $this->_aop->execute('alipay.marketing.card.template.create', $params , null, $this->_isv_app_auth_token);
    }

    // 会员卡模板 查询
    public function cardTemplateQuery($data)
    {
        $params['biz_content'] = json_encode($data);

        return $this->_aop->execute('alipay.marketing.card.template.query', $params , null, $this->_isv_app_auth_token);
    }

    // 会员卡模板 修改
    public function cardTemplateModify($data)
    {
        $params['biz_content'] = json_encode($data);

        return $this->_aop->execute('alipay.marketing.card.template.modify', $params , null, $this->_isv_app_auth_token);
    }

    // 会员卡 开卡 表单模板配置
    public function cardFormtemplateSet($data)
    {
        $params['biz_content'] = json_encode($data);

        return $this->_aop->execute('alipay.marketing.card.formtemplate.set', $params , null, $this->_isv_app_auth_token);
    }

    // 获取会员卡领卡投放链接
    public function cardActivateurlApply($data)
    {
        $params['biz_content'] = json_encode($data);

        return $this->_aop->execute('alipay.marketing.card.activateurl.apply', $params , null, $this->_isv_app_auth_token);
    }

    // 查询用户提交的会员卡表单信息
    public function cardActivateformQuery($data)
    {
        $params['biz_content'] = json_encode($data);

        return $this->_aop->execute('alipay.marketing.card.activateform.query', $params , null, $this->_isv_app_auth_token);
    }

    // 会员卡 开卡
    public function cardOpen($data, $authToken)
    {
        $params['biz_content'] = json_encode($data);

        return $this->_aop->execute('alipay.marketing.card.open', $params , $authToken, $this->_isv_app_auth_token);
    }

    // 会员卡 更新
    public function cardUpdate($data)
    {
        $params['biz_content'] = json_encode($data);

        return $this->_aop->execute('alipay.marketing.card.update', $params , null, $this->_isv_app_auth_token);
    }

    // 会员卡 查询
    public function cardQuery($data)
    {
        $params['biz_content'] = json_encode($data);

        return $this->_aop->execute('alipay.marketing.card.query', $params , null, $this->_isv_app_auth_token);
    }

    // 会员卡 删卡
    public function cardDelete($data)
    {
        $params['biz_content'] = json_encode($data);

        return $this->_aop->execute('alipay.marketing.card.delete', $params , null, $this->_isv_app_auth_token);
    }

    //小程序二维码
    public function smallQrcode($data)
    {
        $params['biz_content'] = json_encode($data);

        return $this->_aop->execute('alipay.open.app.qrcode.create', $params , null, $this->_isv_app_auth_token);
    }

    //小程序模板消息
    public function smallPushMsg($data)
    {
        $params['biz_content'] = json_encode($data);

        return $this->_aop->execute('alipay.open.app.mini.templatemessage.send', $params , null, $this->_isv_app_auth_token);
    }

    //生活号服务获取
    public function getServices()
    {
        //TODO
    }


    /**
     * 获取支付宝生活号授权token值
     * @param $authCode
     * @return array|bool|\common\core\jdk\提交表单HTML文本|mixed|\SimpleXMLElement|string
     */
    public function getAccessToken($authCode)
    {
        $params['code'] = $authCode;
        $params['grant_type'] = 'authorization_code';
        return $this->_aop->execute('alipay.system.oauth.token', $params);
    }

    /**
     * 获取支付宝授权用户信息
     * @param $accessToken
     * @return array|bool|\common\core\jdk\提交表单HTML文本|mixed|\SimpleXMLElement|string
     */
    public function getOauthInfo($accessToken)
    {
    }

    /**
     * 支付回调校验
     * @param $data
     * @return bool
     */
    public function notifyCheck($data)
    {
    }

}

