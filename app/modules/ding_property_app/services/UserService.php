<?php
/**
 * Created by PhpStorm.
 * User: fengwenchao
 * Date: 2017/6/28
 * Time: 10:37
 */
namespace app\modules\ding_property_app\services;
use app\models\PsAdPositionModel;
use app\models\PsAdvert;
use app\models\PsCommunityModel;
use app\models\PsGroups;
use app\models\PsLoginToken;
use app\models\PsMenus;
use app\models\PsRepair;
use app\models\PsRepairAssign;
use app\models\PsUser;
use app\models\PsWaterMeter;
use app\models\UserInfo;
use app\models\ZjyRoleMenu;
use app\models\ZjyUserRole;
use common\MyException;
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
        $userInfo = UserInfo::find()
            ->select('username,dept_id,node_type,user_id as id,mobile_number as mobile')
            ->where(['user_id' => $user_id])
            ->asArray()
            ->one();
        if (!$userInfo) {
            throw new MyException('该用户不存在！');
        }
        $userInfo['truename'] = $userInfo['username'];
        return $userInfo;
    }
}