<?php
namespace service\park;

use app\models\PsParkShared;
use app\models\PsParkSpace;
use service\BaseService;
use Yii;
use yii\db\Exception;

class SharedService extends BaseService{

    public static $WORK_DAY = [
        1 => ['en' => 'Monday', 'cn' => '周一'],
        2 => ['en' => 'Tuesday', 'cn' => '周二'],
        3 => ['en' => 'Wednesday', 'cn' => '周三'],
        4 => ['en' => 'Thursday', 'cn' => '周四'],
        5 => ['en' => 'Friday', 'cn' => '周五'],
        6 => ['en' => 'Saturday', 'cn' => '周六'],
        7 => ['en' => 'Sunday', 'cn' => '周日'],
    ];

    /*
     * 发布共享
     * 1.判断是否车位业主
     */
    public function addOfC($params){
        $trans = Yii::$app->db->beginTransaction();
        try{
            $model = new PsParkShared(['scenario'=>'add']);
            $params['start_date'] = !empty($params['start_date'])?strtotime($params['start_date']):0;
            $params['end_date'] = !empty($params['end_date'])?strtotime($params['end_date']." 23:59:59"):0;
            if($model->load($params,'')&&$model->validate()){
                if(!$model->save()){
                    return $this->failed('新增失败！');
                }
                //生成共享车位
                self::batchAddSpace($model->attributes);
                $trans->commit();
                return $this->success(['id'=>$model->attributes['id']]);
            }else{
                $msg = array_values($model->errors)[0][0];
                return $this->failed($msg);
            }
        }catch (Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    /*
     * 发布者删除共享车位
     */
    public function DelSpace($params){
        $model = new PsParkSpace(['scenario'=>'del']);
        $params['is_del'] = 2; //删除共享车位
        if($model->load($params,'')&&$model->validate()){
            if(!$model->edit($params)){
                return $this->failed('删除失败！');
            }
            //判断是否关闭发布共享
            return $this->success();
        }else{
            $msg = array_values($model->errors)[0][0];
            return $this->failed($msg);
        }
    }


    private function batchAddSpace($params){
        $dateParams['start_at'] = date('Y-m-d',$params['start_date']);
        $dateParams['end_at'] = date('Y-m-d',$params['end_date']);
        $dateParams['exec_type_msg'] = $params['exec_type_msg'];
        $dateAll = self::getExecDate($dateParams);
        $fields = [
                    'community_id','community_name','room_id','room_name','publish_id','publish_name','publish_mobile','shared_id',
                    'park_space','ali_form_id','ali_user_id','create_at','update_at','shared_at','start_at','end_at'
        ];
        $data = [];
        if(!empty($dateAll)){
            $element['community_id'] = $params['community_id'];
            $element['community_name'] = $params['community_name'];
            $element['room_id'] = $params['room_id'];
            $element['room_name'] = $params['room_name'];
            $element['publish_id'] = $params['publish_id'];
            $element['publish_name'] = $params['publish_name'];
            $element['publish_mobile'] = $params['publish_mobile'];
            $element['shared_id'] = $params['id'];
            $element['park_space'] = $params['park_space'];

            $element['ali_form_id'] = $params['ali_form_id'];
            $element['ali_user_id'] = $params['ali_user_id'];
            $element['create_at'] = $params['create_at'];
            $element['update_at'] = $params['update_at'];
            foreach($dateAll as $value){
                $element['shared_at'] = strtotime($value);
                $element['start_at'] = strtotime($value." ".$params['start_at']);
                $element['end_at'] = strtotime($value." ".$params['end_at']);
                $data[] = $element;
            }
        }
        if(!empty($data)){
            Yii::$app->db->createCommand()->batchInsert(PsParkSpace::tableName(),$fields,$data)->execute();
        }
    }


    /*
     * 获得执行日期
     */
    public function getExecDate($params){
        $dateAll = [];

        $exec_type_msg = explode(",",$params['exec_type_msg']);
        foreach($exec_type_msg as $value){
            $dateList = self::getWeeklyBuyDate($params['start_at'],$params['end_at'],$value,1);
            if(empty($dateAll)){
                $dateAll = $dateList;
            }else{
                $dateAll = array_merge($dateAll,$dateList);
            }
        }
        asort($dateAll);
        return $dateAll;
    }

    /**
     * desc 获取每x周X执行的所有日期
     * @param string $start 开始日期, 2016-10-17
     * @param string $end 结束日期, 2016-10-17
     * @param int $weekDay 1~5
     * @param int $interval
     * @return array
     */
    public function getWeeklyBuyDate($start, $end, $weekDay,$interval)
    {
        //获取每周要执行的日期 例如: 2016-01-02
        $start = empty($start) ? date('Y-m-d') : $start;
        $startTime = strtotime($start);
        $startDay = date('N', $startTime);
        if ($startDay <= $weekDay) {
            $startTime = strtotime(self::$WORK_DAY[$weekDay]['en'], strtotime($start)); //本周x开始, 例如, 今天(周二)用户设置每周四执行, 那本周四就会开始执行
        } else {
            $startTime = strtotime('next '.self::$WORK_DAY[$weekDay]['en'], strtotime($start));//下一个周x开始, 今天(周二)用户设置每周一执行, 那应该是下周一开始执行
        }

        $endTime = strtotime($end);
        $list = [];
        for ($i=0;;) {

            $dayOfWeek = strtotime("+{$i} week", $startTime); //每周x
            if ($dayOfWeek > $endTime) {
                break;
            }
            $list[] = date('Y-m-d', $dayOfWeek);
            $i = $i+$interval;
        }
        return $list;
    }

}