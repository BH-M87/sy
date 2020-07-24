<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2020/7/17
 * Time: 10:12
 */
namespace service\shop;

use app\models\PsShopStatistic;
use service\BaseService;
use Yii;
use yii\db\Exception;

Class StatisticService extends BaseService {

    //推广统计
    public function promoteStatistic($params){
        if(empty($params['start_time'])){
            return $this->failed("开始时间必填");
        }

        if(empty($params['end_time'])){
            return $this->failed("结束时间必填");
        }

        if(empty($params['type'])){
            return $this->failed("类型必填");
        }

        if(empty($params['data_code'])){
            return $this->failed("code必填");
        }


        //查询有多少天
        $dateAll = self::getDate($params);
        $dataInfo = [];
        if(!empty($dateAll)){
            $fields = ['sum(click_num) as count','day'];
            $model = PsShopStatistic::find()->select($fields)->where(1);
            if(!empty($params['start_time'])){
                $model->andWhere(['>=','day',$params['start_time']]);
            }
            if(!empty($params['end_time'])){
                $model->andWhere(['<=','day',$params['end_time']]);
            }
            if(!empty($params['type'])){
                $model->andWhere(['=','type',$params['type']]);
            }
            if(!empty($params['data_code'])){
                $model->andWhere(['=','data_code',$params['data_code']]);
            }
            $result = $model->groupBy(['day'])->orderBy(['day'=>SORT_ASC])->asArray()->all();
            foreach($dateAll as $value){
                if(!empty($result)){
                    $count = 0;
                    foreach($result as $key=>$v){
                        if($value == $v['day']){
                            $count = $v['count'];
                            continue;
                        }
                    }
                    $dataInfo[] = $count;
                }else{
                    $dataInfo[] = 0;
                }
            }
        }

        return $this->success(['date'=>$dateAll,'data'=>$dataInfo]);
    }

    //查询时间段之间有多少日期
    public function getDate($params){
        $dt_start = strtotime($params['start_time']);
        $dt_end = strtotime($params['end_time']);
        $result = [];
        while ($dt_start<=$dt_end){
            $result[] =  date('Y-m-d',$dt_start);
            $dt_start = strtotime('+1 day',$dt_start);
        }
        return $result;
    }
}