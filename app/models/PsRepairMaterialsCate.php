<?php
namespace app\models;
use Yii;

/**
 * This is the model class for table "ps_repair_materials_cate".
 *
 * @property integer $id
 * @property string $name
 */
class PsRepairMaterialsCate extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_repair_materials_cate';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'string', 'max' => 20],
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
        ];
    }
}
