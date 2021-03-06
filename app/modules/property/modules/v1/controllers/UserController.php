<?php

namespace app\modules\property\modules\v1\controllers;

use common\core\F;
use common\core\PsCommon;
use app\models\PsUser;
use service\manage\CompanyService;
use service\common\SmsService;
use service\rbac\OperateService;
use service\rbac\UserService;
use app\modules\property\controllers\BaseController;
use Yii;

class UserController extends BaseController
{
    // 允许不带token访问的控制器数组
    public $enableAction = ['login', 'get-sms-code', 'validate-sms-code', 'reset-password', 'saas-sso'];
    public $communityNoCheck = ['get-user-by-token'];

    /**
     * 获取短信验证码
     */
    public function actionGetSmsCode()
    {
        $data = $this->request_params;
        if (!empty($data['mobile'])) {
            if ($isEnable = PsUser::find()->select('is_enable')->where(['mobile' => $data['mobile'], 'system_type' => $this->systemType])->scalar()) {
                if ($isEnable == 1) {
                    if (SmsService::service()->init(4, $data['mobile'])->send() === true) {
                        return PsCommon::responseSuccess();
                    }
                    return PsCommon::responseFailed('发送失败');
                }
                return PsCommon::responseFailed('该号码绑定的用户已禁用');
            }
            return PsCommon::responseFailed('该手机号未绑定用户');
        }
        return PsCommon::responseFailed('手机号不能为空');
    }

    /**
     * 验证短信验证码
     */
    public function actionValidateSmsCode()
    {
        $data = $this->request_params;
        if (!empty($data['mobile']) && !empty($data['code'])) {
            if (SmsService::service()->init(4, $data['mobile'])->valid($data['code'])) {
                $rand = PsCommon::getRandomString(10);
                Yii::$app->redis->hset('lyl:validate:smscode', $rand, 1);
                return PsCommon::responseSuccess(['acode' => $rand]);
            }
            return PsCommon::responseFailed('验证码不正确');
        }
        return PsCommon::responseFailed('手机号或验证码不能为空');
    }

    /**
     * 重置密码
     */
    public function actionResetPassword()
    {
        $data = $this->request_params;
        if (!empty($data['mobile']) && !empty($data['password'])) {
            $rand = PsCommon::get($this->request_params, 'acode');
            if (!$rand) {
                return PsCommon::responseFailed('未通过短信验证');
            }
            if (!Yii::$app->redis->hget('lyl:validate:smscode', $rand)) {
                return PsCommon::responseFailed('未通过短信验证');
            }
            Yii::$app->redis->hdel('lyl:validate:smscode', $rand);
            if ($user = PsUser::findOne(['mobile' => $data['mobile'], 'system_type' => $this->systemType])) {
                $user->password = Yii::$app->security->generatePasswordHash($data['password']);
                if ($user->save()) {
                    return PsCommon::responseSuccess();
                }
                return PsCommon::responseFailed('保存失败');
            }
            return PsCommon::responseFailed('该手机号未绑定用户');
        }
        return PsCommon::responseFailed('手机号或密码不能为空');
    }

    /**
     * 修改密码
     */
    public function actionChangePassword()
    {
        $data = $this->request_params;
//        $loginToken = PsLoginToken::findOne(['token' => $token]);
        $user_id = UserService::currentUser('id');
        $user = new User();
        $user->scenario = 'update'; // 设置更新密码场景
        $user->load($data, '');
        if ($user->validate()) {
            $user = PsUser::findOne(['id' => $user_id]);
            if ($user->validatePassword($data['old_password'])) {
                $user->password = Yii::$app->security->generatePasswordHash($data['password']);
                if ($user->save()) {
                    return PsCommon::responseSuccess();
                }
                return PsCommon::responseFailed('保存失败');
            }
            return PsCommon::responseFailed('旧密码不正确');
        }
        return PsCommon::responseFailed('保存失败');
    }

    /**
     * 根据 token 获取用户信息
     */
    public function actionGetUserByToken()
    {
        $userInfo = $this->user_info;
        //返回操作手册
        $userInfo['version_file_url'] = '';
        return PsCommon::responseSuccess($userInfo);
    }

    public function actionCommOperateLog()
    {
        $data = $this->request_params;
        if (!empty($data)) {
            $model = new User();
            $model->setScenario('comm-operate-log');
            foreach ($data as $key => $val) {
                $form['User'][$key] = $val;
            }
            $model->load($form);
            if (!$model->validate()) {
                $errorMsg = array_values($model->errors);
                return PsCommon::responseFailed($errorMsg[0][0]);
            }
        }
        $page = $data['page'] ? $data['page'] : 1;
        $rows = $data['rows'] ? $data['rows'] : 20;
        $resultData = OperateService::service()->commlists($data, $page, $rows, '');
        return PsCommon::responseSuccess($resultData);
    }

    /**
     * 退出登录
     */
    public function actionLoginOut()
    {
        $token = F::request('token');
        UserService::service()->deleteByToken($token);
        return PsCommon::responseSuccess();
    }

    /**
     * 登录
     */
    public function actionLogin()
    {
        $data = $this->request_params;
        $user = new PsUser();
        $user->scenario = 'login'; // 设置登录场景
        $user->load($data, '');    // 加载数据准备验证
        if ($user->validate()) {
            $r = UserService::service()->login($data['username'], $data['password'], $data['system_type']);
            if (!$r['code']) {
                return PsCommon::responseFailed($r['msg']);
            }
            return PsCommon::responseSuccess($r['data']);
        } else {
            $errorMsg = array_values($user->errors);
            return PsCommon::responseFailed($errorMsg[0][0]);
        }
    }

}
