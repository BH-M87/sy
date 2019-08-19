<?php
/**
 * 业主投诉
 * @author shenyang
 * @date 2017/11/13
 */
namespace service\property_basic;

use common\core\F;
use common\core\PsCommon;
use app\models\PsCommunityModel;
use app\models\PsComplaint;
use app\models\PsComplaintImages;
use app\models\PsMember;
use service\BaseService;

Class ComplaintService extends BaseService
{
    const STATUS_TODO = 1;
    const STATUS_CANCEL = 2;
    const STATUS_DONE = 3;

    public $types = [
        1 => ['id' => 1, 'name' => '我要投诉'],
        2 => ['id' => 2, 'name' => '我要建议'],
    ];

    public $status = [
        1 => ['id' => 1, 'name' => '待处理'],
        2 => ['id' => 2, 'name' => '已取消'],
        3 => ['id' => 3, 'name' => '已处理'],
    ];

    /**
     * 搜索
     * @param $params
     */
    private function _search($params)
    {
        $model = new PsComplaint();
        return $model->find()->alias('t')
            ->leftJoin(['m' => PsMember::tableName()], 't.member_id=m.id')
            ->leftJoin(['c' => PsCommunityModel::tableName()], 't.community_id=c.id')
            ->filterWhere([
                't.community_id' => PsCommon::get($params, 'community_id'),
                't.status' => PsCommon::get($params, 'status'),
                't.type' => PsCommon::get($params, 'type'),
            ])
            ->andFilterWhere(['like', 'm.name', PsCommon::get($params, 'username')])
            ->andFilterWhere(['like', 'm.mobile', PsCommon::get($params, 'mobile')]);
    }

    /**
     * 后台搜索列表
     * @param $params
     * @param $page
     * @param $pageSize
     */
    public function getList($params, $page, $pageSize)
    {
        $data = $this->_search($params)
            ->select('t.community_id, c.name as community_name, m.name as username, m.mobile, t.id, t.member_id, t.content, t.type, 
            t.status, t.create_at')
            ->offset(($page - 1) * $pageSize)->limit($pageSize)
            ->orderBy('id desc')
            ->asArray()->all();
        $total = $this->getListCount($params);

        $result = [];
        $i = $total - ($page-1)*$pageSize;
        foreach ($data as $v) {
            $v['hide_mobile'] = !empty($v['mobile'])?mb_substr($v['mobile'],0,3)."****".mb_substr($v['mobile'],-4):"";
            $v['type'] = PsCommon::get($this->types, $v['type'], []);
            $v['status'] = PsCommon::get($this->status, $v['status'], []);
            $v['create_at'] = date('Y-m-d H:i:s', $v['create_at']);
            $v['content'] = F::cutString($v['content'], 15);
            $v['tid'] = $i--;//编号
            $result[] = $v;
        }
        return ['list' => $result, 'totals' => $total];
    }

    /**
     * 后台搜索记录
     * @param $params
     */
    public function getListCount($params)
    {
        return intval($this->_search($params)->count());
    }

    /**
     * 标记为已完成
     * @param $id
     * @param $content
     */
    public function done($id, $communityId, $content,$userinfo='')
    {
        $model = PsComplaint::findOne(['id' => $id, 'community_id' => $communityId]);
        if (!$model) {
            return $this->failed('数据不存在');
        }
        if ($model->status != self::STATUS_TODO) {
            return $this->failed('只有待处理状态才可以标记');
        }
        $model->status = self::STATUS_DONE;
        $model->handle_content = $content;
        $model->handle_at = time();
        if ($model->validate() && $model->save()) {
            $operate = [
                "community_id" =>$communityId,
                "operate_menu" => "投诉建议",
                "operate_type" => "处理投诉建议",
                "operate_content" => '投诉建议内容：'.$model->content.'-处理内容'.$content
            ];
            OperateService::addComm($userinfo, $operate);
            return $this->success();
        }
        return $this->failed($this->getError($model));
    }

    /**
     * 详情
     * @param $id
     * @param $communityId
     */
    public function detail($id, $communityId, $memberId = null)
    {
        $data = PsComplaint::find()->alias('t')
            ->select('m.name as username, m.mobile, t.*')
            ->leftJoin(['m' => PsMember::tableName()], 't.member_id=m.id')
            ->where(['t.id' => $id, 'community_id' => $communityId])
            ->andFilterWhere(['member_id' => $memberId])
            ->asArray()->one();
        if (!$data) {
            return $this->failed('数据不存在');
        }
        $images = $this->findImages($id);
        $data['images'] = $images;
        $data['type'] = PsCommon::get($this->types, $data['type'], []);
        $data['status'] = PsCommon::get($this->status, $data['status'], []);
        $data['create_at'] = date('Y-m-d H:i:s', $data['create_at']);
        $data['handle_at'] = $data['handle_at'] ? date('Y-m-d H:i:s', $data['handle_at']) : '';
        return $this->success($data);
    }

    /**
     * 投诉图片
     * @param $id
     */
    public function findImages($id)
    {
        return PsComplaintImages::find()->select('img')
            ->where(['complaint_id' => $id])
            ->column();
    }

    /**
     * 新增投诉
     * @param $params
     */
    public function create($memberId, $communityId, $params)
    {
        $model = new PsComplaint();
        $model->member_id = $memberId;
        $model->community_id = $communityId;
        $model->type = PsCommon::get($params, 'type');
        $model->content = PsCommon::get($params, 'content');
        $model->status = self::STATUS_TODO;
        $model->create_at = time();
        $model->day = date('Y-m-d');
        if ($model->validate() && $model->save()) {
            if (!empty($params['images']) && is_array($params['images'])) {
                $data['img'] = $params['images'];
                $data['complaint_id'] = [$model->id];
                PsComplaintImages::model()->batchInsert($data, true);
            }
            $member = MemberService::service()->getInfo($memberId);
            //添加监控实时推送数据
            if ($member) {
                MonitorService::service()->dynamicComplaint($communityId, $member['name']);
            }
            return $this->success();
        }
        return $this->failed($this->getError($model));
    }

    /**
     * 投诉记录
     * @param $appUserId
     */
    public function record($memberId, $communityId)
    {
        $data = PsComplaint::find()->select('id, member_id, content, type, status, create_at')
            ->where(['member_id' => $memberId, 'community_id' => $communityId])
            ->orderBy('id desc')
            ->limit(100)
            ->asArray()->all();
        $result = [];
        foreach ($data as $v) {
            $v['type'] = PsCommon::get($this->types, $v['type'], []);
            $v['status'] = PsCommon::get($this->status, $v['status'], []);
            $v['create_at'] = date('Y-m-d H:i:s', $v['create_at']);
            $result[] = $v;
        }
        return $result;
    }

    /**
     * 取消投诉
     * @param $id
     */
    public function cancel($id, $memberId, $communityId)
    {
        $r = PsComplaint::updateAll(['status' => self::STATUS_CANCEL],
            ['id' => $id, 'member_id' => $memberId, 'community_id' => $communityId]);
        if (!$r) {
            return $this->failed('取消失败');
        }
        return $this->success();
    }

    // 投诉量
    public function complaintNum($param, $communitys)
    {
        $communityIds = array_column($communitys, 'id');
        $list = PsComplaint::getComplaintNum($param, $communityIds);
        $list = $list ? $list : [];
        //按照community_id分组
        $data = [];
        foreach ($list as $v) {
            if (!empty($param['type'])) { // 投诉率
                $v['complaint'] = round($v['complaint'], 4) * 100;
            }

            $data[$v['community_id']] = $v;
        }

        foreach ($communitys as $community) {
            $result['xAxis'][] = $community['name'];
            $result['yAxis']['data'][] = !empty($data[$community['id']]['complaint']) ? $data[$community['id']]['complaint'] : 0;
        }
        $result['yAxis']['name']  = !empty($param['type']) ? '投诉率对比' : '投诉量对比';
        $result['yAxis']['stack'] = '投诉';
        $result['yAxis']['type']  = 'bar';

        return $result;
    }
}
