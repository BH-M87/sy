<?php
namespace app\models;
use Yii;

/**
 * This is the model class for table "ps_community_groups".
 *
 * @property integer $id
 * @property integer $community_id
 * @property string $name
 */
class PsCommunityGroups extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_community_groups';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id'], 'integer'],
            [['name'], 'string', 'max' => 50],
            [['community_id', 'name'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '',
            'name' => 'Name',
        ];
    }

    //获得小区幢列表
    public function getBuilding(){
        return $this->hasMany(PsCommunityBuilding::className(),['group_id'=>'id']);
    }

}