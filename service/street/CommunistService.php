<?php
/**
 * 党员接口相关服务
 * User: wenchao.feng
 * Date: 2019/9/4
 * Time: 18:23
 */

namespace service\street;


use app\models\StCommunist;
use common\core\F;
use common\core\PsCommon;
use common\MyException;

class CommunistService extends BaseService
{
    public $_types = [
        1 => ['id' => 1, 'name' => '在职党员'],
        2 => ['id' => 2, 'name' => '在册党员'],
        3 => ['id' => 3, 'name' => '发展党员'],
        4 => ['id' => 4, 'name' => '其他'],
    ];

    public $_sex = [
        1 => ['id' => 1, 'name' => '男'],
        2 => ['id' => 2, 'name' => '女']
    ];

    public function getCommon()
    {
        $comm = [
            'type' => $this->returnIdName($this->_types)
        ];
        return $comm;
    }

    public function getList($page, $rows, $params)
    {
        $query = StCommunist::find()
            ->alias('sc')
            ->leftJoin('st_station st', 'sc.station_id = st.id')
            ->where("1=1");
        if (!empty($params['name'])) {
            $query->andWhere(['like', 'sc.name', $params['name']]);
        }
        if (!empty($params['branch'])) {
            $query->andWhere(['like', 'sc.branch', $params['branch']]);
        }
        if (!empty($params['type'])) {
            $query->andWhere(['sc.type' => $params['type']]);
        }
        $re['totals'] = $query->select('id')->count();
        $communistList = $query->select('sc.id, sc.name, sc.mobile, sc.sex, sc.birth_time, sc.join_party_time,
         sc.formal_time, sc.branch, sc.job, sc.type, sc.station_id, sc.is_authentication, st.station')
            ->offset((($page - 1) * $rows))
            ->limit($rows)
            ->orderBy('id desc')
            ->asArray()
            ->all();
        foreach ($communistList as $k => $v) {
            $communistList[$k]['mobile'] = F::processMobile($v['mobile']);
            $communistList[$k]['sex_info'] = $this->_sex[$v['sex']];
            $communistList[$k]['birth_time'] = $v['birth_time'] ? date("Y-m-d", $v['birth_time']) : '';
            $communistList[$k]['join_party_time'] = $v['join_party_time'] ? date("Y-m-d", $v['join_party_time']) : '';
            $communistList[$k]['formal_time'] = $v['formal_time'] ? date("Y-m-d", $v['formal_time']) : '';
            $communistList[$k]['type_info'] = $this->_types[$v['type']];
            $communistList[$k]['station_info'] = [
                'id' => $v['station_id'],
                'name' => $v['station']
            ];
            unset($communistList[$k]['type']);
            unset($communistList[$k]['station']);
            unset($communistList[$k]['station_id']);
        }
        $re['list'] = $communistList;
        return $re;
    }

    public function view($params)
    {
        $info = StCommunist::find()
            ->alias('sc')
            ->select('sc.id, sc.image,sc.name, sc.mobile, sc.sex, sc.birth_time, sc.join_party_time,
         sc.formal_time, sc.branch, sc.job, sc.type, sc.station_id, sc.is_authentication, st.station')
            ->leftJoin('st_station st', 'sc.station_id = st.id')
            ->where(['sc.id' => $params['id']])
            ->asArray()
            ->one();
        $info['sex_info'] = $this->_sex[$info['sex']];
        $info['birth_time'] = $info['birth_time'] ? date("Y-m-d", $info['birth_time']) : '';
        $info['join_party_time'] = $info['join_party_time'] ? date("Y-m-d", $info['join_party_time']) : '';
        $info['formal_time'] = $info['formal_time'] ? date("Y-m-d", $info['formal_time']) : '';
        $info['type_info'] = $this->_types[$info['type']];
        $info['station_info'] = [
            'id' => $info['station_id'],
            'name' => $info['station']
        ];
        unset($info['type']);
        unset($info['station']);
        unset($info['station_id']);
        return $info;
    }

    public function delete($params)
    {
        $model = $this->getData($params);
        $model->is_del = 2;
        if ($model->save()) {
            return true;
        } else {
            throw new MyException($this->getError($model));
        }
    }

    private function getData($params)
    {
        $info = StCommunist::findOne($params['id']);
        if (!$info) {
            throw new MyException("党员记录不存在！");
        }
        return $info;
    }

}