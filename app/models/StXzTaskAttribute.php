<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "st_xz_task_attribute".
 *
 * @property int $id
 * @property string $name 类别名称
 */
class StXzTaskAttribute extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'st_xz_task_attribute';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
        ];
    }
}
