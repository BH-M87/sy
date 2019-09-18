<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_service".
 *
 * @property integer $id
 * @property string $name
 * @property integer $parent_id
 * @property string $intro
 * @property string $service_no
 * @property integer $order_sort
 * @property string $link_url
 * @property string $img_url
 * @property integer $status
 * @property integer $create_at
 */
class PsServiceModel extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_service';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'order_sort', 'status', 'create_at'], 'required'],
            [['parent_id', 'order_sort', 'status', 'create_at'], 'integer'],
            [['name'], 'string', 'max' => 45],
            [['intro'], 'string', 'max' => 100],
            [['service_no'], 'string', 'max' => 4],
            [['link_url', 'img_url'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'parent_id' => 'Parent ID',
            'intro' => 'Intro',
            'service_no' => 'Service No',
            'order_sort' => 'Order Sort',
            'link_url' => 'Link Url',
            'img_url' => 'Img Url',
            'status' => 'Status',
            'create_at' => 'Create At',
        ];
    }
}
