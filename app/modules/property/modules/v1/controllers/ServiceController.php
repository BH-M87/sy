<?php

namespace app\modules\property\modules\v1\controllers;

use common\core\PsCommon;
use app\models\PsServiceModel;
use Yii;
use service\alipay\ServiceService;
use service\alipay\BillCostService;
use app\models\PsServiceForm;
use app\modules\property\controllers\BaseController;

class ServiceController extends BaseController
{
    public $repeatAction = ['update'];

    /**
     * 2016-12-15
     * 查看服务 {"service_id":1}
     */
    public function actionShow()
    {
        $data = $this->request_params;

        if (!$data) {
            return PsCommon::responseFailed('json串为空,解析出错');
        }

        $model = new PsServiceForm;
        foreach ($data as $key => $val) {
            $serviceForm['PsServiceForm'][$key] = $val;
        }

        $model->setScenario('show');

        $model->load($serviceForm); // 加载数据

        if ($model->validate()) { // 检验数据
            $result = ServiceService::service()->serviceShow($data['service_id']);

            if ($result) {
                return PsCommon::responseSuccess($result);
            } else {
                return PsCommon::responseFailed('服务不存在');
            }
        } else {
            $errorMsg = array_values($model->errors);
            return PsCommon::responseFailed($errorMsg[0][0]);
        }
    }

    /**
     * 2016-12-14
     * 获取服务列表 {"name":"费","page":1,"rows":20,"parent_id":17,"service_no":1,"status":1, "type"：2}
     */
    public function actionList()
    {
        $data = $this->request_params;
        $result = ServiceService::service()->serviceList($data);

        return PsCommon::responseSuccess($result);
    }

    /**
     * 2016-12-14
     * 获取缴费列表下的服务项目+临时停车费
     */
    public function actionBillLists()
    {
//        $result = ServiceService::service()->getBillService();
//        $park = ServiceService::service()->getServiceByName('临时停车费');
//        $key = count($result);
//        $result[$key]['id']=$park['id'];
//        $result[$key]['name'] = $park['name'];
        //================================陈科浪2018-04-27修改
        $result = BillCostService::service()->getAllByPay($this->user_info);
        if ($result['code']) {
            foreach ($result['data'] as $data) {
                $arr['id'] = $data['value'];
                $arr['name'] = $data['label'];
                $arrList[] = $arr;
            }
        }
        return PsCommon::responseSuccess($arrList);
    }

    /**
     * 2016-12-14
     * 获取缴费列表下的服务项目
     */
    public function actionBillList()
    {
        //$result = ServiceService::service()->getBillService();
        //================================陈科浪2018-04-27修改
        $result = BillCostService::service()->getAllByReport($this->user_info);
        if ($result['code']) {
            foreach ($result['data'] as $data) {
                $arr['id'] = $data['value'];
                $arr['name'] = $data['label'];
                $arrList[] = $arr;
            }
        }
        return PsCommon::responseSuccess($arrList);
    }

    /**
     * 2016-12-14
     * 查询小区下当前可用的服务列表
     */
    public function actionCommunityService()
    {
        $data = $this->request_params;
        if ($data) {
            if (!$data["community_id"]) {
                return PsCommon::responseFailed('小区id不能为空');
            }
            $resultData = ServiceService::service()->getCommunityService($data['community_id']);
            return PsCommon::responseSuccess($resultData);
        } else {
            return PsCommon::responseFailed('json串解析为空');
        }
    }

    /**
     * 2016-12-14
     * 查找父级服务 {"status":2}
     */
    public function actionParent()
    {
        $data = $this->request_params;

        $model = new PsServiceForm;
        foreach ($data as $key => $val) {
            $serviceForm['PsServiceForm'][$key] = $val;
        }

        $model->setScenario('parent');

        $model->load($serviceForm); // 加载数据

        if ($model->validate()) { // 检验数据
            $result = ServiceService::service()->serviceParent(PsCommon::get($this->request_params, 'status'));
            return PsCommon::responseSuccess($result);
        } else {
            $errorMsg = array_values($model->errors);
            return PsCommon::responseFailed($errorMsg[0][0]);
        }
    }

    /**
     * 2016-12-14
     * 启用停用服务 {"service_id":"25","status":"2"}
     */
    public function actionCheck()
    {
        $data = $this->request_params;

        if (!$data) {
            return PsCommon::responseFailed('json串为空,解析出错');
        }

        $model = new PsServiceForm;

        $model->setScenario('check');

        $model->load($this->request_params, ''); // 加载数据

        if ($model->validate()) { // 检验数据
            $result = ServiceService::service()->serviceCheck($data, $this->user_info);
            return $result;
        } else {
            $errorMsg = array_values($model->errors);
            return PsCommon::responseFailed($errorMsg[0][0]);
        }
    }

    /**
     * 2016-12-14
     * 新增或者修改服务 {"service_id":"0","img_url":"1.jpg","intro":"12","name":"电视费","order_sort":1,"parent_id":"17","status":"1"}
     */
    public function actionUpdate()
    {
        $data = $this->request_params;

        if (!$data) {
            return PsCommon::responseFailed('json串为空,解析出错');
        }

        $model = new PsServiceForm;

        $model->setScenario('create');
        $model->load($this->request_params, ''); // 加载数据

        if ($model->validate()) { // 检验数据
            if ($data['header_type'] == 1) {
                if (!preg_match('/(((^https?:(?:\/\/)?)(?:[-;:&=\+\$,\w]+@)?[A-Za-z0-9.-]+|(?:www.|[-;:&=\+\$,\w]+@)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w-_]*)?\??(?:[-\+=&;%@.\w_]*)#?(?:[\w]*))?)$/', $data->link_url)) {
                    return PsCommon::responseFailed('请输入正确的网址');
                }
            }
            $result = ServiceService::service()->serviceUpdate($data, $this->user_info);
            return $result;
        } else {
            $errorMsg = array_values($model->errors);
            return PsCommon::responseFailed($errorMsg[0][0]);
        }
    }

    /**
     * 2016-12-23
     * 获取开通服务
     */
    public function actionService()
    {
        $list = Yii::$app->db->createCommand("SELECT id as value, name as label FROM ps_service where parent_id = :parent_id and status = :status")
            ->bindValue(":status", 1)
            ->bindValue(":parent_id", 0)
            ->queryAll();

        foreach ($list as $key => $val) {
            $model = Yii::$app->db->createCommand("SELECT id as value, name as label FROM ps_service where parent_id = :parent_id and status = :status")
                ->bindValue(":status", 1)
                ->bindValue(":parent_id", $val['value'])
                ->queryAll();

            $list[$key]['key'] = $val['value'];

            foreach ($model as $k => $v) {
                $model[$k]['key'] = $v['value'];
            }

            $list[$key]['children'] = $model;
        }

        return PsCommon::responseSuccess($list);
    }

    //获取服务类型接口
    public function actionTypes()
    {
        $typeList = ServiceService::service()->getTypes();
        return PsCommon::responseSuccess($typeList);
    }
}