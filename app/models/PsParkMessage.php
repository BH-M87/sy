<?php

namespace app\models;

class PsParkMessage extends BaseModel
{
    public static $status_desc = ["1"=>'停车消息','2'=>'积分消息'];
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_park_reservation';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id','community_name','user_id','type','content'], 'required','on'=>'add'],
            [['content'], 'string', 'max' => 500],
            [['create_at','update_at'], 'default', 'value' => time(),'on'=>['add']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'              => 'ID',
            'community_id'    => '小区',
            'community_name'  => '小区名称',
            'user_id'         => '用户id',
            'type'       => '消息类型',
            'content'         => '消息内容',
            'create_at'       => '创建时间',
            'update_at'       => '修改时间',
        ];
    }

    /***
     * 新增
     * @return bool
     */
    public function saveData()
    {
        return $this->save();
    }
    /**
     * 获取列表
     * @author yjh
     * @param $params
     * @param $field
     * @param $page true 分页显示
     * @return array
     */
    public static function getList($params,$field = "*",$page = true)
    {
        $activity = self::find()->select($field)
            ->where(['is_del' => 1])
            ->andFilterWhere(['user_id' => $params['user_id']]);
        $count = $activity->count();
        if ($count > 0) {
            $activity->orderBy('create_at desc');
            if ($page) {
                $activity->offset((($params['page'] ?? 1) - 1) * ($params['rows'] ?? 10))->limit($params['rows'] ?? 10);
            }
            $data = $activity->asArray()->all();
            self::afterList($data);
        }
        return ['totals'=>$count,'list'=>$data ?? []];
    }
    /**
     * 列表结果格式化
     * @author yjh
     * @param $data
     */
    public static function afterList(&$data)
    {
        foreach ($data as &$v) {
            $v['create_at'] = date('Y-m-d H:i:s',$v['create_at']);
            $v['type_desc'] = PsParkMessage::$status_desc[$v['type']];
        }
    }
}
