<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_life_broadcast".
 *
 * @property integer $id
 * @property integer $type
 * @property integer $material_id
 * @property string $content
 * @property string $image
 * @property integer $created_at
 */
class PsLifeBroadcast extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_life_broadcast';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type','push_type'], 'required'],
            //生活号
            [['content'], 'required', 'on'=>'content'],
            [['image'], 'required', 'on'=>'image'],
            [['material_id'], 'required', 'on'=>'material'],

            //小程序
            [['title','content'], 'required', 'on'=>'content_small'],
            [['title','image'], 'required', 'on'=>'image_small'],
            [['title','image','content'], 'required', 'on'=>'imaText_small'],

            [['type', 'material_id', 'created_at'], 'integer'],
            [['type'], 'in', 'range' => [1, 2, 3], 'message' => '{attribute}取值范围出错'],
            [['push_type'], 'in', 'range' => [1, 2], 'message' => '{attribute}取值范围出错'],
            [['title'], 'string', 'max' => 30],
            //[['content'], 'string', 'max' => 600],
            [['image'], 'string', 'max' => 255],
            [['content'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => '消息类型',
            'push_type' => '推送范围',
            'material_id' => '图文素材',
            'content' => '内容',
            'image' => '图片',
            'title' => '标题',
            'created_at' => 'Created At',
        ];
    }
}
