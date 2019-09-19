<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_complaint_images".
 *
 * @property integer $id
 * @property integer $complaint_id
 * @property string $img
 */
class PsComplaintImages extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_complaint_images';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['complaint_id', 'img'], 'required'],
            [['complaint_id'], 'integer'],
            [['img'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'complaint_id' => 'Complaint ID',
            'img' => 'Img',
        ];
    }
}
