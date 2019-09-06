<?php
/**
 * 单位相关服务
 * User: wenchao.feng
 * Date: 2019/9/5
 * Time: 17:52
 */

namespace service\street;


use app\models\StCompany;
use app\models\StPlace;
use common\core\F;
use common\MyException;

class CompanyService extends BaseService
{
    public $_types = [
        1 => ['id' => 1, 'name' => '国企'],
        2 => ['id' => 2, 'name' => '私企'],
        3 => ['id' => 3, 'name' => '事业'],
        4 => ['id' => 4, 'name' => '其他'],
    ];

    public function getCommon()
    {
        $comm = [
            'type' => $this->returnIdName($this->_types)
        ];
        return $comm;
    }

    public function add($params, $userInfo = [])
    {
        //查询数据是否重复
        $tmpModel = StCompany::find()
            ->where(['name' => $params['name']])
            ->asArray()
            ->one();
        if ($tmpModel) {
            throw new MyException("单位已存在！");
        }

        $params['operator_id'] = $userInfo['id'];
        $params['operator_name'] = $userInfo['username'];
        $params['create_at'] = time();
        $model = new StCompany();
        $model->load($params, '');
        if ($model->save()) {
            return $model->id;
        }
        throw new MyException($this->getError($model));
    }

    public function edit($params, $userInfo = [])
    {
        $model = $this->getData($params);
        //查询数据是否重复
        $tmpModel = StCompany::find()
            ->where(['name' => $params['name']])
            ->andWhere(['!=', 'id', $params['id']])
            ->asArray()
            ->one();
        if ($tmpModel) {
            throw new MyException("单位名称重复！");
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
        $re['id'] = $model->id;
        $re['name'] = $model->name;
        $re['contact_name'] = $model->contact_name;
        $re['contact_mobile'] = $model->contact_mobile;
        $re['type_info'] = $this->_types[$model->type];
        $re['contact_position'] = $model->contact_position;
        $re['address'] = $model->address;
        $re['lon'] = $model->lon;
        $re['lat'] = $model->lat;
        $re['created_at'] = $model->create_at ? date("Y-m-d H:i", $model->create_at) : '';
        return $re;
    }

    public function delete($params)
    {
        $model = $this->getData($params);
        //查询任务数
        $partyTask = StPlace::find()
            ->select('count(id)')
            ->where(['company_id' => $params['id']])
            ->asArray()
            ->scalar();
        if ($partyTask > 0) {
            throw new MyException("此单位下有场地，无法删除！");
        }
        if ($model->delete()) {
            return true;
        }
        throw new MyException($this->getError($model));
    }

    public function getList($page, $rows, $params)
    {
        $query = StCompany::find()
            ->alias('sc')
            ->where(['organization_type' => $params['organization_type'], 'organization_id' => $params['organization_id']]);
        if (!empty($params['name'])) {
            $query->andWhere(['like', 'sc.name', $params['name']]);
        }
        if (!empty($params['type'])) {
            $query->andWhere(['sc.type' => $params['type']]);
        }
        $re['totals'] = $query->select('id')->count();
        $list = $query->select('sc.id, sc.name, sc.contact_name, sc.contact_position, 
        sc.contact_mobile, sc.address, sc.type, sc.create_at')
            ->offset((($page - 1) * $rows))
            ->limit($rows)
            ->orderBy('id desc')
            ->asArray()
            ->all();
        foreach ($list as $k => $v) {
            $list[$k]['contact_mobile'] = F::processMobile($v['contact_mobile']);
            $list[$k]['type_info'] = $this->_types[$v['type']];
            $list[$k]['created_at'] = $v['create_at'] ? date("Y-m-d H:i", $v['create_at']) : '';
            unset($list[$k]['create_at']);
        }
        $re['list'] = $list;
        return $re;
    }

    public function getSimpleList($params)
    {
        $list = StCompany::find()
            ->alias('sc')
            ->where(['organization_type' => $params['organization_type'], 'organization_id' => $params['organization_id']])
            ->select('sc.id, sc.name')
            ->asArray()
            ->all();
        $re['list'] = $list;
        return $re;
    }

    private function getData($params)
    {
        $info = StCompany::findOne($params['id']);
        if (!$info) {
            throw new MyException("单位记录不存在！");
        }
        return $info;
    }
}