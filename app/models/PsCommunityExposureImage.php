<?php

namespace app\models;

use Yii;

class PsCommunityExposureImage extends BaseModel
{
    public static function tableName()
    {
        return 'ps_community_exposure_image';
    }

    public function rules()
    {
        return [
            [['community_exposure_id', 'image_url', 'type'], 'required'],
            [['community_exposure_id', 'type'], 'integer'],
            [['image_url'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_exposure_id' => 'Community Exposure ID',
            'image_url' => 'Image Url',
            'type' => 'Type',
        ];
    }
}
