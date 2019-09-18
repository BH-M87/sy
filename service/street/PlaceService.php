<?php
/**
 * 场地相关服务
 * User: wenchao.feng
 * Date: 2019/9/5
 * Time: 17:52
 */

namespace service\street;


use app\models\StCompany;
use app\models\StPlace;
use common\core\F;
use common\MyException;

class PlaceService extends BaseService
{
    public $_weekDay = [
        1 => ['id' => 1, 'name' => '周一'],
        2 => ['id' => 2, 'name' => '周二'],
        3 => ['id' => 3, 'name' => '周三'],
        4 => ['id' => 4, 'name' => '周四'],
        5 => ['id' => 5, 'name' => '周五'],
        6 => ['id' => 6, 'name' => '周六'],
        7 => ['id' => 7, 'name' => '周日'],
    ];

    public function getCommon()
    {
        $comm = [
            'weekday' => $this->returnIdName($this->_weekDay)
        ];
        return $comm;
    }

    public function add($params, $userInfo = [])
    {
        $companyId = F::value($params, 'company_id', 0);
        if ($companyId) {
            $this->getCompanyData($params['company_id']);
        }

        //查询数据是否重复
        $tmpModel = StPlace::find()
            ->where(['name' => $params['name'],'organization_type' => $params['organization_type'],
                'organization_id' => $params['organization_id']])
            ->asArray()
            ->one();
        if ($tmpModel) {
            throw new MyException("场地已存在！");
        }

        $params['operator_id'] = $userInfo['id'];
        $params['operator_name'] = $userInfo['username'];
        $params['create_at'] = time();
        $model = new StPlace();
        $model->load($params, '');
        if ($model->save()) {
            return $model->id;
        }
        throw new MyException($this->getError($model));
    }

    public function edit($params, $userInfo = [])
    {
        $model = $this->getData($params);
        $companyId = F::value($params, 'company_id', 0);
        if ($companyId) {
            $this->getCompanyData($params['company_id']);
        }
        //查询数据是否重复
        $tmpModel = StPlace::find()
            ->where(['name' => $params['name']])
            ->andWhere(['!=', 'id', $params['id']])
            ->andWhere(['organization_type' => $params['organization_type'],
                'organization_id' => $params['organization_id']])
            ->asArray()
            ->one();
        if ($tmpModel) {
            throw new MyException("场地名称重复！");
        }
        $model->load($params, '');
        if ($model->save()) {
            return $model->id;
        }
        throw new MyException($this->getError($model));
    }

    public function view($params)
    {
        $model = $this->getData($params);
        $info = StPlace::find()
            ->alias('sp')
            ->select('sp.id, sp.name, sp.area, sp.open_start_weekday, sp.open_end_weekday, sp.open_start_time,
             sp.open_end_time, sp.contact_name, sp.contact_mobile, sp.address, sp.note, sp.people_num, sp.company_id, sc.name as company_name')
            ->leftJoin('st_company sc', 'sc.id = sp.company_id')
            ->where(['sp.id' => $params['id']])
            ->asArray()
            ->one();
        $info['open_start_weekday_info'] = $this->_weekDay[$info['open_start_weekday']];
        $info['open_end_weekday_info'] = $this->_weekDay[$info['open_end_weekday']];
        $info['company_info'] = [
            'id' => $info['company_id'],
            'name' => $info['company_name']
        ];
        $info['area'] = $info['area'] ? sprintf("%.1f",$info['area']) : '';
        unset($info['open_start_weekday']);
        unset($info['open_end_weekday']);
        return $info;
    }

    public function delete($params)
    {
        $model = $this->getData($params);
        if ($model->delete()) {
            return true;
        }
        throw new MyException($this->getError($model));
    }

    public function getList($page, $rows, $params)
    {
        $query = StPlace::find()
            ->alias('sp')
            ->leftJoin('st_company sc', 'sc.id = sp.company_id')
            ->where(['sp.organization_type' => $params['organization_type'], 'sp.organization_id' => $params['organization_id']]);
        if (!empty($params['company_name'])) {
            $query->andWhere(['like', 'sc.name', $params['company_name']]);
        }
        if (!empty($params['name'])) {
            $query->andWhere(['like', 'sp.name', $params['name']]);
        }
        if (!empty($params['contact_name'])) {
            $query->andWhere(['or', ['like', 'sp.contact_name', $params['contact_name'] ], ['like', 'sp.contact_mobile', $params['contact_name']]]);
        }
        if (!empty($params['area_min'])) {
            $query->andWhere(['>=', 'sp.area', $params['area_min']]);
        }
        if (!empty($params['area_max'])) {
            $query->andWhere(['<=', 'sp.area', $params['area_max']]);
        }
        $re['totals'] = $query->select('id')->count();
        $list = $query->select('sp.id, sp.name, sp.area, sp.open_start_weekday, sp.open_end_weekday, sp.open_start_time,
             sp.open_end_time, sp.contact_name, sp.contact_mobile, sp.address, sp.note, sp.people_num, sp.company_id, sp.create_at, sc.name as company_name')
            ->offset((($page - 1) * $rows))
            ->limit($rows)
            ->orderBy('id desc')
            ->asArray()
            ->all();
        foreach ($list as $k => $v) {
            $list[$k]['open_start_weekday_info'] = $this->_weekDay[$v['open_start_weekday']];
            $list[$k]['open_end_weekday_info'] = $this->_weekDay[$v['open_end_weekday']];
            $list[$k]['company_info'] = [
                'id' => $v['company_id'],
                'name' => $v['company_name']
            ];
            $list[$k]['area'] = $v['area'] ? sprintf("%.1f",$v['area']) : '';
            $list[$k]['contact_mobile'] = $v['contact_mobile'] ? F::processMobile($v['contact_mobile']) : '';
            $list[$k]['created_at'] = $v['create_at'] ? date("Y-m-d H:i", $v['create_at']) : '';
            unset($list[$k]['create_at']);
            unset($list[$k]['open_end_weekday']);
            unset($list[$k]['open_start_weekday']);
        }
        $re['list'] = $list;
        return $re;
    }

    private function getData($params)
    {
        $info = StPlace::findOne($params['id']);
        if (!$info) {
            throw new MyException("场地记录不存在！");
        }
        return $info;
    }

    private function getCompanyData($companyId)
    {
        $info = StCompany::findOne($companyId);
        if (!$info) {
            throw new MyException("单位不存在！");
        }
        return $info;
    }
}