<?php
/**
 * 先锋岗位相关接口
 * User: wenchao.feng
 * Date: 2019/9/5
 * Time: 16:20
 */

namespace service\street;


use app\models\StPartyTask;
use app\models\StStation;
use common\MyException;

class StationService extends BaseService
{
    public function add($params, $userInfo = [])
    {
        //查询数据是否重复
        $tmpModel = StStation::find()
            ->where(['station' => $params['station'], 'organization_type' => $params['organization_type'],
                'organization_id' => $params['organization_id']])
            ->asArray()
            ->one();
        if ($tmpModel) {
            throw new MyException("先锋岗名称重复！");
        }
        $params['operator_id'] = $userInfo['id'];
        $params['operator_name'] = $userInfo['username'];
        $params['create_at'] = time();
        $model = new StStation();
        $model->load($params, '');
        if ($model->save()) {
            return $model->id;
        }
        throw new MyException($this->getError($model));
    }

    //编辑
    public function edit($params, $userInfo = [])
    {
        $model = $this->getData($params);
        //查询数据是否重复
        $tmpModel = StStation::find()
            ->where(['station' => $params['station'],'organization_type' => $params['organization_type'],
                'organization_id' => $params['organization_id']])
            ->andWhere(['!=', 'id', $params['id']])
            ->asArray()
            ->one();
        if ($tmpModel) {
            throw new MyException("先锋岗名称重复！");
        }
        $model->load($params, '');
        if ($model->save()) {
            return $model->id;
        }
        throw new MyException($this->getError($model));
    }

    //显示或隐藏
    public function editStatus($params, $userInfo = [])
    {
        $model = $this->getData($params);
        if ($model->status == $params['status']) {
            $statusDesc = $model->status == 1 ? '显示' : '隐藏';
            throw new MyException("先锋岗已经{$statusDesc}！");
        }
        $model->status = $params['status'];
        if ($model->save()) {
            return $model->id;
        }
        throw new MyException($this->getError($model));
    }

    public function view($params)
    {
        $model = $this->getData($params);
        $re['id'] = $model->id;
        $re['name'] = $model->station;
        $re['content'] = $model->content;
        $re['status'] = $model->status;
        $re['created_at'] = $model->create_at ? date("Y-m-d H:i", $model->create_at) : '';
        return $re;
    }

    public function delete($params)
    {
        $model = $this->getData($params);
        //查询任务数
        $partyTask = StPartyTask::find()
            ->select('count(id)')
            ->where(['station_id' => $params['id']])
            ->asArray()
            ->scalar();
        if ($partyTask > 0) {
            throw new MyException("此先锋岗下有任务，无法删除！");
        }
        if ($model->delete()) {
            return true;
        }
        throw new MyException($this->getError($model));
    }

    public function getList($page, $rows, $params)
    {
        $query = StStation::find()
            ->alias('st')
            ->where(['st.organization_type' => $params['organization_type'], 'st.organization_id' => $params['organization_id']]);
        if (!empty($params['name'])) {
            $query->andWhere(['like', 'st.station', $params['name']]);
        }
        if (!empty($params['status'])) {
            $query->andWhere(['st.status' => $params['status']]);
        }
        $re['totals'] = $query->select('id')->count();
        $list = $query->select('st.id, st.station name, st.content, st.status, st.create_at')
            ->offset((($page - 1) * $rows))
            ->limit($rows)
            ->orderBy('id desc')
            ->asArray()
            ->all();
        foreach ($list as $k => $v) {
            $list[$k]['task_num'] = StPartyTask::find()
                ->select('count(id)')
                ->where(['station_id' => $v['id']])
                ->asArray()
                ->scalar();
            $list[$k]['created_at'] = $v['create_at'] ? date("Y-m-d H:i", $v['create_at']) : '';
            unset($list[$k]['create_at']);
        }
        $re['list'] = $list;
        return $re;
    }

    public function getSimpleList($params)
    {
        $list = StStation::find()
            ->alias('st')
            ->where(['st.organization_type' => $params['organization_type'], 'st.organization_id' => $params['organization_id'], 'st.status' => 1])
            ->select('st.id, st.station name')
            ->orderBy('id desc')
            ->asArray()
            ->all();
        return $re['list'] = $list;
    }

    private function getData($params)
    {
        $info = StStation::findOne($params['id']);
        if (!$info) {
            throw new MyException("先锋岗记录不存在！");
        }
        return $info;
    }

}