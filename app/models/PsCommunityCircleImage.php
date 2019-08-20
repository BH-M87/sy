<?php

namespace app\models;

use Yii;

class PsCommunityCircleImage extends BaseModel
{
    public static function tableName()
    {
        return 'ps_community_circle_image';
    }

    public function rules()
    {
        return [
            [['community_circle_id', 'image_url'], 'required'],
            [['community_circle_id'], 'integer'],
            [['image_url'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_circle_id' => 'Community Circle ID',
            'image_url' => 'Image Url',
        ];
    }
}
