<?php

namespace service\property_basic;


use common\core\PsCommon;
use common\MyException;
use app\models\PsUser;
use app\models\PsPropertyAlipay;
use app\models\PsPropertyAlipayInfo;
use app\models\PsPropertyCompany;
use service\message\MessageService;
use service\common\SmsService;
use service\BaseService;

class AlipayApplyService extends BaseService
{

    /**
     * 新增支付申请
     * @author yjh
     * @param $params
     * @throws MyException
     * @throws \yii\db\Exception
     */
    public function create($params,$userinfo)
    {
        $alipay = new PsPropertyAlipay();
        $data = PsCommon::validParamArr($alipay, $params, 'create');
        if (!$data['status']) {
            throw new MyException($data['errorMsg']);
        }
        $tran = \Yii::$app->getDb()->beginTransaction();
        try {
            $alipay->save();
            $this->createInfo($alipay);
            $tran->commit();
        } catch (\Exception $e) {
            $tran->rollBack();
            throw new MyException($e->getMessage());
        }
        $operate = [
            "operate_menu" => "支付宝申请",
            "operate_type" => "新增申请资料",
            "operate_content" => "支付宝新增申请",
        ];
        OperateService::addComm($userinfo, $operate);
    }

    public function edit($params,$userinfo)
    {
        $alipay = $this->getOneById($params['id'] ?? null);
        if ($alipay->status != 4) {
            throw new MyException('只有已驳回才可以修改');
        }
        $data = PsCommon::validParamArr($alipay, $params, 'edit');
        if (!$data['status']) {
            throw new MyException($data['errorMsg']);
        }
        $tran = \Yii::$app->getDb()->beginTransaction();
        try {
            $alipay->status = 1;
            $alipay->save();
            $this->createInfo($alipay);
            $tran->commit();
        } catch (\Exception $e) {
            $tran->rollBack();
            throw new MyException($e->getMessage());
        }
        $operate = [
            "operate_menu" => "支付宝申请",
            "operate_type" => "编辑申请资料",
            "operate_content" => "支付宝编辑申请",
        ];
        OperateService::addComm($userinfo, $operate);
    }

    public function getOneById($id)
    {
        if (empty($id)) {
            throw new MyException('ID不能为空');
        }
        $result = PsPropertyAlipay::find()->where(['id' => $id])->one();
        if (!$result) {
            throw new MyException('数据不存在');
        }
        return $result;
    }

    public function getDetail($data)
    {
        $result = $this->getOneById($data['id'])->toArray();
        if (empty($result)) {
            throw new MyException('数据不存在');
        }
        $result['type_desc'] = PsPropertyAlipay::$type_desc[$result['type']];
        $result['status_desc'] = PsPropertyAlipay::$status_desc[$result['status']];
        if ($result['status'] == 4) {              
            $info = PsPropertyAlipayInfo::find()->where(['apply_id' => $result['id'], 'status' => 6])->orderBy('id desc')->one();
            $result['info'] = $info['info'];
        }
        if ($result['status'] == 3) {
            $company = PsPropertyCompany::find()->where(['id' => $result['company_id']])->one();
            $result['info'] = $company['nonce'] ? \Yii::$app->params['auth_to_us_url'] . "&nonce=" . $company['nonce'] : '';
        }
        return $result;
    }

    public function getOpreationDetail($request)
    {
        $result = $this->getDetail($request);
        $info = PsPropertyAlipayInfo::find()->where(['apply_id' => $result['id']])->orderBy('id desc')->asArray()->all();
        $result['descprition'] = $result['info'] ?? '';
        $result['info']= ['list' => []];
        foreach ($info as $v) {
            $result['info']['list'][] = [
              'status' => $v['status'],
              'status_desc' => PsPropertyAlipayInfo::$status_desc[$v['status']],
              'name' => PsUser::find()->where(['id' => $v['uid']])->one()['username'],
              'created_at' => date('Y-m-d H:i:s',$v['created_at']),
              'info' => $v['info'],
            ];
        }
        $status = PsPropertyAlipayInfo::find()->where(['apply_id' => $result['id']])->orderBy('id desc')->one();
        $result['status'] = $status['status'];
        $result['status_desc'] = PsPropertyAlipayInfo::$status_desc[$status['status']];
//        $result['status_list'] = $this->getStatusData($result['info']['list'][0]['status']);
        return $result;
    }

    public function getStatusData($status)
    {
        if ($status == 1) {
            return [['id'=>'2','name' => '已提交'],['id'=>'3','name' => '审核中']];
        } else if($status == 2) {
            return [['id'=>'3','name' => '审核中']];
        } else if($status == 3) {
            return [['id'=>'4','name' => '待确认']];
        } else if($status == 4) {
            return [['id'=>'5','name' => '待授权']];
        } else {
            return [];
        }
    }

    public function getList($params)
    {
        $data = PsPropertyAlipay::getList($params);
        $result['list'] = $data['list'];
        return $result;
    }

    public function getOpreationList($params)
    {
        $data = PsPropertyAlipayInfo::getList($params);
        foreach ($data['list'] as &$v) {
            $info = PsPropertyAlipayInfo::find()->where(['apply_id' => $v['id']])->orderBy('id desc')->one();
            $v['status'] = $info['status'];
            $v['status_desc'] = PsPropertyAlipayInfo::$status_desc[$info['status']];
        }
        unset($v);
        foreach (PsPropertyAlipayInfo::$status_desc as $k => $v) {
            $data['status'][] = ['id' => $k ,'name' => $v];
        }
        return $data;
    }

    public function updateStatus($params,$user_name)
    {
        if (empty($params['id']) || empty($params['status'])) {
            throw new MyException('参数错误');
        }
        if ($params['status'] == 7 && empty($params['info'])) {
            throw new MyException('驳回原因必填');
        }
        $alipay = $this->getOneById($params['id']);
        if (empty($alipay)) {
            throw new MyException('不存在该数据');
        }

        if ($params['status'] == 2) { //审核中
            $this->createInfo($alipay,'2','',$user_name['id']);
        }
        if ($params['status'] == 3) { //待确认
            $this->createInfo($alipay,'3','',$user_name['id']);
            $alipay->status = 5;
            SmsService::service()->init(46, $alipay->link_mobile)->send([$alipay->link_name]);
            $this->sendMessage(['id' => $alipay->id,'temp_id' => 21,'member_id' => $user_name['id'],'username' => $user_name['username'],'user_list' => $alipay->user_id]);
        }
        if ($params['status'] == 4) { //待授权
            $this->createInfo($alipay,'4','',$user_name['id']);
            $alipay->status = 3;
            SmsService::service()->init(47, $alipay->link_mobile)->send([$alipay->link_name]);
            $this->sendMessage(['id' => $alipay->id,'temp_id' => 22,'member_id' => $user_name['id'],'username' => $user_name['username'],'user_list' => $alipay->user_id]);
        }
        if ($params['status'] == 6) { //已驳回
            $this->createInfo($alipay,'6',$params['info'],$user_name['id']);
            $alipay->status = 4;
            SmsService::service()->init(45, $alipay->link_mobile)->send([$alipay->link_name,$params['info']]);
            $this->sendMessage(['id' => $alipay->id,'temp_id' => 20,'member_id' => $user_name['id'],'username' => $user_name['username'],'user_list' => $alipay->user_id,'message' => $params['info']]);
        }
        $alipay->save();
    }

    public function sendMessage($params)
    {
        $data = [
            'id' => $params['id'],
            'member_id' => $params['member_id'],
            'user_name' => $params['username'],
            'create_user_type' => 1,
            'msg_type' => 1,
            'msg_tmpId' => $params['temp_id'],
            'msg_target_type' => 17,
            'msg_auth_type' => 10,
            'user_list' => [$params['user_list']],
            'msg' => [
                $params['message'] ?? null
            ]
        ];
        MessageService::service()->addMessageTemplate($data,false);
    }

    /**
     * 查看是否开通和是否超管
     * @author yjh
     * @param $uid
     * @return array
     */
    public function checkAdmin($uid)
    {
        $result = [];
        $user = PsUser::find()->where(['id' => $uid])->one();
        if ($user->user_type == 'admin' && $user->level == 1 && $user->system_type == 2) {
            $result['is_admin'] = 1;
        } else {
            $result['is_admin'] = 2;
        }
        $alipay = PsPropertyAlipay::find()->where(['company_id' => $user->property_company_id])->one();
        if ($alipay->status == 2) {
            //开通
            $result['status'] = 1;
        } else {
            //未开通
            $result['status'] = 2;
        }

        return $result;
    }

    /**
     * 支付宝申请过程新增
     * @author yjh
     * @param $alipay
     * @param int $status
     * @param string $desc
     * @param string $uid
     * @throws MyException
     */
    public function createInfo($alipay,$status = 1,$desc = '',$uid = '')
    {
        $info = new PsPropertyAlipayInfo();
        $info->apply_id = $alipay->id;
        $info->status = $status;
        $info->info = $desc;
        if (empty($uid)) {
            $info->uid = $alipay->user_id;
        } else {
            $info->uid = $uid;
        }
        $info->created_at = time();
        if (!$info->save()) {
            throw new MyException('数据异常');
        }
    }

    public function endApply($nonce)
    {
        $company =  PsPropertyCompany::find()->select('id')
            ->where(['nonce'=>$nonce])
            ->one();
        if (empty($company)) {
            return '公司不存在';
        }
        //修改支付宝申请状态为已签约
        $alipay = PsPropertyAlipay::find()->where(['company_id' => $company->id])->one();
        $info = PsPropertyAlipayInfo::find()->where(['apply_id' => $alipay->id])->orderBy('id desc')->one();
        $alipay->status = 2;
        $alipay->save();
        $user = PsUser::find()->where(['id' => $info->uid])->one();
        AlipayApplyService::service()->createInfo($alipay,'5','',$info->uid);
        AlipayApplyService::service()->sendMessage(['id' => $alipay->id,'temp_id' => 23,'member_id' => $info->uid,'username' => $user['username'],'user_list' => $alipay->user_id]);
        SmsService::service()->init(48, $alipay->link_mobile)->send([$alipay->link_name]);
    }
}

