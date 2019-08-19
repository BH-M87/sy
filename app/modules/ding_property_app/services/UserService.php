<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2017/6/28
 * Time: 10:37
 */
namespace app\modules\ding_property_app\services;
use app\models\PsCommunityModel;
use app\models\PsGroups;
use app\models\PsLoginToken;
use app\models\PsMenus;
use app\models\PsRepair;
use app\models\PsUser;
use service\BaseService;
use service\manage\CommunityService;
use Yii;

class UserService extends BaseService
{

    /**
     * 获取用户信息
     * @param $phone
     * @return array|string
     */
    public function getUserInfo($phone)
    {
        return $this->getUserByPhone($phone);
    }

    /**
     * 根据手机号查询用户信息
     * @update by shenyang v4.6，用户禁用的时候，清理缓存比较麻烦，所以这个该方法的不加缓存
     * @param $phone
     * @return string
     */
    public function getUserByPhone($phone)
    {
        $userInfo = PsUser::find()
            ->select(['id','username','mobile','truename','level','is_enable','group_id','property_company_id','ding_icon'])
            ->where(['mobile'=>$phone,'system_type'=>2,'is_enable'=>1])
            ->asArray()->one();
        if (!$userInfo) {
            return "该用户不存在！";
        }

        //该用户是否已绑定了小区
        $userInfo['groupname'] = "";

        //查询用户所在的组
        $userGroup = PsGroups::find()
            ->select(['name'])
            ->where(['id' => $userInfo['group_id']])
            ->asArray()
            ->one();
        if ($userGroup) {
            $userInfo['groupname'] = $userGroup['name'];
        }

        if (!CommunityService::service()->getUserCommunityIds($userInfo['id'])) {
            return "该用户未绑定小区！";
        }

        if (!$userInfo) {
            return "该用户不存在！";
        }

        $userInfo['operator_id'] = $userInfo['id'];

        return $userInfo;
    }

    /**
     * 获取用户信息by id
     * @param $user_id
     * @return array|string|\yii\db\ActiveRecord|null
     */
    public function getUserById($user_id)
    {
        $userInfo = PsUser::find()
            ->select(['id','username','mobile','truename','level','is_enable','group_id','property_company_id','ding_icon'])
            ->where(['id'=>$user_id,'system_type'=>2,'is_enable'=>1])
            ->asArray()->one();
        if (!$userInfo) {
            return "该用户不存在！";
        }
        //该用户是否已绑定了小区
        $userInfo['groupname'] = "";

        //查询用户所在的组
        $userGroup = PsGroups::find()
            ->select(['name'])
            ->where(['id' => $userInfo['group_id']])
            ->asArray()
            ->one();
        if ($userGroup) {
            $userInfo['groupname'] = $userGroup['name'];
        }

        if (!CommunityService::service()->getUserCommunityIds($userInfo['id'])) {
            return "该用户未绑定小区！";
        }

        if (!$userInfo) {
            return "该用户不存在！";
        }

        $userInfo['operator_id'] = $userInfo['id'];

        return $userInfo;
    }

    /**
     * 生成token值
     * @param $phone
     * @return array|string
     */
    public function generalToken($user_id,$phone)
    {
        //存入数据库，如果有则更新
        $loginToken = PsLoginToken::find()
            ->where(['user_id' => $user_id])
            ->andWhere(['app_type' => 2])
            ->one();
        if ($loginToken) {
            //判断是否已过有效期
            if (time() >= ($loginToken->expired_time - 3600)) {
                //更新token
                $token = md5('linyilianapp'. $phone . microtime());
                $timeExpired = time() + Yii::$app->getModule('lylapp')->params['api_token_expired_time'] * 86400;

                $loginToken->token     = $token;
                $loginToken->expired_time = $timeExpired;
                $loginToken->save();
            }
        } else {
            $token = md5('linyilianapp'. $phone . microtime());
            $timeExpired = time() + Yii::$app->getModule('ding_property_app')->params['api_token_expired_time'] * 86400;
            $loginToken = new PsLoginToken();
            $loginToken->token     = $token;
            $loginToken->user_id   = $user_id;
            $loginToken->app_type  = 2;
            $loginToken->expired_time = $timeExpired;
            $loginToken->create_at = time();
            $loginToken->save();
        }
        $user['token'] = $loginToken->token;

        return $user;
    }

    /**
     * 更新token值,token正确返回用户id
     * @param $token
     * @return bool|int
     */
    public function refreshToken($token)
    {
        $token = PsLoginToken::find()
            ->select(['user_id', 'expired_time'])
            ->where(['token' => $token, 'app_type' => 2])
            ->one();
        if (!$token) {
            return false;
        }

        if (time() > $token->expired_time) {
            //token过期
            PsLoginToken::deleteAll(['token' => $token, 'app_type' => 2]);
            return false;
        }

        //更新token过期时间
        $timeExpired = time() + Yii::$app->getModule('ding_property_app')->params['api_token_expired_time'] * 86400;
        $token->expired_time = $timeExpired;
        $token->save();
        return $token->user_id;
    }



}