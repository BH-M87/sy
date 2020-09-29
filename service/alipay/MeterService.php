<?php
namespace service\alipay;
use common\core\F;
use common\core\PsCommon;
use app\models\PsWaterMeterFrom;
use service\BaseService;
use service\common\CsvService;
use service\common\ExcelService;
use service\rbac\OperateService;
use Yii;

class MeterService extends  BaseService
{
    /**
     * 删除仪表数据
     * @author yjh
     * @return array
     */
    public function delete($data)
    {
        if (empty($data['id']) || empty($data['type'])) {
            return $this->failed('参数错误');
        }
        switch ($data['type']) {
            //水表
            case 1:
                PsWaterMeter::findOne($data['id'])->delete();
                break;
            //电表
            case 2:
                PsElectricMeter::findOne($data['id'])->delete();
                break;
//            //公共表
//            case 3:
//                PsShared::findOne($data['id'])->delete();
//                break;
            default:
                return $this->failed('类型错误');
        }
        return $this->success();
    }

    /**
     * 导出仪表数据
     * @author yjh
     * @return array
     */
    public function export($data,$userinfo='')
    {
        if (empty($data['type'])) {
            return $this->failed('参数错误');
        }
        switch ($data['type']) {
            //水表
            case 1:
                $result = WaterMeterService::service()->export($data);
                //保存日志
                $log = [
                    "community_id" => $data['community_id'],
                    "operate_menu" => "仪表信息",
                    "operate_type" => "导出水表",
                    "operate_content" => ''
                ];
                OperateService::addComm($userinfo, $log);
                break;
            //电表
            case 2:
                $result = ElectrictMeterService::service()->export($data);
                //保存日志
                $log = [
                    "community_id" => $data['community_id'],
                    "operate_menu" => "仪表信息",
                    "operate_type" => "导出电表",
                    "operate_content" => ''
                ];
                OperateService::addComm($userinfo, $log);
                break;
            //公共表
            case 3:
                $result = SharedService::service()->export($data);
                //保存日志
                $log = [
                    "community_id" => $data['community_id'],
                    "operate_menu" => "仪表信息",
                    "operate_type" => "导出仪表",
                    "operate_content" => ''
                ];
                OperateService::addComm($userinfo, $log);
                break;
            default:
                return $this->failed('类型错误');
        }
        return $this->success(['downUrl'=>$result]);
    }
}