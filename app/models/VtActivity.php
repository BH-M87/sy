<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/7/23
 * Time: 11:19
 * Desc: 活动
 */
namespace app\models;

class VtActivity extends BaseModel
{


    public static function tableName()
    {
        return 'vt_activity';
    }

    public function rules()
    {
        return [

            [['name', 'code', 'start_at', 'end_at', 'group_status'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['add']],
            [['id','name', 'start_at', 'end_at', 'group_status'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['edit']],
            [['id'], 'required', 'message' => '{attribute}不能为空！', 'on' => ['detail']],
            [["id",  'start_at', 'end_at', 'group_status', 'create_at', 'update_at'], 'integer'],
            [['name', 'code', 'start_at', 'end_at', 'group_status','content'], 'trim'],
            [['content'], 'string'],
            [['code'], 'string', "max" => 20],
            [['name'], 'string', "max" => 50],
            [['link_url', 'qrcode'], 'string', "max" => 255],
            ['group_status', 'in', 'range' => [1, 2], 'on' => ['add','edit']],
            [['code'], 'codeInfo', 'on' => ["add"]], //活动code唯一
            [['id'], 'dataInfo', 'on' => ["edit","detail"]], //活动是否存在
            [['start_at', 'end_at'], 'timeVerification', 'on' => ["add"]], //活动时间验证
            [["id",'start_at', 'end_at','group_status'], 'editVerification', 'on' => ["edit"]], //判断活动是否开始 开始不能编辑时间和分组
            [["create_at", 'update_at'], "default", 'value' => time(), 'on' => ['add']],
        ];
    }

    public function attributeLabels()
    {
        return [
              'id'              => '活动id',
              'code'            => '活动code',
              'name'            => '活动名称',
              'start_at'        => '开始时间',
              'end_at'          => '结束时间',
              'content'         => '活动规则',
              'group_status'    => '选手分组',
              'link_url'        => '页面链接',
              'qrcode'          => '二维码',
              'create_at'       => '创建时间',
              'update_at'       => '修改时间',
        ];
    }

    /***
     * 新增
     * @return true|false
     */
    public function saveData()
    {
        return $this->save();
    }

    /***
     * 修改
     * @return bool
     */
    public function edit($param)
    {
        $param['update_at'] = time();
        return self::updateAll($param, ['id' => $param['id']]);
    }

    /*
     * code唯一
     */
    public function codeInfo($attribute){
        if(!empty($this->code)){
            $res = self::find()->select(['id'])->where(['=','code',$this->code])->asArray()->one();
            if(!empty($res)){
                return $this->addError($attribute, "投票活动code已存在，请重新输入");
            }
        }
    }

    /*
     * 时间验证
     */
    public function timeVerification($attribute){
        if(!empty($this->start_at)&&!empty($this->end_at)){
            $nowTime = time();
            if($this->start_at<$nowTime){
                return $this->addError($attribute, "投票活动开始时间应大于当前时间");
            }

            if($this->start_at>=$this->end_at){
                return $this->addError($attribute, "投票活动开始时间应小于投票结束时间");
            }
        }
    }

    /*
     * 验证数据是否存在
     */
    public function dataInfo($attribute){
        if(!empty($this->id)){
            $res = self::find()->select(['id'])->where(['=','id',$this->id])->asArray()->one();
            if(empty($res)){
                return $this->addError($attribute, "该投票活动不存在");
            }
        }
    }

    //活动是一开始 验证
    public function editVerification($attribute){
        if(!empty($this->id)){
            $nowTime = time();
            $res = self::find()->select(['id','start_at','end_at','group_status'])->where(['=','id',$this->id])->asArray()->one();
            if($res['start_at']<$nowTime){  //活动已开始
                if($res['start_at']!=$this->start_at){
                    return $this->addError($attribute, "投票活动开始时间不能修改");
                }
                if($res['end_at']!=$this->end_at){
                    return $this->addError($attribute, "投票活动结束时间不能修改");
                }
//                if($res['group_status']!=$this->group_status){
//                    return $this->addError($attribute, "投票活动选手分组不能修改");
//                }
            }else{
                if($this->start_at<$nowTime){
                    return $this->addError($attribute, "投票活动开始时间应大于当前时间");
                }

                if($this->start_at>=$this->end_at){
                    return $this->addError($attribute, "投票活动开始时间应小于投票结束时间");
                }
            }
        }
    }

    /*
     * 活动列表
     */
    public function getList($params){
        $fields = ['id','code','name','create_at','start_at','end_at'];
        $model = self::find()->select($fields)->where(1);

        if(!empty($params['code'])){
            $model->andWhere(['like','code',$params['code']]);
        }
        if(!empty($params['name'])){
            $model->andWhere(['like','name',$params['name']]);
        }

        $count = $model->count();
        if(!empty($params['page'])||!empty($params['pageSize'])){
            $page = !empty($params['page'])?intval($params['page']):1;
            $pageSize = !empty($params['pageSize'])?intval($params['pageSize']):10;
            $offset = ($page-1)*$pageSize;
            $model->offset($offset)->limit($pageSize);
        }
        $model->orderBy(["id"=>SORT_DESC]);
        $result = $model->asArray()->all();
        return [
            'list'=>$result,
            'totals'=>$count
        ];
    }

    //关联分组
    public function getGroup(){
        return $this->hasMany(VtActivityGroup::className(),['activity_id'=>'id']);
    }

    //关联banner
    public function getBanner(){
        return $this->hasMany(VtActivityBanner::className(),['activity_id'=>'id']);
    }

    //活动详情
    public function getDetail($params){
        $fields = ['id','code','name','content','start_at','end_at','group_status','link_url','qrcode'];
        $model = self::find()->select($fields)->with('group')->with('banner')->where(['=','id',$params['id']]);

        return $model->asArray()->one();
    }

    //活动下拉
    public function getDropList($params){
        $fields = ['id','name'];
        $model = self::find()->select($fields)->where(1);
        if(!empty($params['status'])&&$params['status']==1){    // 未开始活动
            $model->andWhere(['>','start_at',time()]);
        }
        $model->orderBy(['id'=>SORT_DESC]);
        return $model->asArray()->all();
    }
}