<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_meter_cycle".
 *
 * @property integer $id
 * @property integer $period
 * @property integer $meter_time
 * @property integer $type
 * @property integer $status
 * @property integer $created_at
 */
class PsMeterCycle extends BaseModel
{
    public static $_status_msg = ['1'=>'未发布账单','2'=>'已发布账单'];
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_meter_cycle';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['period',"meter_time",'community_id','type',],'required','on'=>['add']],
            [['id',],'required','on'=>['delete']],
//            [['type','community_id'],'required','on'=>['list']],
            [['type'],'required','on'=>['list']],
            [['type', 'status', 'created_at'], 'integer'],
            [['community_id'], 'string', 'max' => 30],
            ['type','in','range'=>[1,2]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'period' => '抄表周期',
            'community_id' => '小区id',
            'meter_time' => '本次抄表时间',
            'type' => '1:水表 2:电表',
            'status' => '1:发布账单 2:未发布账单',
            'created_at' => 'Created At',
        ];
    }

    /**
     * 获取周期列表
     * @author Yjh
     * @param $param
     * @return mixed
     */
    public static function getList($param,$page = true,$communityList)
    {
        $query = self::find()->where($param['where'])->orderBy('created_at desc');
        if ($page) {
            $page = !empty($param['page']) ? $param['page'] : 1;
            $row = !empty($param['row']) ? $param['row'] : 10;
            $page = ($page-1)*$row;
            $countQuery = clone $query;
            $count = $countQuery->count();
            $query->offset($page)->limit($row);
        }
        if(!empty($communityList)){
            $query->andWhere(['in','community_id',$communityList]);
        }
        $models = $query->asArray()->all();
        if ($models) {
            $result = self::afterSelect($models);
        }
        return ["totals" => $count ?? null,"list" => $result ?? null];

    }

    /**
     * 数据格式化
     * @author Yjh
     * @param $model
     * @return mixed
     */
    public static function afterSelect($model)
    {
        if (count($model) == count($model, 1)) {
            $model['period'] = date('Y-m', $model['period']);
            $model['meter_time'] = date('Y-m-d', $model['meter_time']);
            $model['status_msg'] = self::$_status_msg[$model['status']];
        } else {
            foreach ($model as $k => &$v) {
                $v['period'] = date('Y-m', $v['period']);
                $v['meter_time'] = date('Y-m-d', $v['meter_time']);
                $v['status_msg'] = self::$_status_msg[$v['status']];
            }
        }
        return $model;
    }
    
}
