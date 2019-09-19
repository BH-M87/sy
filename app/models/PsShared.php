<?php

namespace app\models;

use service\rbac\OperateService;
use Yii;

/**
 * This is the model class for table "ps_shared".
 *
 * @property integer $id
 * @property integer $community_id
 * @property integer $shared_type
 * @property string $name
 * @property integer $panel_type
 * @property integer $panel_status
 * @property string $start_num
 * @property string $remark
 * @property integer $create_at
 */
class PsShared extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_shared';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'required', 'message' => '{attribute}不能为空!', 'on' => 'show'],
            [['community_id', 'shared_type', 'name', 'panel_type', 'panel_status', 'start_num', 'create_at'], 'required', 'message' => '{attribute}不能为空!', 'on' => 'add'],
            [['community_id', 'shared_type'], 'required', 'message' => '{attribute}不能为空!', 'on' => 'search'],
            [['id', 'community_id', 'shared_type', 'name', 'panel_type', 'panel_status', 'start_num'], 'required', 'message' => '{attribute}不能为空!', 'on' => 'edit'],
            [['panel_type', 'shared_type', 'panel_status'], 'in', 'range' => [1, 2, 3], 'message' => '{attribute}取值范围错误', 'on' => ['edit', 'add', 'search']],
            [['id', 'community_id', 'shared_type', 'panel_type', 'panel_status'], 'integer'],
            [['start_num'], 'number'],
            [['name'], 'string', 'max' => 15],
            [['remark'], 'string', 'max' => 200],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '项目id',
            'community_id' => '小区id',
            'shared_type' => '公摊类型',
            'name' => '项目名称',
            'panel_type' => '对应表盘',
            'panel_status' => '表盘状态',
            'start_num' => '起始度数',
            'remark' => '备注',
            'create_at' => 'Create At',
        ];
    }

    /**
     * 获取数据
     * @author yjh
     * @param $data
     * @param bool $page
     * @return array
     */
    public static function getData($data,$page=true)
    {
        $return = [];
        $shared_meter = self::find()->where($data['where'])->andWhere($data['like'])->orderBy([ 'id' => SORT_DESC]);
        if ($page) {
            $page = !empty($data['page']) ? $data['page'] : 1;
            $row = !empty($data['row']) ? $data['row'] : 10;
            $page = ($page-1)*$row;
            $countQuery = clone $shared_meter;
            $count = $countQuery->count();
            $return['totals'] = $count;
            $shared_meter->offset($page)->limit($row);
        }
        $models = $shared_meter->asArray()->all();
        foreach ($models as $key => $shared) {
            $arr[$key]['id'] = $shared['id'];
            $arr[$key]['community_id'] = $shared['community_id'];
            $arr[$key]['name'] = $shared['name'];
            $arr[$key]['start_num'] = $shared['start_num'];
            $arr[$key]['remark'] = $shared['remark'];
            $arr[$key]['shared_type'] = PsCommon::$sharedType[$shared['shared_type']];
            $arr[$key]['panel_type'] = $shared['panel_type'] == 1 ? '水表' : '电表';
            $arr[$key]['panel_status'] = $shared['panel_status'];
            $arr[$key]['panel_status_msg'] = $shared['panel_status'] == 1 ? '正常' : '异常';
            $arr[$key]['create_at'] = date('Y-m-d H:i:s', $shared['create_at']);
        }
        $return['list'] = $models;
        return $return;
    }

    /**
     * 删除数据
     * @author yjh
     * @param $id
     * @return bool
     */
    public static function deleteData($id,$userinfo='')
    {
        $model = self::findOne($id);
        if (!empty($model)) {
            //保存日志
            $log = [
                "community_id" => $model->community_id,
                "operate_menu" => "仪表信息",
                "operate_type" => "删除电表",
                "operate_content" => $model->name
            ];
            OperateService::addComm($userinfo, $log);
           return $model->delete();
        }
        return false;
    }
}
