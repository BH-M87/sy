<?php
namespace app\models;

use Yii;

class PsLabels extends BaseModel
{   
    public static $attribute = [1 => '房屋标签', 2 => '住户标签', 3 => '车辆标签'];
    public static $type = [1 => '日常画像', 2 => '重点关注', 3 => '关怀对象'];
    
    public static function tableName()
    {
        return 'ps_labels';
    }

    public function rules()
    {
        return [
            [['name', 'label_type', 'community_id', 'label_attribute'], 'required', 'on' => ['add', 'edit']],
            [['id'], 'required', 'on' => ['edit']],
            [['community_id'], 'required', 'on' => ['typelist']],
            [['label_type', 'community_id', 'created_at', 'updated_at', 'id'], 'integer'],
            [['name'], 'string', 'max' => 15],
            [['content'], 'string', 'max' => 100],
            ['label_type', 'in', 'range' => [1,2,3]],
            ['label_attribute', 'in', 'range' => [1,2]],
            [['updated_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '标签名称',
            'label_type' => '标签分类',
            'label_attribute' => '标签属性',
            'community_id' => '小区ID',
            'content' => '标签描述',
            'created_at' => '新曾时间',
            'updated_at' => '修改时间',
        ];
    }

    // 数据处理
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
                        case 3:
                            $type = self::$type[3];
                            break;
                        default:
                            $type = '未知';
                    }
                    $data[$k]['label_type_name'] = $type;
                }

                if($kk == 'label_attribute') {
                    switch ($vv) {
                        case 1:
                            $attribute = self::$attribute[1];
                            break;
                        case 2:
                            $attribute = self::$attribute[2];
                            break;
                        default:
                            $attribute = '未知';
                    }
                    $data[$k]['label_attribute_name'] = $attribute;
                }

                $data[$k]['content'] = $v['content'] ?? '';
            }
        }
        return $data;
    }

    public function getLabelsList($param)
    {
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
