<?php
/**
 * 群团组织相关服务
 * User: wenchao.feng
 * Date: 2019/10/21
 * Time: 17:52
 */

namespace service\street;


use app\models\StOrganization;
use common\core\F;
use common\MyException;

class OrganizationService extends BaseService
{
    public $_types = [
        1 => ['id' => 1, 'name' => '群团组织'],
        2 => ['id' => 2, 'name' => '志愿者组织'],
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
        $tmpModel = StOrganization::find()
            ->where(['name' => $params['name'], 'organization_type' => $params['organization_type'],
                'organization_id' => $params['organization_id']])
            ->asArray()
            ->one();
        if ($tmpModel) {
            throw new MyException("群团组织已存在！");
        }

        $params['operator_id'] = $userInfo['id'];
        $params['operator_name'] = $userInfo['username'];
        $params['create_at'] = time();
        $model = new StOrganization();
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
        $tmpModel = StOrganization::find()
            ->where(['name' => $params['name']])
            ->andWhere(['!=', 'id', $params['id']])
            ->andWhere(['organization_type' => $params['organization_type'],
                'organization_id' => $params['organization_id']])
            ->asArray()
            ->one();
        if ($tmpModel) {
            throw new MyException("群团组织名称重复！");
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
        $re['org_type'] = $model->org_type;
        $re['contact_name'] = $model->contact_name;
        $re['contact_mobile'] = $model->contact_mobile;
        $re['type_info'] = $this->_types[$model->org_type];
        $re['address'] = $model->address;
        $re['job'] = $model->job;
        $re['lon'] = $model->lon;
        $re['lat'] = $model->lat;
        $re['member_num'] = $model->member_num;
        $re['created_at'] = $model->create_at ? date("Y-m-d H:i", $model->create_at) : '';
        $re['org_build_time'] = $model->org_build_time ? date("Y-m-d", $model->org_build_time) : '';
        return $re;
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
        $query = StOrganization::find()
            ->alias('so')
            ->where(['so.organization_type' => $params['organization_type'], 'so.organization_id' => $params['organization_id']]);
        if (!empty($params['name'])) {
            $query->andWhere(['like', 'so.name', $params['name']]);
        }
        if (!empty($params['org_type'])) {
            $query->andWhere(['so.org_type' => $params['org_type']]);
        }
        if (!empty($params['contact_name'])) {
            $query->andWhere(['or', ['like', 'so.contact_name', $params['contact_name'] ], ['like', 'so.contact_mobile', $params['contact_name']]]);
        }
        $re['totals'] = $query->select('id')->count();
        $list = $query->select('so.id, so.name,so.org_type,so.org_build_time,so.member_num,
        so.address,so.lat,so.lon,so.contact_name,so.contact_mobile,so.job,so.create_at')
            ->offset((($page - 1) * $rows))
            ->limit($rows)
            ->orderBy('so.id desc')
            ->asArray()
            ->all();
        foreach ($list as $k => $v) {
            $list[$k]['contact_mobile'] = F::processMobile($v['contact_mobile']);
            $list[$k]['type_info'] = $this->_types[$v['org_type']];
            $list[$k]['created_at'] = $v['create_at'] ? date("Y-m-d H:i", $v['create_at']) : '';
            $list[$k]['org_build_time'] = $v['org_build_time'] ? date("Y-m-d", $v['org_build_time']) : '';
            unset($list[$k]['create_at']);
        }
        $re['list'] = $list;
        return $re;
    }


    private function getData($params)
    {
        $info = StOrganization::findOne($params['id']);
        if (!$info) {
            throw new MyException("群团组织记录不存在！");
        }
        return $info;
    }
}