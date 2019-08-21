<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_labels".
 *
 * @property string $id
 * @property string $name 标签名称
 * @property int $label_type 1:房屋标签 2:住户标签
 * @property int $community_id 小区id
 * @property int $created_at
 * @property int $updated_at
 */
class PsLabels extends BaseModel
{
    public static $type = [1=>'房屋标签',2=>'住户标签'];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ps_labels';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'label_type', 'community_id', 'created_at', 'updated_at'], 'required'],
            [['label_type', 'community_id', 'created_at', 'updated_at'], 'integer'],
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
            'label_type' => 'Label Type',
            'community_id' => 'Community ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    //数据处理
    public static function handleData($data)
    {
        foreach($data as $k => $v) {
            foreach($v as $kk => $vv) {
                if($kk == 'label_type') {
                    switch ($vv) {
                        case 1:
                            $type = self::$type[1];
                            break;
                        case 2:
                            $type = self::$type[2];
                            break;
                        default:
                            $type = '未知';
                    }
                    $data[$k]['label_type_name'] = $type;

                }
            }
        }
        return $data;
    }

    /***
     * @param $param
     * @return array
     */
    public function getLabelsList($param){
        $fields = [
            '*'
        ];
        $contion = "1=1";
        //标签名称
        if(!empty($param['name'])){
            $contion.=" and ps_labels.name like '%".$param['name']."%'";
        }
        //1:房屋标签 2:住户标签
        if(!empty($param['label_type'])){
            $contion.= " and ps_labels.label_type =".$param['label_type'];
        }
        //小区id
        if(!empty($param['community_id'])){
            $contion.=" and ps_labels.community_id = ".$param['community_id'];
        }
        $result = self::find()
            ->select($fields)
            ->where($contion)
            ->asArray()
            ->one();
        return $result;
    }
}
