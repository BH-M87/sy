<?php

namespace service\rbac;

use common\core\PsCommon;
use service\BaseService;
use app\models\PsUser;
use app\models\PsAgent;
use app\models\PsGroups;
use app\models\PsUserCommunity;
use app\modules\street\services\DingdingService;
use app\services\SmsService;
use app\models\PsLoginToken;
use yii\db\Exception;
use Yii;

class UserService extends BaseService
{

    public static $userInfo = null;//当前用户

    const STATUS_ACTIVE = 1;//激活用户
    const STATUS_BLOCK = 2;//禁用用户

    const SYSTEM_OM = 1;//运营系统(operation manager)
    const SYSTEM_PROPERTY = 2;//物业系统
    const SYSTEM_STREET = 3;//街道办
    const SYSTEM_PETITION = 4; // 信访局

    /**
     * 运营后台登录
     * @param $params
     * @return array
     */
    public function operateLogin($params)
    {
        $user = new PsUser();
        $user->setScenario('login');
        $user->load($params, '');
        if (!$user->validate()) {
            return $this->failed($this->getError($user));
        }
        $result = $this->login($user['username'], $user['password'], $user['system_type']);
        if (!$result['code']) {
            return $this->failed($result['msg']);
        }
        return $this->success($result['data']);
    }

    /**
     * 修改密码
     * @param $userId
     * @param $params
     * @return array
     */
    public function changePassword($userId, $params)
    {
        $model = new PsUser();
        $model->setScenario('change-password');
        $model->load($params, '');
        if (!$model->validate()) {
            return $this->failed($this->getError($model));
        }
        $user = PsUser::findOne(['id' => $userId, 'is_enable' => self::STATUS_ACTIVE]);
        if ($user->validatePassword($params['old_password'])) {//密码验证
            $user->password = Yii::$app->security->generatePasswordHash($params['password']);//生成新密码
            if ($user->save()) {
                return $this->success();
            }
            return $this->failed('保存失败');
        }
        return $this->failed('旧密码不正确');
    }

    /**
     * 登录
     * @param $userName
     * @param $password
     * @param $systemType
     * @return array
     */
    public function login($userName, $password, $systemType)
    {


        $user = PsUser::findOne(['username' => $userName, 'system_type' => $systemType]);
        if (!$user) {
            return $this->failed('账户不存在');
        }
        if (!$user->validatePassword($password)) {
            return $this->failed('密码错误');
        }
        if ($user->is_enable != 1) {
            return $this->failed('账号已禁用，请联系管理员');
        }
        if ($systemType == 2 && !$user->checkProCompanyExistCommunity($user['id'])) {
            return $this->failed('账号没有关联小区');
        }
        //是否有token存在
        $loginToken = PsLoginToken::findOne(['user_id' => $user['id'], 'app_type' => 1]);
        if ($loginToken && $this->_getCache($loginToken['token'])) {
            //token存在·切没有过期，继续使用
            $this->refreshExpired($loginToken['token']);
            return $this->success(['id' => $user['id'], 'property_company_id' => $user['property_company_id'],
                'token' => $loginToken['token']]);
        }
        //缓存已过期，或第一次生成token
        if ($token = $this->_saveLoginToken($user, $loginToken)) {
            return $this->success(['id' => $user['id'], 'property_company_id' => $user['property_company_id'],
                'token' => $token]);
        }
        return $this->failed();
    }

    /**
     * user_id, token关系存表
     * @param PsUser $user
     * @param $loginToken
     * @return bool|string
     */
    private function _saveLoginToken(PsUser $user, $loginToken)
    {
        $newToken = md5($user['id'] . $user['username'] . time());
        if ($loginToken) {
            $loginToken->token = $newToken;
            $loginToken->create_at = time();
        } else {
            $loginToken = new PsLoginToken();
            $loginToken->token = $newToken;
            $loginToken->user_id = $user['id'];
            $loginToken->app_type = 1;
            //expired time没有意义，ps_login_token表仅存储最新的token,uid关系
//            $loginToken->expired_time = time() + 7200;
            $loginToken->create_at = time();
        }
        if ($loginToken->save()) {
            $this->_setCache($user, $newToken);
            return $newToken;
        }
        return false;
    }

    private function _getCache($token)
    {
        return Yii::$app->redis->get($this->_cacheKey($token));
    }

    /**
     * 用户数据存入缓存
     * @param PsUser $user
     * @param $token
     */
    private function _setCache(PsUser $user, $token)
    {
        $cacheData = [
            'id' => $user['id'],
            'is_enable' => $user['is_enable'],
            'mobile' => $user['mobile'],
            'property_company_id' => $user['property_company_id'],
            'truename' => $user['truename'],
            'username' => $user['username'],
            'group_id' => $user['group_id'],
            'level' => $user['level'],
            'creator' => $user['creator'],
            'system_type' => $user['system_type'],
            'tenant_id' => $user['tenant_id'],
        ];
        Yii::$app->redis->set($this->_cacheKey($token), json_encode($cacheData), 'EX', 7200);
    }

    /**
     * 缓存Key
     * old cache key: wy_user_$token
     * @param $token
     * @return string
     */
    private function _cacheKey($token)
    {
        return 'wuye_user_' . $token;
    }

    /**
     * 刷新token有效期
     * @param $token
     * @param float|int $time
     */
    protected function refreshExpired($token, $time = 7200)
    {
        $key = $this->_cacheKey($token);
        Yii::$app->redis->expire($key, $time);
    }

    /**
     * 根据token获取缓存中存储的user信息
     * @param $token
     * @return array
     */
    public function getInfoByToken($token)
    {
        if (!$token) {
            return $this->failed('token不能为空');
        }

        if (!$user = Yii::$app->redis->get($this->_cacheKey($token))) {
            return $this->failed('token不存在,token失效');
        }
        $this->refreshExpired($token);//刷新有效期(2个小时)
        return $this->success(json_decode($user, true));
    }

    /**
     * 根据token，剔除用户登录状态
     * 因为判断token是否有效，仅从redis是否有数据判断，所以剔除状态，无需删除ps_login_token
     * @param $token
     * @return bool
     */
    public function deleteByToken($token)
    {
//        PsLoginToken::deleteAll(['token' => $token]);
        Yii::$app->redis->del($this->_cacheKey($token));
        return true;
    }

    /**
     * 根据user_id，剔除登录状态
     * @param integer $userId
     * @param boolean $realDelete 是否删除ps_login_token表记录（删除ps_user表记录时）
     * @return bool
     */
    public function deleteLogin($userId, $realDelete = false)
    {
        $loginToken = PsLoginToken::findOne(['user_id' => $userId]);
        if ($loginToken) {
            Yii::$app->redis->del($this->_cacheKey($loginToken['token']));
        }
        if ($realDelete && $loginToken) {//当删除ps_user表数据时，ps_login_token的数据就不再有意义
            $loginToken->delete();
        }
        return true;
    }

    /*
     * 获取父级用户id
     * */
    public function getParentUser($group_id)
    {
        $self_group = Yii::$app->db->createCommand("select parent_id from ps_groups where id=:group_id", [":group_id" => $group_id])->queryOne();
        if ($self_group["parent_id"] == 0) {
            $parent_id = $group_id;
        } else {
            $parent_id = $self_group["parent_id"];
        }
        $user = Yii::$app->db->createCommand("select id,mobile,username,truename from ps_user where group_id=:group_id", [":group_id" => $parent_id])->queryOne();
        return $user;
    }

    /**
     * 保存UserInfo信息
     * @author shenyang
     * @param $userInfo
     */
    public static function setUser($userInfo)
    {
        self::$userInfo = $userInfo;
        return true;
    }

    /**
     * 查看当前登录的用户信息
     * @author shenyang
     * @param $userInfo
     */
    public static function currentUser($field = false)
    {
        $userInfo = self::$userInfo;
        if ($field) {
            return PsCommon::get($userInfo, $field);
        }
        return $userInfo;
    }

    /**
     * 根据community_id查找user信息
     * @param $communityId
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getUserByCommunityId($communityId)
    {
        return PsUser::find()
            ->alias('u')
            ->leftJoin(['uc' => PsUserCommunity::tableName()], 'uc.manage_id = u.id')
            ->select(['u.id', 'u.truename'])
            ->where(['uc.community_id' => $communityId])
            ->andWhere(['u.system_type' => 2, 'u.is_enable' => 1])
            ->asArray()
            ->all();
    }

    /**
     * 根据用户id获取这个用户的管理员
     * @param $user_id
     * @return false|null|string
     */
    public function getSendUserByUserId($user_id)
    {
        $property_company_id = PsUser::find()->select(['property_company_id'])->where(['id' => $user_id])->scalar();
        return PsUser::find()->select(['id'])->where(['property_company_id' => $property_company_id, 'system_type' => 2, 'is_enable' => 1, 'level' => 1])->scalar();
    }

    //新增，编辑员工(街道办员工，业委会成员)
    private function _saveUser($params, $systemType, $id = 0)
    {
        $mobile = PsCommon::get($params, 'mobile');
        $randPwd = PsCommon::getRandomString(6);
        $truename = PsCommon::get($params, 'truename');
        if ($id) {
            $model = PsUser::findOne($id);
            if (!$model) {
                return $this->failed('数据不存在');
            }
            $currentId = self::currentUser('id');
            if ($currentId == $id) {
                return $this->failed('无法编辑自己');
            }
            //唯一性判断，手机号不重复
            $flag = PsUser::find()
                ->where(['<>', 'id', $id])
                ->andWhere(['mobile' => $mobile, 'system_type' => $systemType, 'is_enable' => 1])
                ->exists();
            $sms = ($mobile != $model['mobile']);
            if ($mobile != $model['mobile']) {//只有编辑手机号，才会重置密码
                $model->password = Yii::$app->security->generatePasswordHash($randPwd);
            }
        } else {
            $model = new PsUser();
            $model->create_at = time();
            $model->password = Yii::$app->security->generatePasswordHash($randPwd);
            //唯一性判断，手机号不重复
            $flag = PsUser::find()
                ->where(['mobile' => $mobile, 'system_type' => $systemType, 'is_enable' => 1])
                ->exists();
            $sms = true;
        }
        $model->setScenario('street');
        if ($flag) {
            return $this->failed('手机号已被注册，不能重复');
        }
        $groupId = PsCommon::get($params, 'group_id');
        $userInfo = self::currentUser();

        $from_ding = !empty($userInfo['from_ding']) ? true : false;//是否来自钉钉的通讯录同步
        //level同group保持一致
        $level = PsGroups::find()->select('level')
            ->where(['id' => $groupId])->scalar();
        if (!$level) {
            return $this->failed('部门不存在');
        }
        $model->truename = $truename;
        $model->username = $mobile;
        $model->mobile = $mobile;
        $model->group_id = $groupId;
        $model->property_company_id = !empty($userInfo['property_company_id']) ? $userInfo['property_company_id'] : 0;
        $isEnable = PsCommon::get($params, 'is_enable');
        if ($isEnable) {
            $model->is_enable = $isEnable;//启用，禁用
        }
        $model->creator = !empty($userInfo['id']) ? $userInfo['id'] : 1;
        $model->sex = PsCommon::get($params, 'sex');
        $model->user_no = PsCommon::get($params, 'user_no');
        $model->entry_time = PsCommon::get($params, 'entry_time');
        $model->system_type = $systemType;
        $model->level = $level;

        if (!$id) {
            if (!$model->validate() || !$model->save()) {
                return $this->failed($this->getError($model));
            }
            Yii::info("zq-test-1:" . $from_ding, 'api');
            if (!$from_ding) {
                $res = DingdingService::service()->createUser($userInfo['property_company_id'], $groupId, $truename, $model->id, $mobile);
                $result = json_decode($res, true);
                Yii::info("add_user_result:" . $result, 'api');
                if ($result['errCode']) {
                    $model->delete();
                    return $this->failed('新增钉钉用户失败:' . $result['errMsg']);
                }
            }

            //只有新增才有
            //添加用户小区关联关系
            $communityIds = CommunityService::service()->getUserCommunityIds($model->id);
            CommunityService::service()->batchInsertUserCommunity($model->id, $communityIds);
        } else {

            if (!$from_ding) {
                $res = DingdingService::service()->editUser($userInfo['property_company_id'], $groupId, $truename, $id, $mobile);
                $result = json_decode($res, true);
                if ($result['errCode']) {
                    return $this->failed('编辑钉钉用户失败:' . $result['errMsg']);
                }
            }

            if (!$model->validate() || !$model->save()) {
                return $this->failed($this->getError($model));
            }
        }

        //发送短信通知密码
        if ($sms) {
            if ($systemType == 3) {
                SmsService::service()->init(15, $mobile)->send([$randPwd]);
            } elseif ($systemType == 2) {
                SmsService::service()->init(9, $mobile)->send([$randPwd]);
            }
        }
        return $this->success($model->id);
    }

    /**
     * 创建用户
     */
    public function createUser($params, $systemType)
    {
        return $this->_saveUser($params, $systemType);
    }

    /**
     * 编辑用户
     */
    public function editUser($id, $params, $systemType)
    {
        return $this->_saveUser($params, $systemType, $id);
    }

    /**
     * 获取街道办所有员工(非管理员)
     * @param $streetId
     */
    public function getStreetUsers($streetId)
    {
        $data = PsUser::find()->alias('t')
            ->select('t.id, t.truename, g.name as group_name')
            ->leftJoin(['g' => PsGroups::tableName()], 't.group_id=g.id')
            ->where(['t.system_type' => self::SYSTEM_STREET, 't.property_company_id' => $streetId])
            ->andWhere(['<>', 't.level', 1])
            ->orderBy('t.truename asc')
            ->asArray()->all();
        $result = [];
        foreach ($data as $v) {
            $v['truename'] = $v['truename'] . '（' . $v['group_name'] . '）';
            $v['checked'] = 0;
            $result[] = $v;
        }
        return $result;
    }

    private function _streetSearch($params, $systemType)
    {
        return PsUser::find()->alias('t')
            ->where([
                't.property_company_id' => PsCommon::get($params, 'property_company_id'),
                't.system_type' => $systemType,
            ])->andWhere(['<>', 't.level', 1])
            ->andFilterWhere(['group_id' => PsCommon::get($params, 'group_id')])
            ->andFilterWhere(['OR',
                ['like', 't.truename', PsCommon::get($params, 'name')],
                ['like', 't.mobile', PsCommon::get($params, 'name')],
            ]);
    }

    /**
     * 用户列表
     */
    public function getUserList($params, $systemType, $page, $pageSize)
    {
        $result = [];
        $data = $this->_streetSearch($params, $systemType)
            ->leftJoin(['g' => PsGroups::tableName()], 't.group_id=g.id')
            ->select('t.id, t.truename, t.user_no, t.mobile, t.is_enable, t.sex, t.entry_time, g.name as group_name')
            ->orderBy('id desc')
            ->offset(($page - 1) * $pageSize)->limit($pageSize)
            ->asArray()->all();
        $currentId = UserService::currentUser('id');
        foreach ($data as $v) {
            if ($v['id'] == $currentId) {
                $v['self'] = true;
            } else {
                $v['self'] = false;
            }
            $result[] = $v;
        }
        return $result;
    }

    /**
     * 用户查询总数
     */
    public function getUserCount($params, $systemType)
    {
        return $this->_streetSearch($params, $systemType)->count();
    }

    /**
     * 删除用户（钉钉同步删除）
     */
    public function delUser($id, $propertyId)
    {
        $from_ding = !empty(self::currentUser('from_ding')) ? true : false;//是否来自钉钉的通讯录同步
        if (!$from_ding) {
            $res = DingdingService::service()->delUser($propertyId, $id);
            $result = json_decode($res, true);
            if (!empty($result['errCode'])) {
                return $this->failed('钉钉删除失败');
            }
        }
        $r = $this->removeUser($id);
        if (!$r['code']) {
            return $this->failed('删除失败:'.$r['msg']);
        }
        return $this->success();
    }

    /**
     * 删除用户
     * @param $userId
     */
    public function removeUser($userId)
    {
        $user = PsUser::findOne(['id' => $userId]);
        if (!$user) {
            return $this->failed('用户不存在');
        }
        if ($userId == self::currentUser('id')) {
            return $this->failed('无法删除自己');
        }
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            if (!$user->delete()) {//删除ps_user表
                throw new Exception('删除失败');
            }
            CommunityService::service()->deleteUserCommunity($userId);//删除ps_user_community关联表
            $this->deleteLogin($userId);//剔除登录token
            $trans->commit();
            return $this->success();
        } catch (Exception $e) {
            $trans->rollback();
            return $this->failed($e->getMessage());
        }
    }

    /**
     * 用户详情
     */
    public function showUser($id, $companyId)
    {
        $data = PsUser::find()
            ->select('id, property_company_id, truename, username, group_id, mobile, sex, user_no, entry_time, is_enable')
            ->where(['id' => $id, 'system_type' => 3])
            ->asArray()->one();
        if (!$data) {
            return $this->failed('数据不存在');
        }
        if ($data['property_company_id'] != $companyId) {
            return $this->failed('无权限查看');
        }
        return $this->success($data);
    }

    /**
     * 获取所有物业公司员工ID
     * @param $companyId
     */
    public function getUidsByProperty($companyId)
    {
        return PsUser::find()->select('id')->where(['property_company_id' => $companyId])->column();
    }

    /**
     * 判断当前用户是否为管理员帐号（排除业委会）
     * @param $userInfo
     */
    public function isAdmin($userInfo)
    {
        if ($userInfo['level'] != 1) {
            return false;
        }
        $agent = PsAgent::find()->select('type')
            ->where(['id' => $userInfo['property_company_id']])
            ->asArray()->scalar();
        if ($agent == 6) {//业委会
            return false;
        }
        return true;
    }

    /**
     * 根据用户ID获取id+truename
     * @param $ids
     */
    public function getUsersById($ids)
    {
        return PsUser::find()->select('id, truename')
            ->where(['id' => $ids])
            ->asArray()->all();
    }

    /**
     * 变更用户信息
     * @param $userId
     * @param $data
     * @return bool|int
     */
    public function changeUser($userId, $data)
    {
        if (!$userId) return false;
        if (!empty($data['is_enable']) && $data['is_enable'] == 2) {//用户禁用状态
            $this->deleteLogin($userId);
        }
        return PsUser::updateAll($data, ['id' => $userId]);
    }
}