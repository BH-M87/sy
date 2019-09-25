<?php
/**
 * 支付宝商户会员卡相关
 * 第一步：上传相关图片到支付宝（先上传到自己的服务器）
 * 第二步：创建会员卡模板 拿到模板ID 
 * 第三步：会员卡开卡表单模板配置
 * 第四步：获取会员卡领卡投放链接（小程序调首页接口 返回投放链接地址）
 * 第五步：小程序授权成功调用会员卡卡开接口 开卡成功调卡详情接口 获取pass_id
 */

namespace service\alipay;

use Yii;
use yii\web\Response;
use yii\helpers\FileHelper;

use common\core\F;
use common\core\PsCommon;

use service\BaseService;
use service\alipay\AlipayBillService;

use app\models\PsAppUser;
use app\models\PsAlipayCardRecord;

class MemberCardService extends BaseService
{
    //申请单前缀
    const BIZ_NO_PREFIX = 'lyl';

    public $logo_id = 'drORonD9SzuYD4pal4WdHAAAACMAAQED'; // 上传到支付宝的logo图片地址
    public $background_id = 'grWetZzlS2moPBbn6whyYQAAACMAAQED'; // 上传到支付宝的背景图片地址
    public $template_id = "20190423000000001526846000300139"; // 邻易联小程序 支付宝的会员卡模板ID
    public $door_template_id = "20190513000000001581246000300134"; // 门禁
    public $edoor_template_id = "20190606000000001671267000300130"; // 南京门禁
    public $zlz_template_id = "20190705000000001740775000300132"; // 浙里住小程序
    public $zjy_template_id = "20190705000000001748935000300133"; // 筑家易小程序
    public $fczl_template_id = "20190718000000001757875000300137"; // 富春智联小程序
    public $small_url;

    //获取阿里实例
    public function getAliService($type = 'fczl')
    {
        $this->small_url = 'alipays://platformapi/startapp?appId='.Yii::$app->params['fczl_app_id'].'&pages/homePage/homePage/homePage';

        switch ($type){
            case 'edoor'://筑家易智能门禁
                $this->template_id = $this->door_template_id;
                $alipayPublicKey = file_get_contents(Yii::$app->params['edoor_alipay_public_key_file']);
                $rsaPrivateKey = file_get_contents(Yii::$app->params['edoor_rsa_private_key_file']);
                $alipayLifeService = new IsvLifeService(Yii::$app->params['edoor_app_id'], null, null, $alipayPublicKey, $rsaPrivateKey);
                break;
            case 'fczl':
                $this->template_id = $this->fczl_template_id;
                $alipayPublicKey = file_get_contents(Yii::$app->params['fczl_alipay_public_key_file']);
                $rsaPrivateKey = file_get_contents(Yii::$app->params['fczl_rsa_private_key_file']);
                $alipayLifeService = new IsvLifeService(Yii::$app->params['fczl_app_id'], null, null, $alipayPublicKey, $rsaPrivateKey);
                break;
            default://邻易联
                $this->template_id = $this->fczl_template_id;
                $alipayPublicKey = file_get_contents(Yii::$app->params['fczl_alipay_public_key_file']);
                $rsaPrivateKey = file_get_contents(Yii::$app->params['fczl_rsa_private_key_file']);
                $alipayLifeService = new IsvLifeService(Yii::$app->params['fczl_app_id'], null, null, $alipayPublicKey, $rsaPrivateKey);
        }

        return $alipayLifeService;
    }

    // 添加图片
    public function createImg($param)
    {
        $alipayLifeService = $this->getAliService($param['type']);
        if (is_object($alipayLifeService) === false) {
            return $alipayLifeService;
        }

        $img_type = explode('.', $param['local_url']);
        $imgPara['image_type'] = $img_type[1];
        $imgPara['image_name'] = uniqid();
        $img_url = "@" . F::originalImage() . $param['local_url'];
        $imgPara['image_content'] = $img_url;

        $imgResult = $alipayLifeService->createImg($imgPara);
      
        if ($imgResult['code'] == 10000) {
            return $imgResult['image_id'];
        } else {
            return "图片错误:" . $imgResult['sub_msg'];
        }
    }

    // 会员卡模板 创建
    public function cardTemplateCreate($param)
    {
        $alipayLifeService = $this->getAliService($param['type']);
        if (is_object($alipayLifeService) === false) {
            return $alipayLifeService;
        }
        // 模板配置参数
        $reqArr = [
            'request_id' => time().rand(10000, 100000),
            'card_type' => 'OUT_MEMBER_CARD',
            'biz_no_suffix_len' => '10',
            'write_off_type' => 'none',
            'template_style_info' => [
                'card_show_name' => '社区一卡通',
                'logo_id' => $this->logo_id,
                'background_id' => $this->background_id,
                'bg_color' => 'rgb(255,168,51)'
            ],
            'column_info_list' => [[
                'code' => 'notice',
                'operate_type' => 'openWeb',
                'title' => '小区公告',
                'value' => '点击查看',
                'more_info' => ['url' => $this->small_url]
            ], [
                'code' => 'pay',
                'operate_type' => 'openWeb',
                'title' => '物业缴费',
                'value' => '点击查看',
                'more_info' => ['url' => $this->small_url]
            ], [
                'code' => 'repair',
                'operate_type' => 'openWeb',
                'title' => '报事报修',
                'value' => '点击查看',
                'more_info' => ['url' => $this->small_url]
            ], [
                'code' => 'complaint',
                'operate_type' => 'openWeb',
                'title' => '投诉建议',
                'value' => '点击查看',
                'more_info' => ['url' => $this->small_url]
            ], [
                'code' => 'convention',
                'operate_type' => 'openWeb',
                'title' => '邻里公约',
                'value' => '点击查看',
                'more_info' => ['url' => $this->small_url]
            ], [
                'code' => 'housekeeper',
                'operate_type' => 'openWeb',
                'title' => '物业管家',
                'value' => '点击查看',
                'more_info' => ['url' => $this->small_url]
            ]],
            'field_rule_list' => [[
                'field_name' => 'Balance',
                'rule_name' => 'ASSIGN_FROM_REQUEST',
                'rule_value' => 'Balance'
            ]],
            'card_action_list' => [[
                'code' => 'door',
                'text' => '去开门',
                'url_type' => 'miniAppUrl',
                'mini_app_url' => ['mini_app_id' => Yii::$app->params['door_app_id'], 'display_on_list' => true]
            ], [
                'code' => 'small',
                'text' => '社区服务',
                'url_type' => 'miniAppUrl',
                'mini_app_url' => ['mini_app_id' => Yii::$app->params['small_app_id'], 'display_on_list' => true]
            ]]
        ];
        // 添加模板
        $result = $alipayLifeService->cardTemplateCreate($reqArr);
        return $result;
    }

    // 会员卡 开卡 表单模板配置
    public function cardFormtemplateSet($param)
    {
        $alipayLifeService = $this->getAliService($param['type']);
        if (is_object($alipayLifeService) === false) {
            return $alipayLifeService;
        }
  
        $reqArr = [
            'template_id' => $this->template_id,
            'fields' =>  [
                // 设置授权必填参数 手机号、性别、姓名
                'required' => ['common_fields' => ['OPEN_FORM_FIELD_MOBILE', 'OPEN_FORM_FIELD_GENDER', 'OPEN_FORM_FIELD_NAME']]
            ]
        ];
 
        $result = $alipayLifeService->cardFormtemplateSet($reqArr);
        return $result;
    }

    // 获取会员卡领卡投放链接
    public function cardActivateurlApply($param)
    {
        $alipayLifeService = $this->getAliService($param['type']);
        if (is_object($alipayLifeService) === false) {
            return $alipayLifeService;
        }

        $reqArr = [
            'template_id' => $this->template_id,
        ];

        $result = $alipayLifeService->cardActivateurlApply($reqArr);
        $result['apply_card_url'] = URLDecode($result['apply_card_url']); // 需要解码 小程序里才可以用
        return $result;
    }

    // 会员卡 开卡
    public function cardOpen($param)
    {
        $user = PsAppUser::find()->select('id, channel_user_id, authtoken')->where(['id' => $param['user_id']])->asArray()->one();
        $authtoken = $user['authtoken'];
        if (empty($user)) {
            return $this->failed('用户不存在！');
        }

        $system_type = !empty($param['system_type']) ? $param['system_type'] : 'fczl';
        $alipayLifeService = $this->getAliService($system_type);
        if (is_object($alipayLifeService) === false) {
            return $alipayLifeService;
        }

        // 开卡
        $reqArr = [
            'out_serial_no' => time().rand(10000, 100000),
            'card_template_id' => $this->template_id,
            'card_user_info' => [
                'user_uni_id' => $user['channel_user_id'],
                'user_uni_id_type' => 'UID',
            ],
            'card_ext_info' => [
                'external_card_no' => '11', // 外部会员卡 区分不同的商户 不同的系统需要修改不同的值会员卡号biz_card_no才会变
                'open_date' => date('Y-m-d H:i:s', time()),
                'valid_date' => '3040-12-12 23:59:59',
            ]
        ];

        $result = $alipayLifeService->cardOpen($reqArr, $authtoken);
        // 开卡记录
        $record = new PsAlipayCardRecord();

        $record->app_user_id = $user['id'];
        $record->template_id = $this->template_id;
        $record->authtoken = $authtoken;
        $record->biz_card_no = $result['card_info']['biz_card_no'];;
        $record->channel_user_id = $user['channel_user_id'];
        $record->code = $result['code'];
        $record->sub_msg = $result['sub_msg'];
        $record->create_at = time();
        $record->save();

        if ($result['code'] == 10000) { // 开卡成功 调用卡详情接口 拿到pass_id小程序跳转卡详情用
            $user['biz_card_no'] = $result['card_info']['biz_card_no'];
            $user['type'] = $param['type'];

            return $this->cardQuery($user);
        } else {
            return $this->failed('开卡失败-'.$result['sub_msg']);
        }
    }

    // 会员卡 查询
    public function cardQuery($user)
    {
        $type = !empty($user['type']) ? $user['type'] : 'fczl';
        $alipayLifeService = $this->getAliService($type);
        if (is_object($alipayLifeService) === false) {
            return $alipayLifeService;
        }

        $reqArr = [
            'target_card_no_type' => 'BIZ_CARD',
            'target_card_no' => !empty($user['biz_card_no']) ? $user['biz_card_no'] : '123',
            'card_user_info' => [
                'user_uni_id' => $user['channel_user_id'],
                'user_uni_id_type' => 'UID',
            ],
        ];

        $result = $alipayLifeService->cardQuery($reqArr);

        if ($result['code'] == '10000') {
            if (!empty($result['pass_id'])) {
                PsAppUser::updateAll(['biz_card_no' => $user['biz_card_no']], ['id' => $user['id']]);
            } else {
                PsAppUser::updateAll(['biz_card_no' => ''], ['id' => $user['id']]);
            }
            
            return $this->success(['pass_id' => $result['pass_id']]);
        } else {
            //TODO 20190531 edit 响应空
            return $this->success(['pass_id' => ""]);
        }
    }

    // 查询用户提交的会员卡表单信息
    public function cardActivateformQuery()
    {
        $alipayLifeService = $this->getAliService();
        if (is_object($alipayLifeService) === false) {
            return $alipayLifeService;
        }

        $reqArr = [
            'biz_type' => 'MEMBER_CARD',
            'template_id' => $this->template_id,
            'request_id' => '2017021929993993992839493394'
        ];
 
        $result = $alipayLifeService->cardActivateformQuery($reqArr);
        return $result;
    }

    // 会员卡 更新
    public function cardUpdate()
    {
        $alipayLifeService = $this->getAliService();
        if (is_object($alipayLifeService) === false) {
            return $alipayLifeService;
        }

        $reqArr = [
            'target_card_no' => '',
            'target_card_no_type' => 'BIZ_CARD',
            'occur_time' => date('Y-m-d H:i:s', time()),
            'card_info' => [
                'open_date' => date('Y-m-d H:i:s', time()),
                'valid_date' => '3020-12-12 23:59:59',
            ]
        ];
 
        $result = $alipayLifeService->cardUpdate($reqArr);
        return $result;
    }

    // 会员卡 删卡
    public function cardDelete()
    {
        $alipayLifeService = $this->getAliService();
        if (is_object($alipayLifeService) === false) {
            return $alipayLifeService;
        }

        $reqArr = [
            'template_id' => $this->template_id
        ];

        $result = $alipayLifeService->cardDelete($reqArr);
        return $result;
    }

    // 会员卡模板 修改
    public function cardTemplateModify()
    {
        $alipayLifeService = $this->getAliService();
        if (is_object($alipayLifeService) === false) {
            return $alipayLifeService;
        }

        $reqArr = [
            'request_id' => time().rand(10000, 100000),
            'template_id' => $this->template_id,
            'write_off_type' => 'none',
            'template_style_info' => [
                'card_show_name' => '社区一卡通',
                'logo_id' => $this->logo_id,
                'background_id' => $this->background_id,
                'bg_color' => 'rgb(255,168,51)'
            ],
            'column_info_list' => [[
                'code' => 'notice',
                'operate_type' => 'openWeb',
                'title' => '小区公告',
                'value' => '点击查看',
                'more_info' => ['url' => $this->small_url]
            ], [
                'code' => 'pay',
                'operate_type' => 'openWeb',
                'title' => '物业缴费',
                'value' => '点击查看',
                'more_info' => ['url' => $this->small_url]
            ], [
                'code' => 'repair',
                'operate_type' => 'openWeb',
                'title' => '报事报修',
                'value' => '点击查看',
                'more_info' => ['url' => $this->small_url]
            ], [
                'code' => 'complaint',
                'operate_type' => 'openWeb',
                'title' => '投诉建议',
                'value' => '点击查看',
                'more_info' => ['url' => $this->small_url]
            ], [
                'code' => 'convention',
                'operate_type' => 'openWeb',
                'title' => '邻里公约',
                'value' => '点击查看',
                'more_info' => ['url' => $this->small_url]
            ], [
                'code' => 'housekeeper',
                'operate_type' => 'openWeb',
                'title' => '物业管家',
                'value' => '点击查看',
                'more_info' => ['url' => $this->small_url]
            ]],
            'field_rule_list' => [[
                'field_name' => 'Balance',
                'rule_name' => 'ASSIGN_FROM_REQUEST',
                'rule_value' => 'Balance'
            ]],
            'card_action_list' => [[
                'code' => 'door',
                'text' => '去开门',
                'url_type' => 'miniAppUrl',
                'mini_app_url' => ['mini_app_id' => Yii::$app->params['edoor_app_id'], 'display_on_list' => true]
            ], [
                'code' => 'small',
                'text' => '社区服务',
                'url_type' => 'miniAppUrl',
                'mini_app_url' => ['mini_app_id' => Yii::$app->params['fczl_app_id'], 'display_on_list' => true]
            ]]
        ];

        $result = $alipayLifeService->cardTemplateModify($reqArr);
        return $result;
    }

    // 会员卡模板 查询
    public function cardTemplateQuery()
    {
        $alipayLifeService = $this->getAliService();
        if (is_object($alipayLifeService) === false) {
            return $alipayLifeService;
        }

        $reqArr = [
            'template_id' => $this->template_id
        ];

        $result = $alipayLifeService->cardTemplateQuery($reqArr);
        return $result;
    }

    private function _writeLog($error_msg, $data)
    {
        $html = " \r\n";
        $html .= "请求时间:" . date('YmdHis') . "  请求结果:" . $error_msg . "\r\n";
        $html .= "请求数据:" . json_encode($data) . "\r\n";
        $file_name = date("Ymd") . '.txt';
        $savePath = Yii::$app->basePath . '/runtime/interface_log/';
        if (!file_exists($savePath)) {
            FileHelper::createDirectory($savePath, 0777, true);
//            mkdir($savePath,0777,true);
        }
        if (file_exists($savePath . $file_name)) {
            file_put_contents($savePath . $file_name, $html, FILE_APPEND);
        } else {
            file_put_contents($savePath . $file_name, $html);
        }
    }

    private function _response($data, $status, $msg = '')
    {
        if ($status == 'success') {
            $msg = $status;
        }

        $this->_writeLog($msg, $data);
    }

    /**
     *  获取小程序二维码
     * @param $params   :type:small(邻易联)，door(筑家易智能门禁),edoor(筑家e门禁)；url_param:小程序路由地址；query_param小程序参数（x=1）
     * @return IsvLifeService|array|bool    qr_code_url：二维码图片链接地址。
     */
    public function getQrcode($params)
    {
        $alipayLifeService = $this->getAliService($params['type']);
        if (is_object($alipayLifeService) === false) {
            return $alipayLifeService;
        }
        $reqArr = [
            'url_param' => $params['url_param'],
            'query_param' => $params['query_param'],
            'describe' => $params['describe']
        ];

        $result = $alipayLifeService->smallQrcode($reqArr);
        return $result;
    }
}
