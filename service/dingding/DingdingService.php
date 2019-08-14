<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2017/12/5
 * Time: 16:34
 */
namespace services\dingding;

use dingding\jdk\api\Auth;
use dingding\jdk\api\ISVService;
use dingding\jdk\api\Message;
use dingding\jdk\api\User;
use dingding\jdk\util\Client;
use dingding\jdk\util\Log;
use service\BaseService;
use Yii;


class DingdingService extends BaseService  {
    private $suiteAccessToken;
    private $corpAccessToken;
    private $corpName;
    private $ticket;

    /**
     * 获取企业的js_api配置项
     * @param $cropId
     * @return array
     */
    public function getConfig($corpId,$appId = '',$url = '')
    {
        //return Auth::isvConfig($cropId);
        $appId = $appId ? $appId : Yii::$app->params['appid'];

        //判断企业是否存在
        $corpInfo = PsDingCorp::find()
            ->select(['id', 'corp_name'])
            ->where(['corpid' => $corpId])
            ->asArray()
            ->one();
        if (!$corpInfo) {
            return "企业未开通";
        }

        $res =  PsDingAgent::find()->where(['corpid'=>$corpId,'appid'=>$appId])->asArray()->one();
        if(!$res){
            return "获取失败";
        }

        $corpInfo = $res;
        $agentId = $corpInfo['agentid'];
        $nonceStr = 'abcdefg';
        $timeStamp = time();
        //$url    = Auth::curPageURL();  
        if ($url == "index") {
            $url = Yii::$app->params['dingding_host']."?corpid=".$corpInfo['corpid'];//测试用
        } else {
            $url = Yii::$app->params['dingding_host'];
        }

        $corp_access_token = self::getCorpAccessToken($corpInfo['corpid'],$appId);
        $ticket = self::getTicket($corpId,$corp_access_token);
        $signature = Auth::sign($ticket, $nonceStr, $timeStamp, $url);
        $arr = array();
        $arr['ticket'] = $ticket;
        $arr['nonceStr'] = $nonceStr;
        $arr['timeStamp'] = $timeStamp;
        $arr['url'] = $url;
        $arr['signature'] = $signature;
        \Yii::info("-----config-arr-----".json_encode($arr, JSON_UNESCAPED_UNICODE)."\r\n", 'api');
        $configTmp = array(
            'url' => $url,
            'nonceStr' => $nonceStr,
            'agentId' => $agentId,
            'timeStamp' => $timeStamp,
            'corpId' => $corpId,
            'suite_key' => Yii::$app->params['suite_key'],
            'signature' => $signature);
        $config['data'] = $configTmp;
        \Yii::info("-----config-tmp-----".json_encode($configTmp, JSON_UNESCAPED_UNICODE)."\r\n", 'api');
        return $config;
    }

    /**
     * 根据code获取用户token信息
     * @param $cropId
     * @param $code
     * @param $userId
     * @return array|string
     */
    public function getUserToken($userId)
    {
        $psUserId = 0;
        $dingUserModel = PsDingUser::find()
            ->where(['userid' => $userId])
            ->one();
        if ($dingUserModel) {
            $psUserId = $dingUserModel->ps_user_id;
            $phone = $dingUserModel->mobile;
            if(empty($phone)){
                return "用户未绑定";
            }
        } else {
            return "用户不存在";
        }
        //获取物业的token
        $params = ['user_id'   => $psUserId];
        Log::i(json_encode($params)."MSG-get-token-req");
        $result = $this->apiPost('/v1/dingding/get-token', $params, false, false, true);
        Log::i(json_encode($result)."MSG-get-token-res");
        if(!empty($result['data'])) {
            return $result;
        }else{
            return $result['msg'];
        }
    }
    /**
     * 根据code获取用户信息
     * @param $cropId
     * @param $code
     * @param $userId
     * @return array|string
     */
    public function getUserInfo($cropId, $code, $userId, $phone)
    {
        $re = $this->getCorpAccessToken($cropId);
        if (!$this->corpAccessToken) {
            return $re;
        }

        //获取用户的UserId信息
        $userSimpleInfo = User::getUserInfo($this->corpAccessToken, $code);
        $userInfo = json_decode($userSimpleInfo,true);
        if($userInfo['errcode'] == '0'){
            $userId = $userInfo['userid'];
        }

        //通过user_id 获取用户的通讯录详情
        $userInfo = User::getUserDetail($this->corpAccessToken, $userId);
        //var_dump($userInfo);die;

        $userInfoArr = json_decode($userInfo, true);
        $corp_name =  PsDingCorp::find()
            ->select(['corp_name'])
            ->where(['corpid' => $cropId])
            ->asArray()
            ->scalar();
        //需要申请才能拿到
        if ($userInfo) {
            $psUserId = 0;
            $dingUserModel = PsDingUser::find()
                ->where(['userid' => $userId])
                ->one();
            if ($dingUserModel) {
                $psUserId = $dingUserModel->ps_user_id;
                $phone = $dingUserModel->mobile;
                if(empty($phone)){
                    $result['data']['user_id'] = !empty($userId)?$userId:'';
                    $result['data']['user_bind'] = 1;//用户未绑定
                    return $result;
                }
            } else {
                $dingUserModel = new PsDingUser();
                $dingUserModel->userid = $userId;
                $dingUserModel->mobile = $phone;
                $dingUserModel->name = !empty($userInfoArr['name']) ? $userInfoArr['name'] : '';
                $dingUserModel->email = '';
                $dingUserModel->dingId = !empty($userInfoArr['dingId']) ? $userInfoArr['dingId'] : '';
                $dingUserModel->ps_user_id = $psUserId;
                $dingUserModel->corpid = $cropId;
                $dingUserModel->created_at = time();
                if (!$dingUserModel->save()) {
                    return "获取用户信息失败！";
                }
                $result['data']['user_id'] = !empty($userId)?$userId:'';
                $result['data']['user_bind'] = 1;//用户未绑定
                return $result;
            }

            //存入到物业后台
            $params = [
                'corpid'    => $cropId,
                'user_id'   => $psUserId,
                'corp_name'      => $corp_name,
                'phone'      => $phone,
                'ding_icon'=>!empty($userInfoArr['avatar'])?$userInfoArr['avatar']:''
            ];
            Log::i(json_encode($params)."MSG-get-user-info-req");

            $result = $this->apiPost('/v1/dingding/get-user-info', $params, false, false, true);
            Log::i(json_encode($result)."MSG-get-user-info-res");
            if(!empty($result['data'])) {
                if(!empty($result['data']['id'])){
                    $dingUserModel->ps_user_id = $result['data']['id'];
                    if (!$dingUserModel->save()) {
                        Log::i(json_encode($dingUserModel->getErrors())."MSG-SAVE-DING-USER");
                    }
                }
                $result['data']['user_id'] = $userId;
            }else{
                $result['data']['user_id'] = $userId;
                $result['data']['user_bind'] = 3;
            }
            return $result;
        } else {
            return "获取用户信息失败！";
        }
    }
    /**
     * 发送验证码
     */
    public function sendSms($phone)
    {
        $params = ['phone' => $phone];
        $result = $this->apiPost('/v1/bind/sms', $params, false, false, true);
        return $result;
    }
    /**
     * 根据code绑定用户
     * @param $cropId
     * @param $code
     * @param $userId
     * @return array|string
     */
    public function bindUser($cropId, $code, $userId, $phone, $phone_code)
    {
        Log::i("MSG-bind-user-req-1111");
        $re = $this->getCorpAccessToken($cropId);
        Log::i(json_encode($re)."MSG-bind-user-req");
        if (!$this->corpAccessToken) {
            return $re;
        }

        //if (!$userId && $code != 'test') {
            //获取用户的UserId信息
            $userSimpleInfo = User::getUserInfo($this->corpAccessToken, $code);
            $userInfo = json_decode($userSimpleInfo,true);
            if($userInfo['errcode'] == '0'){
                $userId = $userInfo['userid'];
            }
        //}

        //通过user_id 获取用户的通讯录详情
        $userInfo = User::getUserDetail($this->corpAccessToken, $userId);
        //var_dump($userInfo);die;

        $userInfoArr = json_decode($userInfo, true);
        $corp_name =  PsDingCorp::find()
            ->select(['corp_name'])
            ->where(['corpid' => $cropId])
            ->asArray()
            ->scalar();
        //需要申请才能拿到
        if ($userInfo) {
            $psUserId = 0;
            $dingUserModel = PsDingUser::find()
                ->where(['userid' => $userId])
                ->one();
            if ($dingUserModel) {
                PsDingUser::updateAll(['mobile'=>$phone],['userid'=>$userId]);
                $psUserId = $dingUserModel->ps_user_id;
            } else {
                $dingUserModel = new PsDingUser();
                $dingUserModel->userid = $userId;
                $dingUserModel->mobile = $phone;
                $dingUserModel->name = !empty($userInfoArr['name']) ? $userInfoArr['name'] : '';
                $dingUserModel->email = '';
                $dingUserModel->dingId = !empty($userInfoArr['dingId']) ? $userInfoArr['dingId'] : '';
                $dingUserModel->ps_user_id = $psUserId;
                $dingUserModel->corpid = $cropId;
                $dingUserModel->created_at = time();
                if (!$dingUserModel->save()) {
                    return "获取用户信息失败！";
                }
            }

            //存入到物业后台
            $params = [
                'corpid'    => $cropId,
                'user_id'   => $psUserId,
                'corp_name'      => $corp_name,
                'user_name'      => !empty($userInfoArr['name'])?$userInfoArr['name']:'',
                'phone' => $phone,
                'phone_code' => $phone_code,
                'ding_icon'=>!empty($userInfoArr['avatar'])?$userInfoArr['avatar']:''
            ];
            Log::i(json_encode($params)."MSG-bind-user-req");

            $result = $this->apiPost('/v1/dingding/bind-user', $params, false, false, true);
            Log::i(json_encode($result)."MSG-bind-user-res");
            if(!empty($result['data'])) {
                if(!empty($result['data']['id'])){
                    $dingUserModel->ps_user_id = $result['data']['id'];
                    if (!$dingUserModel->save()) {
                        Log::i(json_encode($dingUserModel->getErrors())."MSG-SAVE-DING-USER");
                    }
                }
                $result['data']['user_id'] = $userId;
            }
            return $result;
        } else {
            return "获取用户信息失败！";
        }
    }

    ###########################套件相关#################################
    /**
     * 创建或者更新套件
     * @param $suite_ticket
     * @param string $suite_key
     * @return array
     */
    public function setSuiteTicket($suite_ticket, $suite_key = '')
    {
        $suite_key = $suite_key ? $suite_key : Yii::$app->params['suite_key'];
        $model = PsDingSuite::findOne(['suite_key' => $suite_key]);
        //重新生成ticket
        if ($model) {
            $model->delete();
        }
        $model = new PsDingSuite();
        $model->suite_key    = $suite_key;
        $model->suite_ticket = $suite_ticket;
        $model->suite_secret = Yii::$app->params['suite_secret'];
        $model->suite_access_token = 'suite_access_token';
        $model->expires_in = 0;
        $model->created_at = time();
        if ($model->save()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取套件ticket
     * @param string $suite_key
     * @return mixed
     */
    public function getSuiteTicket($suite_key = ''){
        $params['suite_key'] = $suite_key ? $suite_key : Yii::$app->params['suite_key'];
        $res = $this->apiGet('/v1/dingding/get-suite-ticket', $params, false, false, true);
        return $res['data'];
    }

    /**
     * 获取套件的授权token
     * @param $suiteKey
     * @return array
     */
    public function getSuiteAccessToken($suiteKey = '')
    {
        $suiteAccessToken = '';
        $params['suite_key'] = $suiteKey ? $suiteKey : Yii::$app->params['suite_key'];
        file_put_contents("test-re.txt",$params['suite_key']."\r\n",FILE_APPEND);
        $suiteModel = PsDingSuite::find()->where(['suite_key' => $params['suite_key']])->one();
        if ($suiteModel) {
            file_put_contents("test-re.txt","aaaaa"."\r\n",FILE_APPEND);
            //先判断suite_access_token是否有效
            if($suiteModel->expires_in > time()){
                $suiteAccessToken = $suiteModel->suite_access_token;
            } else {
                //获取suite_access_token
                $suite_access_token = ISVService::getSuiteAccessToken($suiteModel['suite_ticket']);
                //存储suite_access_token
                $suiteModel->suite_access_token = $suite_access_token;
                $suiteModel->expires_in = time() +7100;
                if ($suiteModel->save()) {
                    $suiteAccessToken = $suite_access_token;
                }
            }
        }
        return $suiteAccessToken;
    }

    /**
     * 获取企业的授权token
     * @param $corpId
     * @return string
     */
    public function getCorpAccessToken($corpId,$app_id = '')
    {
        $appid = $app_id ? $app_id : '';
        $params['crop_id'] = $corpId;
        $params['appid'] = $appid;

        $corpInfo = PsDingCorp::find()
            ->select(['ps_ding_corp.corp_name','ps_ding_corp.permanent_code','ps_ding_corp.corpid',
                'corp_token.access_token as corp_access_token','corp_token.expires_in as corp_expires_in','corp_token.id as token_id'])
            ->leftJoin('ps_ding_corp_token corp_token', 'ps_ding_corp.corpid = corp_token.corpid')
            ->where(['ps_ding_corp.corpid' => $corpId])
            ->asArray()
            ->one();
        if (empty($corpInfo)) {
            return "企业信息不存在";
        }

        $this->corpName = $corpInfo['corp_name'];
        if ($corpInfo['corp_expires_in'] > time()){
            $this->corpAccessToken = $corpInfo['corp_access_token'];
        } else {
            //已经过期或不存在，重新再获取一次token
            $suite_access_token = self::getSuiteAccessToken();

            if($suite_access_token){
                //获取access_token
                $access_token = ISVService::getIsvCorpAccessToken($suite_access_token,$corpId,$corpInfo['permanent_code']);
                //存储access_token

                $info = PsDingCorpToken::findOne(['corpid' => $corpId]);
                if (!$info) {
                    $info = new PsDingCorpToken();
                    $info->corpid = $corpId;
                    $info->created_at = time();
                }
                $info->access_token = $access_token;
                $info->expires_in = time() +7100;
                $info->save();

                $this->corpAccessToken = $access_token;
            }
        }
        return $this->corpAccessToken;
    }

    /**
     * 获取企业永久授权码并保存到数据库
     * @param $suiteAccessToken
     * @param $tmpAuthCode
     * @return mixed
     */
    public function getPermanentCodeInfo($suiteAccessToken,$tmpAuthCode){
        if ($tmpAuthCode == "test") {
            $params['corp_name'] = '张强测试';
            $params['corp_id'] = 'ding3bf04f1f9db9192135c2f4657eb6378f';
            $params['permanent_code'] = '6vmirAmM0GNH7upvvBQuQEPTeDrKxQJldHuLb4OTpD20RD3imx5EWLXiZJ3-8hRe';
        } else {
            $params = ISVService::getPermanentCodeInfo($suiteAccessToken, $tmpAuthCode);
        }

        $corp = PsDingCorp::find()->where(['corpid' => $params['corp_id']])->one();
        if (!$corp) {
            $mod = new PsDingCorp();
            $mod->corpid = $params['corp_id'];
            $mod->corp_name = $params['corp_name'];
            $mod->permanent_code = $params['permanent_code'];
            $mod->created_at = time();
            $mod->save();
        } else {
            if ($params['permanent_code'] != $corp->permanent_code) {
                $corp->permanent_code = $params['permanent_code'];
                $corp->created_at = time();
                $corp->save();
            }
        }
        return $params;
    }

    /**
     * 获取企业相关信息并保存到数据库
     * @param $suiteAccessToken
     * @param $authCorpId
     * @param $permanetCode
     * @param $corpAccessToken
     * @return mixed
     */
    public function getAuthInfo($suiteAccessToken, $authCorpId, $permanetCode, $corpAccessToken){

        $params = ISVService::getAuthInfo($suiteAccessToken, $authCorpId, $permanetCode);
        $info = json_decode(json_encode($params), true);

        //$info = self::object2array($params);

        $corp = PsDingCorp::findOne(['corpid' => $authCorpId]);
        if($corp){
            $industry = $info['auth_corp_info']['industry'];//所属行业
            $corp_logo_url = $info['auth_corp_info']['corp_logo_url'];//企业logo
            if($industry || $corp_logo_url){
                $corp->industry = $industry;
                $corp->corp_logo_url = $corp_logo_url;
                $corp->save();
            }
            $token = PsDingCorpToken::findOne(['corpid' => $authCorpId]);
            if (!$token) {
                $mod = new PsDingCorpToken();
                $mod->corpid = $authCorpId;
                $mod->access_token = $corpAccessToken;
                $mod->expires_in = time() + 7100;
                $mod->created_at = time();
                $mod->save();
            } else {
                $token->access_token = $corpAccessToken;
                $token->expires_in = time() + 7100;
                $token->save();
            }

            foreach ($info['auth_info']['agent'] as $key => $value){
                $appid = $value['appid'];
                $agent = PsDingAgent::findOne(['corpid'=>$authCorpId,'appid'=>$appid]);
                if(!$agent){
                    $agent_mod = new PsDingAgent();
                    $agent_mod->corpid = $authCorpId;
                    $agent_mod->appid = $appid;
                    $agent_mod->agent_name = $value['agent_name'];
                    $agent_mod->agentid = $value['agentid'];
                    $agent_mod->logo_url = $value['logo_url'];
                    $agent_mod->created_at = time();
                    if (!$agent_mod->save()) {
                        file_put_contents("add-agent.txt",json_encode($agent_mod->getErrors()), FILE_APPEND);
                    }

                }else{
                    $agent->agent_name = $value['agent_name'];
                    $agent->agentid = $value['agentid'];
                    $agent->logo_url = $value['logo_url'];
                    $agent->created_at = time();
                    $agent->save();
                }
            }
        } else {
            return "该企业信息不存在";
        }
        return json_decode(json_encode($info));
    }

    /**
     * 获取前端用的ticket
     * @param $corpId
     * @param $corp_access_token
     * @return mixed
     */
    public function getTicket($corpId,$corp_access_token){
        $data['corp_id'] = $corpId;
        //$tic = $this->apiGet('/v1/dingding/get-ticket', $data, false, false, true);
        $res = PsDingCorpTicket::find()->where(['corpid'=>$corpId])->asArray()->one();
        if ($res) {
            if($res['expires_in'] > time()){
                $this->ticket = $res['ticket'];
            }else{
                $ticket = Auth::getTicket($corpId,$corp_access_token);
                $params['ticket'] = $ticket;
                $params['corp_id'] = $corpId;

                $data = PsDingCorpTicket::findOne(['corpid'=>$corpId]);
                if (!$data) {
                    $mod = new PsDingCorpTicket();
                    $mod->corpid = $corpId;
                    $mod->ticket = $ticket;
                    $mod->expires_in = time()+7100;
                    $mod->created_at =time();
                    $mod->save();
                } else{
                    $data->setAttributes(['ticket'=>$ticket,'expires_in'=>time()+7100]);
                    $data->save();
                }
                $this->ticket = $ticket;
            }
        } else {
            $ticket = Auth::getTicket($corpId,$corp_access_token);
            $params['ticket'] = $ticket;
            $params['corp_id'] = $corpId;

            //保存新的ticket
            $data = PsDingCorpTicket::findOne(['corpid'=>$corpId]);
            if (!$data) {
                $mod = new PsDingCorpTicket();
                $mod->corpid = $corpId;
                $mod->ticket = $ticket;
                $mod->expires_in = time()+7100;
                $mod->created_at =time();
                $mod->save();
            } else{
                $data->setAttributes(['ticket'=>$ticket,'expires_in'=>time()+7100]);
                $data->save();
            }
            $this->ticket = $ticket;
        }
        return $this->ticket;
    }

    /**
     * 初始化的信息
     * @param $tmpAuthCode
     * @param string $suiteKey
     */
    public function autoActivateSuite($tmpAuthCode,$suiteKey = ''){
        //持久化临时授权码
        $suiteAccessToken = DingdingService::service()->getSuiteAccessToken();
        Log::i("[Activate] getSuiteToken: " . $suiteAccessToken);
        //获取永久授权码以及corpid等信息，持久化，并激活临时授权码
        //$permanetCodeInfo = ISVService::getPermanentCodeInfo($suiteAccessToken, $tmpAuthCode);
        $permanetCodeInfo = self::getPermanentCodeInfo($suiteAccessToken,$tmpAuthCode);
        Log::i("[Activate] getPermanentCodeInfo: " . json_encode($permanetCodeInfo));

        $permanetCode = $permanetCodeInfo['permanent_code'];
        $authCorpId = $permanetCodeInfo['corp_id'];
        Log::i("[Activate] permanetCode: " . $permanetCode . ",  authCorpId: " . $authCorpId);

        /**
         * 获取企业access token
         */
        //$corpAccessToken = ISVService::getIsvCorpAccessToken($suiteAccessToken, $authCorpId, $permanetCode);
        $corpAccessToken = self::getCorpAccessToken($authCorpId);
        Log::i("[Activate] getCorpToken: " . $corpAccessToken);

        /**
         * 获取企业授权信息
         */
        //$res = ISVService::getAuthInfo($suiteAccessToken, $authCorpId, $permanetCode);
        $res = self::getAuthInfo($suiteAccessToken, $authCorpId, $permanetCode,$corpAccessToken);
        Log::i("[Activate] getAuthInfo: " . json_encode($res));
        self::check($res);

        /**
         * 激活套件
         */
        $res = ISVService::activeSuite($suiteAccessToken, $authCorpId, $permanetCode);
        Log::i("[activeSuite]: " . json_encode($res));
        self::check($res);
    }

    /**
     * 发送企业消息
     * @param $corpId
     * @param $userid
     * @param $agentid
     * @param $code
     * @param $mes
     * @return mixed
     */
    public function sendMesToDing($corpId, $userid,$agentid,$code,$mes){
        $mes_data['touser'] = $userid;
        $mes_data['agentid'] = $agentid;
        $mes_data['code'] = $code;
        $mes_data['msgtype'] = 'text';
        $mes_data['text'] = ['content' => $mes];
        $access_token = self::getCorpAccessToken($corpId);
        $result = Message::sendByCode($access_token,$mes_data);
        $return['errCode'] = $result->errcode;
        $return['errMsg'] = $result->errmsg;
        return $return;
    }

    public function getDingSign($url,$data,$public_data){
        ksort($data);
        ksort($public_data);
        $string = '';
        $datas = '';
        foreach($public_data as $k =>$v){
            if(!$datas){
                $datas .= $k.'='.urlencode($v);
            }else{
                $datas .= '&'.$k.'='.urlencode($v);
            }
        }
        foreach($data as $key =>$value){
            if(!$datas){
                $datas .= $key.'='.urlencode($value);
            }else{
                $datas .= '&'.$key.'='.urlencode($value);
            }
        }
        $new_data = array_merge($public_data,$data);
        ksort($new_data);
        foreach($new_data as $ke =>$val){
            $string .= $ke.$val;
        }
        $sign = bin2hex(md5($string));
        $return['url'] = $url."?".$datas."&sign=".$sign;
        $return['new_data'] = $new_data;
        $return['data'] = $data;
        return $return;
    }

    /**
     * 发送钉钉通知
     * @param $corpId
     * @param $receiveUserId
     * @param $msg
     * @param array $extend 扩展字段，用户做详情页跳转
     * @param string $type
     * @return bool|string
     */
    public function sendDingMes($corpId, $receiveUserId, $msg, $extend = [], $type='text'){
        $typeList = ['action_card','text'];//目前支持的类型
        if (!in_array($type, $typeList)) {
            return '通知类型错误！';
        }

        $agent = PsDingAgent::find()->where(['corpid' => $corpId, 'appid'=> Yii::$app->params['appid']])->asArray()->one();//暂时固定
        if (!$agent) {
            return '企业信息有误，发送失败！';
        }

        //查找接收者userid
        $receiver = self::getUsersByIdList($receiveUserId, '|');
        if (!$receiver) {
            return '接收者不存在！';
        }
        $mes_data['touser'] = $receiver;
        $mes_data['agentid'] = $agent['agentid'];
        if($type == 'text' || $type == 'action_card'){
            $mes_data['msgtype'] = 'text';
            $mes_data['text'] = ['content' => $msg];
        }
        if ($type == 'action_card') {
            /*$mes_data['msgtype'] = 'action_card';
            $single_url = '?id='.$detailId;//跳转到钉钉详情
            $mes_data['action_card'] = ['title'=>'工作通知','markdown' => $mes,'single_title'=>'查看详情','single_url'=>$single_url];*/
        }
        $access_token = self::getCorpAccessToken($corpId);
        $result = Message::send($access_token,$mes_data);
        if ($result->errcode) {
            return $result->errmsg;
        } else {
            return true;
        }
    }

    /**
     * 发送钉消息
     * @param $corpId
     * @param $sendUserId
     * @param $receiveUserId
     * @param $msg
     * @return array|string
     */
    public function sendDing($corpId, $sendUserId, $receiveUserId, $msg)
    {
        //企业token
        $access_token = DingdingService::service()->getCorpAccessToken($corpId);
        if (!$access_token) {
            return '发送失败！';
        }

        //查找发送者userid
        $creator = PsDingUser::find()
            ->select(['userid'])
            ->where(['ps_user_id' => $sendUserId])
            ->asArray()
            ->scalar();
        if (!$creator) {
            return '发送者不存在！';
        }
        Log::i("ding_create_access_token: " . $access_token);
        Log::i("ding_create_corpId: " . $corpId);
        Log::i("ding_create_receiveUserId: " . json_encode($receiveUserId));
        //查找接收者userid
        $receiver = self::getUsersByIdList($receiveUserId);
        Log::i("ding_create_receiver: " . $receiver);
        if (!$receiver) {
            return '接收者不存在！';
        }

        list($t1, $t2) = explode(' ', microtime());
        $time = (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);//获取毫秒级的时间戳

        $url = "https://eco.taobao.com/router/rest";
        $public_data = [
            'method'=>'dingtalk.corp.ding.create',
            'session'=> $access_token,
            'timestamp'=>date('Y-m-d H:i:s'),
            'v'=>'2.0',
            'simplify'=>true,
            'format'=>'json'
        ];
        $data = [
            'creator_userid'=>$creator,
            'receiver_userids'=>$receiver,
            'remind_type'=>1,
            'remind_time'=>$time,
            'text_content'=>$msg,
        ];
        Log::i("ding_create_data: " . json_encode($data));
        $result = DingdingService::service()->getDingSign($url,$data,$public_data);
        $client = new Client();
        $res = $client->fetch($result['url'],$result['new_data'],'POST',["Content-Type: application/x-www-form-urlencoded; charset=utf-8"]);
        Log::i("ding_create_res: " . json_encode($res));
        return $res;
    }

    //获取钉钉表里面的User信息
    public function getCorpIdByUserId($userId){
        if(is_array($userId)){
            $res = PsDingUser::find()->select(['corpid'])->where(['ps_user_id'=>$userId[0]])->asArray()->scalar();
        }else{
            $res = PsDingUser::find()->select(['corpid'])->where(['ps_user_id'=>$userId])->asArray()->scalar();
        }
        return $res;
    }

    /**
     * 根据id列表获取对应的钉钉userid合集字符串
     * @param $ids
     * @param string $split
     * @return false|null|string
     */
    public function getUsersByIdList($ids,$split = ','){
        if(is_array($ids)){
            $list = PsDingUser::find()->select(['userid'])->where(['in','ps_user_id',$ids])->asArray()->column();
            $receiver = implode($split,$list);
        }else{
            $receiver = PsDingUser::find()->select(['userid'])->where(['ps_user_id' => $ids])->asArray()->scalar();
        }
        return $receiver;
    }



}