<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_repair_type".
 *
 * @property integer $id
 * @property integer $community_id
 * @property string $name
 * @property integer $level
 * @property integer $parent_id
 * @property integer $is_relate_room
 * @property integer $status
 * @property integer $created_at
 */
class PsRepairBill extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_repair_bill';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [];
    }
}
