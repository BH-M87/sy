<?php

namespace app\models;

use app\common\core\PsCommon;
use Yii;
use yii\db\Query;

/**
 * This is the model class for table "ps_complaint".
 *
 * @property integer $id
 * @property integer $member_id
 * @property integer $community_id
 * @property integer $type
 * @property string $content
 * @property integer $status
 * @property string $handle_content
 * @property integer $create_at
 * @property string $day
 * @property integer $handle_at
 */
class PsComplaint extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_complaint';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['member_id', 'community_id', 'type', 'content', 'status', 'create_at'], 'required'],
            [['member_id', 'community_id', 'type', 'status', 'create_at', 'handle_at'], 'integer'],
            [['content'], 'string', 'max' => 200],
            [['day','room_id'], 'safe'],
            [['handle_content'], 'string', 'max' => 200],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'member_id' => '用户',
            'room_id' => '房屋ID',
            'community_id' => '小区ID',
            'type' => '投诉类型',
            'content' => '投诉内容',
            'status' => '投诉状态',
            'handle_content' => '处理内容',
            'create_at' => '提交时间',
            'handle_at' => '处理时间',
        ];
    }

    // 根据条件获取投诉量
    public static function getComplaintNum($param, $communityIds)
    {
        $query = new Query();
        $query->from("ps_complaint A")
            ->leftJoin("st_social_community C", "A.community_id = C.community_id")
            ->where(['A.community_id' => $communityIds]);

        if (!empty($param["start_time"])) {
            $query->andWhere([">=", "A.create_at", strtotime($param["start_time"])]);
        }

        if (!empty($param["end_time"])) {
            $query->andWhere(["<=", "A.create_at", strtotime($param["end_time"])]);
        }

        if (!empty($param["street_id"])) {
            $query->andWhere(["=", "C.street_id", $param["street_id"]]);
        }

        if (!empty($param['type'])) { // 有值 投诉率 
            $count = Yii::$app->db->createCommand("SELECT count(id) from ps_room_user where status = 2")->queryScalar();
            $query->select(["A.community_id", "count(A.id) / $count as complaint"])
                ->groupBy("A.community_id");
        } else {
            $query->select(["A.community_id", "count(A.id) as complaint"])
                ->groupBy("A.community_id");
        }

        $model = $query->createCommand()->queryAll();

        return $model;
    }

    public function getImages()
    {
        return $this->hasMany(PsComplaintImages::className(), ['complaint_id' => 'id']);
    }

}
