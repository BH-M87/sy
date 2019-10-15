<?php

namespace app\modules\manage\controllers;

use app\models\Department;
use common\MyException;
use Yii;
use service\manage\CommunityService;
use common\core\PsCommon;
use service\common\AreaService;
use app\models\PsHouseForm;

class HouseController extends BaseController
{
    /**
     * @author wenchao.feng
     * 获取省市区所有数据
     */
    public function actionArea()
    {
        return AreaService::service()->getCacheArea();
    }


    /**
     * 街道列表
     * @author yjh
     * @return string
     * @throws MyException
     */
    public function actionStreet()
    {
        if (empty($this->request_params['area_code'])) {
            throw new MyException('区编码不能为空');
        }
        $id = Department::find()->select('id')->where(['status' => 1,'org_code' => $this->request_params['area_code']])->asArray()->one()['id'];
        if (!empty($id)) {
            $data = Department::find()->select('id,org_code as code,department_name as name')->where(['status' => 1,'node_type' => 1 ,'parent_id' => $id])->asArray()->all();
        }
        return PsCommon::responseSuccess(['list' => $data ?? []]);
    }

    /**
     * 社区列表
     * @author yjh
     * @return string
     * @throws MyException
     */
    public function actionCommunity()
    {
        if (empty($this->request_params['street_id'])) {
            throw new MyException('街道ID不能为空');
        }
        $data = Department::find()->select('id,org_code as code,department_name as name')->where(['status' => 1,'node_type' => 2 ,'parent_id' => $this->request_params['street_id']])->asArray()->all();
        return PsCommon::responseSuccess(['list' => $data ?? []]);
    }
}
