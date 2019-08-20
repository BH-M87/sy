<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_sensitive_word".
 *
 * @property string $id
 * @property string $name
 * @property integer $type
 * @property string $intercept_num
 * @property integer $create_time
 * @property string $update_time
 */
class PsSensitiveWord extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_sensitive_word';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type', 'intercept_num', 'create_time', 'update_time'], 'integer'],
            [['name'], 'string', 'max' => 255],
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
            'type' => 'Type',
            'intercept_num' => 'Intercept Num',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
