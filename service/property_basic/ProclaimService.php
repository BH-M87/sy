<?php
namespace service\property_basic;

use Yii;
use yii\base\Exception;

use common\core\PsCommon;

use app\models\PsProclaim;

class ProclaimService extends BaseService
{
    public static $proclaim_type = ['1' => '通知', '2' => '新闻', '3' => '公告'];
    public static $proclaim_cate = ['1' => '文字', '2' => '新闻', '3' => '图文新增'];

    // 公告列表
    public function lists($data)
    {
        $communityId = PsCommon::get($data, "community_id"); // 小区id
        $page = (empty($data['page']) || $data['page'] < 1) ? 1 : $data['page'];
        $rows = !empty($data['rows']) ? $data['rows'] : 20;
        // ================================================数据验证操作==================================================
        if (!$communityId) {
            return $this->failed("请选择小区");
        }
        // 查询总数
        $count = $this->_Search($data)->count();
        if ($count == 0) {
            return $this->success(['totals' => 0, 'list' => []]);
        }
        $page = $page > ceil($count / $rows) ? ceil($count / $rows) : $page;
        $limit = ($page - 1) * $rows;
        // 列表
        $models = $this->_Search($data)->orderBy('create_at desc')->offset($limit)->limit($rows)->asArray()->all();
        foreach ($models as $key => $model) {
            $arr[$key]['id'] = $model['id'];
            $arr[$key]['title'] = $model['title'];
            $arr[$key]['proclaim_type_desc'] = !empty($model['proclaim_type']) ? self::$proclaim_type[$model['proclaim_type']] : '';
            $arr[$key]['is_show'] = $model['is_show'];
            $arr[$key]['is_top'] = $model['is_top'];
            $arr[$key]['operator_id'] = $model['operator_id'];
            $arr[$key]['operator_name'] = $model['operator_name'];
            $arr[$key]['show_at'] = !empty($model['show_at']) ? date("Y-m-d H:i", $model['show_at']) : '';
            $arr[$key]['create_at'] = !empty($model['create_at']) ? date("Y-m-d H:i", $model['create_at']) : '';
        }

        return $this->success(['totals' => $count, 'list' => $arr]);
    }

    // 公告新增
    public function add($params, $userInfo)
    {
        $params['operator_id'] = $userInfo['id'];
        $params['operator_name'] = $userInfo['truename'];
        $model = new PsProclaim();
        $model->create_at = time();
        $model->top_at = time();
        $model->scenario = 'add';  # 设置数据验证场景为 新增
        $model->load($params, '');   # 加载数据
        if ($model->validate()) {  # 验证数据
            if ($params['proclaim_cate'] != 1 && empty($model->img_url)) {
                return $this->failed("图片不能为空");
            }
            if ($params['proclaim_cate'] != 2 && empty($model->content)) {
                return $this->failed("内容不能为空");
            }
            if ($model->save()) {  # 保存新增数据
                $content = "公告名称:" . $params['title'] . ',';
                $operate = [ "community_id" => $params['community_id'],
                    "operate_menu" => "物业服务",
                    "operate_type" => "新增物业公告",
                    "operate_content" => $content,
                ];
                OperateService::addComm($userInfo, $operate);
            }
            return $this->success();
        }
        return $this->failed($model->getErrors());
    }

    // 公告编辑
    public function edit($params, $userInfo)
    {
        if (!empty($params['id'])) {
            $model = PsProclaim::findOne($params['id']);
            if (!$model) {
                return $this->failed("数据不存在");
            }
            if ($model->is_show == 2) {
                return $this->failed("数据已线上不可编辑");
            }
            $model->top_at = time();
            $model->scenario = 'edit';  # 设置数据验证场景为 编辑
            $model->load($params, '');   # 加载数据
            if ($model->validate()) {  # 验证数据
                if ($params['proclaim_cate'] != 1 && empty($model->img_url)) {
                    return $this->failed("图片不能为空");
                }
                if ($params['proclaim_cate'] != 2 && empty($model->content)) {
                    return $this->failed("内容不能为空");
                }
                if ($model->save()) {  # 保存新增数据
                    $content = "公告名称:" . $params['title'];
                    $operate = ["community_id" => $params['community_id'],
                        "operate_menu" => "物业服务",
                        "operate_type" => "编辑物业公告",
                        "operate_content" => $content,
                    ];
                    OperateService::addComm($userInfo, $operate);
                }
                return $this->success();
            }
            return $this->failed($model->getErrors());
        }
        return $this->failed("id不能为空");
    }

    // 公告是否显示
    public function editShow($params, $userInfo)
    {
        if (!empty($params['id'])) {
            $model = PsProclaim::findOne($params['id']);
            if (!$model) {
                return $this->failed("数据不存在");
            }
            if (empty($params['is_show'])) {
                return $this->failed("是否显示不能为空");
            }
            $model->show_at = time();
            $model->scenario = 'edit_show';  # 设置数据验证场景为 编辑
            $model->load($params, '');   # 加载数据
            if ($model->validate()) {  # 验证数据
                if ($params['is_show'] == 1) {//取消显示清空显示时间
                    $model->show_at = 0;
                }
                if ($model->save()) {  # 保存新增数据
                    $content = "公告名称:" . $model->title;
                    $operate = ["community_id" => $params['community_id'],
                        "operate_menu" => "物业服务",
                        "operate_type" => $model->is_show==1?"隐藏物业公告":"显示物业公告",
                        "operate_content" => $content,
                    ];
                    OperateService::addComm($userInfo, $operate);
                }
                return $this->success();
            }
            return $this->failed($model->getErrors());
        }
        return $this->failed("id不能为空");
    }

    // 公告是否置顶
    public function editTop($params, $userInfo)
    {
        if (!empty($params['id'])) {
            $model = PsProclaim::findOne($params['id']);
            if (!$model) {
                return $this->failed("数据不存在");
            }
            if (empty($params['is_top'])) {
                return $this->failed("是否置顶不能为空");
            }
            $model->top_at = time();
            $model->scenario = 'edit_top';  # 设置数据验证场景为 编辑
            $model->load($params, '');   # 加载数据

            if ($model->validate()) {  # 验证数据
                if ($params['is_top'] == 1) {//取消置顶情况置顶时间
                    $model->top_at = 0;
                }
                if ($model->save()) {  # 保存新增数据
                    $content = "公告名称:" . $model->title;
                    $operate = ["community_id" => $params['community_id'],
                        "operate_menu" => "物业服务",
                        "operate_type" => "编辑置顶物业公告",
                        "operate_content" => $content,
                    ];
                    OperateService::addComm($userInfo, $operate);
                }
                return $this->success();
            }
            return $this->failed($model->getErrors());
        }
        return $this->failed("id不能为空");
    }

    // 公告详情
    public function show($params)
    {
        if (!empty($params['id'])) {
            $model = PsProclaim::find()->where(['id' => $params['id']])->asArray()->one();
            if (!$model) {
                return $this->failed("数据不存在");
            }
            $data = $model;
            $data['create_at'] = !empty($data['create_at'])?date("Y-m-d H:i",$data['create_at']):'';
            $data['show_at'] = !empty($data['show_at'])?date("Y-m-d H:i",$data['show_at']):'';
            $data['top_at'] = !empty($data['top_at'])?date("Y-m-d H:i",$data['top_at']):'';
            return $this->success($data);
        }
        return $this->failed("id不能为空");
    }

    // 公告删除
    public function del($params, $userInfo)
    {
        if (!empty($params['id'])) {
            $model = PsProclaim::findOne($params['id']);
            if (!$model) {
                return $this->failed("数据不存在");
            }
            if ($model->is_show == 2) {
                return $this->failed("数据已线上不可删除");
            }
            $model->scenario = 'del';  # 设置数据验证场景为 编辑
            $model->load($params, '');   # 加载数据
            if ($model->validate()) {  # 验证数据
                if ($model->delete()) {  # 保存新增数据
                    $content = "公告名称:" . $model->title;
                    $operate = ["community_id" => $params['community_id'],
                        "operate_menu" => "物业服务",
                        "operate_type" => "删除物业公告",
                        "operate_content" => $content,
                    ];
                    OperateService::addComm($userInfo, $operate);
                }
                return $this->success();
            }
            return $this->failed($model->getErrors());
        }
        return $this->failed("id不能为空");
    }

    // 账单搜索
    private function _Search($params)
    {
        $start_date = PsCommon::get($params, "start_date");
        $end_date = PsCommon::get($params, "end_date");
        $start_date_time = !empty($start_date) ? strtotime($start_date) : '';
        $end_date_time = !empty($end_date) ? strtotime($end_date . ' 23:59:59') : '';
        $model = PsProclaim::find()
            ->andFilterWhere(['=', 'community_id', PsCommon::get($params, "community_id")])
            ->andFilterWhere(['=', 'proclaim_type', PsCommon::get($params, "proclaim_type")])
            ->andFilterWhere(['like', 'title', PsCommon::get($params, "title")])
            ->andFilterWhere(['and', ['>=', 'create_at', $start_date_time], ['<=', 'create_at', $end_date_time]]);
        return $model;
    }
}