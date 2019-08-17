<?php
/**
 * Created by PhpStorm.
 * User: ZQ
 * Date: 2019-4-28
 * For: 企业内部应用
 * Time: 16:34
 */
namespace app\modules\ding_property_app\services;

use app\models\PsPropertyCompany;
use app\models\PsUser;
use app\models\StCorp;
use app\models\StCorpAgent;
use app\models\StCorpTicket;
use app\models\StCorpToken;
use app\models\StCorpUser;
use app\modules\ding_property_app\company_jdk\api\Auth;
use app\modules\ding_property_app\company_jdk\api\Message;
use app\modules\ding_property_app\company_jdk\api\User;
use app\modules\ding_property_app\company_jdk\util\Client;
use service\BaseService;
use Yii;


class DingCompanyService extends BaseService  {
    private $ticket;

    /**
     * 获取企业的授权token
     * @param $corpId
     * @return string
     */
    public function getAccessToken($corpId,$agentId)
    {
        //查询授权码是否已过期
        $corpToken = StCorpToken::find()
            ->where(['corp_id' => $corpId])
            ->asArray()
            ->one();
        //未过期
        if (!empty($corpToken) && $corpToken['expires_in'] > time()) {
            return $corpToken['access_token'];
        } else {
            //获取token
            $auth = new Auth();
            $agent = StCorpAgent::find()->where(['corp_id'=>$corpId,'agent_id'=>$agentId])->asArray()->one();
            $accessToken = $auth->getAccessToken($agent['app_key'],$agent['app_secret']);
            //保存token值
            $setRe = $this->setAccessToken($corpId, $accessToken);
            if (!$setRe) {
                //Yii::info("token 获取后保存失败！token值：".$accessToken, 'auth');
                return false;

            }
            return $accessToken;
        }
    }
    /**
     * 保存token值
     * @param $corpId
     * @param $token
     */
    public function setAccessToken($corpId, $token)
    {
        $corpModel = StCorpToken::find()->where(['corp_id' => $corpId])->one();
        if (!$corpModel) {
            $corpModel = new StCorpToken();
            $corpModel->corp_id = $corpId;
        }
        $corpModel->access_token = $token;
        $corpModel->expires_in = time() + 7100;
        $corpModel->created_at = time();
        if (!$corpModel->save()) {
            Yii::info(json_encode($corpModel->getErrors()), 'auth');
        } else {
            return true;
        }
    }

    /**
     * 获取前端用的ticket
     * @param $corpId
     * @param $corp_access_token
     * @return mixed
     */
    public function getTicket($corpId,$corp_access_token){
        //查询ticket
        $ticketModel = StCorpTicket::find()
            ->where(['corp_id' => $corpId])
            ->asArray()
            ->one();
        //未过期
        if (!empty($ticketModel) && $ticketModel['expires_in'] > time()) {
            return $ticketModel['ticket'];
        } else {
            //获取新ticket
            $auth = new Auth();
            $ticketInfo = $auth->getTicket($corp_access_token);
            //保存ticket值
            $setRe = $this->setTicket($corpId, $ticketInfo);
            if (!$setRe) {
                Yii::info("ticket 保存失败！ticket:".$ticketInfo, 'auth');
                return false;
            }
            return $ticketInfo;
        }
    }

    /**
     * 保存ticket值
     * @param $corpId
     * @param $ticket
     * @return bool
     */
    public function setTicket($corpId, $ticket)
    {
        $ticketModel = StCorpTicket::find()->where(['corp_id' => $corpId])->one();
        if (!$ticketModel) {
            $ticketModel = new StCorpTicket();
            $ticketModel->corp_id = $corpId;
        }
        $ticketModel->ticket = $ticket;
        $ticketModel->expires_in = time() + 7100;
        $ticketModel->created_at = time();
        return $ticketModel->save();
    }

    /**
     * 获取js 鉴权配置文件
     * @param $corpId  企业id
     * @param $agentId 微应用id
     * @return array|string
     */
    public function getConfig($corpId, $agentId, $url)
    {
        //查询企业
        $corpModel = StCorp::find()->where(['corp_id' => $corpId])->one();
        if (!$corpModel) {
            return "企业信息不存在！";
        }
        $agentModel = StCorpAgent::findOne(['corp_id'=>$corpId,'agent_id'=>$agentId]);
        if (!$agentModel) {
            return "此应用不存在！";
        }
        //$agentId  = $agentModel->agent_id;
        $nonceStr = 'zhujia360zhujia';
        $timeStamp = time();

        if (empty($url)) {
            $url = Yii::$app->params['dingding_host']."?corp_id=".$corpId."&agent_id=".$agentId;//测试用
        }
        $url = urldecode($url);
        $corpAccessToken = $this->getAccessToken($corpId,$agentId);
        if (!$corpAccessToken) {
            return "企业token获取失败";
        }
        $ticket = $this->getTicket($corpId, $corpAccessToken);
        $signature = $this->sign($ticket, $nonceStr, $timeStamp, $url);

        $config = array(
            'url' => $url,
            'nonceStr' => $nonceStr,
            'agentId' => $agentId,
            'timeStamp' => $timeStamp,
            'corpId' => $corpId,
            'signature' => $signature);
        return $config;
    }

    /**
     * 获取签名
     * @param $ticket
     * @param $nonceStr
     * @param $timeStamp
     * @param $url
     * @return string
     */
    private function sign($ticket, $nonceStr, $timeStamp, $url)
    {
        $plain = 'jsapi_ticket=' . $ticket .
            '&noncestr=' . $nonceStr .
            '&timestamp=' . $timeStamp .
            '&url=' . $url;
        return sha1($plain);
    }

    /**
     * 获取用户详情信息
     * @param $corpId
     * @param $code
     * @param $userId
     * @return array|string
     */
    public function getUserInfo($corpId, $agentId,$code, $userId)
    {
        //查询企业
        $corpModel = StCorp::find()->where(['corp_id' => $corpId])->one();
        if (!$corpModel) {
            $result['data']['user_id'] = !empty($userId)?$userId:'';
            $result['data']['user_bind'] = 1;//用户未绑定
            return $result;
        }
        //通过 code 获取用户 userid 信息
        $user = new User();
        $accessToken = $this->getAccessToken($corpId,$agentId);
        if (!empty($code)) {
            $userInfo = $user->getUserInfo($accessToken, $code);
            $userInfoArr = json_decode($userInfo, true);
            if ($userInfoArr['errcode'] != 0) {
                return $userInfoArr['errmsg'];
            }
            $userId = $userInfoArr['userid'];
        }

        //根据 userid 获取用户详细信息
        $userInfo = $user->get($accessToken, $userId);
        $userInfoArr = json_decode($userInfo, true);
        if ($userInfoArr['errcode'] != 0) {
            return $userInfoArr['errmsg'];
        }

        //保存用户信息
        $addUser = $this->setUserInfo($corpId, $userInfoArr);
        if ($addUser === false) {
            return '员工信息保存失败！';
        }

        //请求物业后台获取token值
        $params['mobile']       = $userInfoArr['mobile'];
        $params['avatar'] = $addUser->avatar;
        $result = $this->getToken($params);
        //todo 后续需要优化返回的结果
        if(!empty($result['data'])) {
            $user_id = $result['data']['id'];
            $addUser->st_user_id = $user_id;
            $addUser->save();
            $result['data']['st_user_id'] = $user_id;
            unset($result['data']['user_id']);
            $result['data']['user_id'] = $addUser->user_id;
            $result['data']['avatar'] = $addUser->avatar;
        }
        return $result;
    }

    /**
     * 登录物业后台获取token
     * @param $request_params
     * @return mixed
     */
    public function getToken($request_params)
    {
        $mobile = !empty($request_params['mobile']) ? $request_params['mobile'] : '';
        $userId = !empty($request_params['user_id']) ? $request_params['user_id'] : '';
        $ding_icon = !empty($request_params['avatar']) ? $request_params['avatar'] : '';
        if ($mobile) {
            $userInfo = PsUser::find()->where(['mobile' => $mobile,'system_type'=>2])->one();
        } elseif ($userId) {
            $userInfo = PsUser::find()->where(['id' => $userId])->one();
        }

        if (empty($userInfo)) {
            $data['data'] = ['user_bind'=>3,'id'=>0];//此用户不存在，请联系管理员
            return $data;
        }

        $moblie = $userInfo->mobile;
        //登录，并返回用户信息
        $user = UserService::service()->getUserInfo($moblie);
        if(!empty($ding_icon)){
            $userInfo->ding_icon = $ding_icon;
            $userInfo->save();
        }
        $userInfo = $user;
        $token = '';
        if(is_array($user)){
            $user = UserService::service()->generalToken($user['id'],$moblie);
            $token = $user['token'];
            $userInfo['token'] = $token;
        }

        if ($token) {
            $userInfo['user_bind'] = 2;
            $data['data'] = $userInfo;
        } else {
            $data['data'] = ['user_bind'=>3,'id'=>0];//此用户不存在，请联系管理员
        }
        return $data;
    }

    /**
     * 保存用户信息
     * @param $corpId
     * @param $userInfo
     * @return bool|int|mixed
     */
    private function setUserInfo($corpId, $userInfo)
    {
        $user = StCorpUser::find()
            ->where(['user_id' => $userInfo['userid'], 'corp_id' => $corpId])
            ->one();
        if (!$user) {
            $user = new StCorpUser();
            $user->corp_id = $corpId;
            $user->user_id = $userInfo['userid'];
            $user->created_at = time();
            $user->st_user_id = 0;
        }
        $user->mobile = !empty($userInfo['mobile']) ? $userInfo['mobile'] : '18768177608';
        $user->name = !empty($userInfo['name']) ? $userInfo['name'] : "张强测试";
        $user->email = !empty($userInfo['email']) ? $userInfo['email']: '';
        $user->ding_id = !empty($userInfo['openId']) ? $userInfo['openId'] : 'zq10086';
        $user->is_admin = $userInfo['isAdmin'] ? 1 : 0;
        $user->is_boss = $userInfo['isBoss'] ? 1 : 0;
        $user->open_id = !empty($userInfo['openId']) ? $userInfo['openId'] : '';
        $user->avatar = !empty($userInfo['avatar']) ? $userInfo['avatar']: '';
        if ($user->save()) {
            return $user;
        }
        return false;
    }


    ##############################################发送钉钉消息##########################################################
    public function getAgentId($corpId)
    {
        $agent = StCorpAgent::find()->where(['corp_id' => $corpId])->asArray()->all();//暂时固定
        if (!$agent) {
            return '';
        }
        //先默认都是取第一个微应用，后续优化
        return $agent[0]['agent_id'];
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
        $agent_id = $this->getAgentId($corpId);
        if (!$agent_id) {
            return '企业应用信息有误，发送失败！';
        }
        //查找接收者userid
        $receiver = self::getUsersByIdList($receiveUserId, '|');
        if (!$receiver) {
            return '接收者不存在！';
        }
        $mes_data['touser'] = $receiver;
        $mes_data['agentid'] = $agent_id;
        if($type == 'text' || $type == 'action_card'){
            $mes_data['msgtype'] = 'text';
            $mes_data['text'] = ['content' => $msg];
        }
        if ($type == 'action_card') {
            /*$mes_data['msgtype'] = 'action_card';
            $single_url = '?id='.$detailId;//跳转到钉钉详情
            $mes_data['action_card'] = ['title'=>'工作通知','markdown' => $mes,'single_title'=>'查看详情','single_url'=>$single_url];*/
        }
        $access_token = self::getAccessToken($corpId,$agent_id);
        $mes = new Message();
        $result = $mes->send($access_token,$mes_data);
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
        $agent_id = $this->getAgentId($corpId);
        if (!$agent_id) {
            return '企业应用信息有误，发送失败！';
        }
        //企业token
        $access_token = self::getAccessToken($corpId,$agent_id);
        if (!$access_token) {
            return '发送失败！';
        }
        //查找发送者userid
        $creator = StCorpUser::find()
            ->select(['user_id'])
            ->where(['st_user_id' => $sendUserId])
            ->asArray()
            ->scalar();
        if (!$creator) {
            return '发送者不存在！';
        }
        //查找接收者userid
        $receiver = self::getUsersByIdList($receiveUserId);
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
        $result = self::getDingSign($url,$data,$public_data);
        $client = new Client();
        $res = $client->fetch($result['url'],$result['new_data'],'POST',["Content-Type: application/x-www-form-urlencoded; charset=utf-8"]);
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
            $list = StCorpUser::find()->select(['user_id'])->where(['in','st_user_id',$ids])->asArray()->column();
            $receiver = implode($split,$list);
        }else{
            $receiver = StCorpUser::find()->select(['user_id'])->where(['st_user_id' => $ids])->asArray()->scalar();
        }
        return $receiver;
    }

    /**
     * 获取钉钉签名
     * @param $url
     * @param $data
     * @param $public_data
     * @return mixed
     */
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
     * 获取钉钉表里面的User信息
     * @param $userId
     * @return false|null|string
     */
    public function getCorpIdByUserId($userId){
        if(is_array($userId)){
            $res = StCorpUser::find()->select(['corp_id'])->where(['st_user_id'=>$userId[0]])->asArray()->scalar();
        }else{
            $res = StCorpUser::find()->select(['corp_id'])->where(['st_user_id'=>$userId])->asArray()->scalar();
        }
        return $res;
    }
    ###########################发送钉钉消息#############################################################################

}