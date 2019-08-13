<?php

namespace app\models;

/**
 * This is the model class for table "ps_area_ali".
 *
 * @property string $areaCode
 * @property string $areaName
 * @property string $areaParentId
 * @property integer $areaType
 */
class PsAreaAli extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_area_ali';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['areaCode', 'areaName'], 'required'],
            [['areaType'], 'integer'],
            [['areaCode', 'areaName', 'areaParentId'], 'string', 'max' => 64],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'areaCode' => 'Area Code',
            'areaName' => 'Area Name',
            'areaParentId' => 'Area Parent ID',
            'areaType' => 'Area Type',
        ];
    }



}
