<?php



namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_channel_day_report".
 *
 * @property integer $id
 * @property integer $community_id
 * @property string $community_name
 * @property integer $cost_id
 * @property integer $parent_id
 * @property integer $parent_time
 * @property integer $type
 * @property string $type_name
 * @property string $amount
 * @property integer $total
 * @property integer $create_at
 * @property integer $update_at
 */
class PsChannelDayReport extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_channel_day_report';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'cost_id', 'type'], 'required'],
            [['community_id', 'cost_id', 'parent_id', 'parent_time', 'type', 'total', 'create_at', 'update_at'], 'integer'],
            [['amount'], 'number'],
            [['community_name', 'type_name'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => 'Community ID',
            'community_name' => 'Community Name',
            'cost_id' => 'Cost ID',
            'parent_id' => 'Parent ID',
            'parent_time' => 'Parent Time',
            'type' => 'Type',
            'type_name' => 'Type Name',
            'amount' => 'Amount',
            'total' => 'Total',
            'create_at' => 'Create At',
            'update_at' => 'Update At',
        ];
    }

    //获取渠道列表
    public static function getChannelList($data,$date)
    {

        if (!empty($data)) {
            if (!empty($data['cost_id'])) {
                $where['cost_id'] = $data['cost_id'];
            }
            $where['community_id'] = $data['community_id'];
        } else {
            $where = '1=1';
        }
        if (!empty($date)) {
            $andwhere = ['and' , 'parent_time >= '.strtotime($date['start_time']),'parent_time <= '.strtotime($date['end_time'])];
        } else {
            $andwhere = '1=1';
        }
        return self::find()->select('community_id,community_name,cost_id,type,type_name,sum(amount) as c_amount')->where($where)->andWhere($andwhere)
            ->groupBy(['type','cost_id'])->asArray()->all();
    }

    //删除小区+项目类型维度数据
    public static  function deleteCost($v)
    {
        $where = [
            'community_id' => $v['community_id'],
            'cost_id' => $v['cost_id'],
        ];
        return self::deleteAll($where);
    }
}
