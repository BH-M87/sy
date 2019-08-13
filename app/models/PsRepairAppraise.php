<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_repair_appraise".
 *
 * @property integer $id
 * @property integer $repair_id
 * @property integer $start_num
 * @property string $appraise_labels
 * @property string $content
 * @property integer $created_at
 */
class PsRepairAppraise extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_repair_appraise';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['repair_id', 'start_num', 'created_at'], 'integer'],
            [['start_num'], 'required'],
            [['appraise_labels', 'content'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'repair_id' => 'Repair ID',
            'start_num' => 'Start Num',
            'appraise_labels' => 'Appraise Labels',
            'content' => 'Content',
            'created_at' => 'Created At',
        ];
    }
}
