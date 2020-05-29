<?php
namespace service\alipay;

use app\models\PsBill;
use app\models\PsSystemSet;
use service\BaseService;
use service\property_basic\JavaService;
use Yii;
use common\core\PsCommon;
use app\models\PsTemplateBill;
use app\models\PsTemplateConfig;
use app\models\PsPropertyCompany;
use app\models\PsRoomUser;
use app\models\PsCommunityModel;
use service\manage\CommunityService;
use service\basic_data\RoomService;

Class TemplateService extends BaseService
{
    // +------------------------------------------------------------------------------------
    // |----------------------------------     模板打印预览     ----------------------------
    // +------------------------------------------------------------------------------------

    // 打印票据数据
    public function printBillInfo($params, $userinfo, $income = '')
    {
        $communityId = PsCommon::get($params, "community_id"); // 小区id
        $roomId = PsCommon::get($params, "room_id"); // 房屋id
        $bill_list = PsCommon::get($params, "bill_list"); // 账单列表

        $communityInfo = CommunityService::service()->getInfoById($communityId);
        if (empty($communityInfo)) {
            return $this->failed("请选择有效小区");
        }

        if (!$roomId) {
            return $this->failed("房屋id不能为空");
        }

        if (!is_array($bill_list)) {
            return $this->failed("选择的账单参数错误");
        }

        $roomInfo = [];

        $roomInfo = RoomService::service()->getRoomById($roomId);
        if (empty($roomInfo)) {
            return $this->failed("未找到房屋");
        }

        $where = " A.is_del = 1 AND A.community_id = " . $communityId; // 查询条件,默认查询未删除的数据
        if (!empty($bill_list)) {
            $IdAll = '';
            foreach ($bill_list as $cost) {
                $IdAll .= $cost . ",";
            }
            $custId = rtrim($IdAll, ",");
            $where .= " AND A.id in({$custId})";
        }
        // 查ps_bill表
        $models = Yii::$app->db->createCommand("SELECT A.id as bill_id, A.cost_type, A.cost_name, A.bill_entry_amount, A.paid_entry_amount, 
            A.prefer_entry_amount, A.acct_period_start, A.acct_period_end, A.community_name, A.group, A.building,
            A.unit, A.room, A.company_id, A.community_id, A.charge_area
            from ps_bill as A where {$where} order by A.id desc")->queryAll();
        if (!empty($models)) {
            $total_money = 0;
            foreach ($models as $v) {
                $arr = [];
                $arr['id'] = $v['bill_id'];
                $arr['house_info'] = $v['group'].$v['building'].$v['unit'].$v['room']; // 房屋信息
                $arr['house_area'] = $v['charge_area'];
                $arr['pay_item'] = $v['cost_name']; // 收费项名称
                $arr['start_at'] = date("Y-m-d", $v['acct_period_start']); // 开始时间
                $arr['end_at'] = date("Y-m-d", $v['acct_period_end']); // 结束时间
                $arr['bill_amount'] = $v['bill_entry_amount']; // 应收金额
                $arr['discount_amount'] = $v['prefer_entry_amount']; // 优惠金额
                $arr['pay_amount'] = $v['paid_entry_amount']; // 实收金额
                
                // 如果是水费和电费则还需要查询使用量跟起始度数1
                if ($v['cost_type'] == 2 || $v['cost_type'] == 3) {
                    $water = Yii::$app->db->createCommand("SELECT use_ton, latest_ton, formula 
                        from ps_water_record where bill_id = {$v['bill_id']} ")->queryOne();
                    if (!empty($water)) {
                        $arr['start'] = $water['latest_ton']; // 起度
                        $arr['end'] = $water['latest_ton'] + $water['use_ton']; // 止度
                        $arr['use'] = $water['use_ton']; // 使用量
                        $arr['formula'] = $water['formula']; // 收费标准
                    }
                }
                $total_money += $v['paid_entry_amount'];
                $arrList[] = $arr;
            }
            // 获取住户姓名 已迁入（迁入已认证+迁入未认证）
            $names = PsRoomUser::find()->select('name')->where(['room_id' => $roomId])
                ->andWhere(['in', 'status', [1,2]])->asArray()->all();
        
            if (!empty($names)) {
                $true_name = '';
                foreach ($names as $key => $val) {
                    $true_name .= $val['name'] . ',';
                }
            }

            $room_comm['print_date'] = date("Y-m-d H:i", time());
            $room_comm['community_id'] = $models[0]['community_id'];
            $room_comm['community_name'] = $communityInfo['name'];
            $room_comm['house_info'] = $roomInfo['address'];
            $room_comm['house_area'] = $models[0]['charge_area'];
            $room_comm['true_name'] = !empty($true_name) ? $true_name : ''; // 住户姓名
            $room_comm['total'] = sprintf("%.2f", $total_money);
            $room_comm['pay_date'] = !empty($income) ? date("Y-m-d", $income['income_time']) : ''; // 收款日期
            $room_comm['payee_name'] = !empty($income) ? $income['payee_name'] : ''; // 收款人
            $room_comm['company_id'] = $models[0]['company_id'];
            $room_comm['pay_company'] = PsPropertyCompany::findOne($models[0]['company_id'])->property_name; // 收款单位
            $room_comm['pay_channel'] = !empty($income) ? BillIncomeService::$pay_channel[$income['pay_channel']] : ''; // 收款方式
            $room_comm['pay_note'] = !empty($income) ? $income['note'] : ''; // 收款备注
        
            $data[$roomId]['bill_list'] = $arrList; // 账单信息
            $data[$roomId]['room_data'] = $room_comm; // 模板信息+房屋信息

            return $this->success($data);
        } else {
            return $this->failed('暂无需打印的账单');
        }
    }

    //打印催缴单
    public function billListNew_($params,$userinfo){
        $bill_list = PsCommon::get($params, "ids");
        $roomId = PsCommon::get($params, "room_id"); // 房屋id

        $fields = ['id as bill_id','room_address','cost_id','cost_type','cost_name','bill_entry_amount','paid_entry_amount',
            'prefer_entry_amount', 'acct_period_start','acct_period_end','community_name','company_id', 'community_id', 'charge_area'];
        $models = PsBill::find()->select($fields)->where(['=','is_del',1])->andWhere(['=','room_id',$roomId]);
        if(!empty($bill_list)){
            $models = $models->andWhere(['in','id',$bill_list]);
        }

        $results = $models->orderBy(['cost_id'=>SORT_ASC,'acct_period_start'=>SORT_ASC])->asArray()->all();
        if(!empty($results)){
            $resultList = $this->getForData($results);
        }else {
            return [];
        }
        $arrList = [];
        if(!empty($resultList)){
            $total_money = 0;
            foreach ($resultList as $k=>$v) {
                $arr = [];
                $arr['pay_item'] = $v['cost_name']; // 收费项名称
                $arr['start_at'] = '';
                $arr['end_at'] = '';
                if($k!=99){
                    $arr['start_at'] = date("Y-m-d", $v['acct_period_start']); // 开始时间
                    $arr['end_at'] = date("Y-m-d", $v['acct_period_end']); // 结束时间
                }
                $arr['bill_amount'] = $v['bill_entry_amount']; // 应收金额
//                $arr['discount_amount'] = $v['prefer_entry_amount']; // 优惠金额
//                $arr['pay_amount'] = $v['paid_entry_amount']; // 实收金额

    //                // 如果是水费和电费则还需要查询使用量跟起始度数1
    //                if ($v['cost_type'] == 2 || $v['cost_type'] == 3) {
    //                    $water = Yii::$app->db->createCommand("SELECT use_ton, latest_ton, formula
    //                        from ps_water_record where bill_id = {$v['bill_id']} ")->queryOne();
    //                    if (!empty($water)) {
    //                        $arr['start'] = $water['latest_ton']; // 起度
    //                        $arr['end'] = $water['latest_ton'] + $water['use_ton']; // 止度
    //                        $arr['use'] = $water['use_ton']; // 使用量
    //                        $arr['formula'] = $water['formula']; // 收费标准
    //                    }
    //                }
                $total_money += $v['bill_entry_amount'];
                $arrList[] = $arr;

            }
            $room_comm['print_date'] = date("Y年m月d日", time());
            $room_comm['house_info'] = $results[0]['room_address'];
            $room_comm['house_area'] = $results[0]['charge_area'];
            $room_comm['total'] = sprintf("%.2f", $total_money);
            $room_comm['pay_company'] = !empty($userinfo['corpName'])?$userinfo['corpName']:''; // 收款单位
            $room_comm['content'] = $params['content'];
            $redis = Yii::$app->redis;
            $qrCodeKey = $results[0]['community_id'];
            $qrCodeResult = json_decode($redis->get($qrCodeKey),true);
            if(empty($qrCodeResult)){
                //获得小区小程序二维码 放到redis中
                $javaService = new JavaService();
                $javaParams['data']['communityId'] = $results[0]['community_id'];
                $javaParams['data']['describe'] = $results[0]['community_name']."小程序二维码";
//                $javaParams['data']['communityId'] = '1254991620133425154';
//                $javaParams['data']['describe'] = $results[0]['community_name']."小程序二维码";
                $javaParams['data']['queryParam'] = "x=1";
                $javaParams['data']['urlParam'] = "pages/homePage/homePage/homePage";
                $javaParams['token'] = $params['token'];
                $javaParams['url'] = '/corpApplet/selectCommunityQrCode';
                $javaResult = $javaService->selectCommunityQrCode($javaParams);
                $qrCodeResult['qrCodeUrl'] = !empty($javaResult['qrCodeUrl'])?$javaResult['qrCodeUrl']."jpg":'';
                $redis->set($qrCodeKey,json_encode($qrCodeResult));
                //设置一个月有效期
                $redis->expire($qrCodeKey,86400*30);
            }
            $setQrCodeUrl = Yii::$app->modules['property']->params['qr_code_url'];
            $room_comm['qr_code'] = !empty($qrCodeResult['qrCodeUrl'])?$qrCodeResult['qrCodeUrl']:$setQrCodeUrl; // 二维码图片

            $data['bill_list'] = $arrList; // 账单信息
            $data['room_data'] = $room_comm; // 模板信息+房屋信息

            return $data;
        }
        return [];
    }

    // 打印票据数据
    public function printBillInfo_($params, $userinfo, $income = '')
    {
        $communityId = PsCommon::get($params, "community_id"); // 小区id
        $roomId = PsCommon::get($params, "room_id"); // 房屋id
        $bill_list = PsCommon::get($params, "bill_list"); // 账单列表

        if(!in_array($communityId,$params['communityList'])){
            return $this->failed("请选择有效小区");
        }

        if (!$roomId) {
            return $this->failed("房屋id不能为空");
        }

        if (!is_array($bill_list)) {
            return $this->failed("选择的账单参数错误");
        }

//        $where = " A.is_del = 1 AND A.community_id = " . $communityId; // 查询条件,默认查询未删除的数据
//        if (!empty($bill_list)) {
//            $IdAll = '';
//            foreach ($bill_list as $cost) {
//                $IdAll .= $cost . ",";
//            }
//            $custId = rtrim($IdAll, ",");
//            $where .= " AND A.id in({$custId})";
//        }
        // 查ps_bill表
//        $models = Yii::$app->db->createCommand("SELECT A.id as bill_id,A.room_address, A.cost_type, A.cost_name, A.bill_entry_amount, A.paid_entry_amount,
//            A.prefer_entry_amount, A.acct_period_start, A.acct_period_end, A.community_name, A.group_id, A.building_id,
//            A.unit_id, A.room_id, A.company_id, A.community_id, A.charge_area
//            from ps_bill as A where {$where} order by A.id desc")->queryAll();
        $fields = ['id as bill_id','room_address','cost_id','cost_type','cost_name','bill_entry_amount','paid_entry_amount',
            'prefer_entry_amount', 'acct_period_start','acct_period_end','community_name','company_id', 'community_id', 'charge_area'];
        $models = PsBill::find()->select($fields)->where(['=','is_del',1])->andWhere(['=','community_id',$communityId]);
        if(!empty($bill_list)){
            $models = $models->andWhere(['in','id',$bill_list]);
        }
        $results = $models->orderBy(['cost_id'=>SORT_ASC,'acct_period_start'=>SORT_ASC])->asArray()->all();
        if(!empty($results)){
            $resultList = $this->getForData($results);
        }else {
            return $this->failed('暂无需打印的账单');
        }
        if (!empty($resultList)) {
            $total_money = 0;
            foreach ($resultList as $k=>$v) {
                $arr = [];
//                $arr['id'] = $v['bill_id'];
//                $arr['house_info'] = $v['group'].$v['building'].$v['unit'].$v['room']; // 房屋信息
//                $arr['house_area'] = $v['charge_area'];
                $arr['pay_item'] = $v['cost_name']; // 收费项名称
                $arr['start_at'] = ''; // 开始时间
                $arr['end_at'] = ''; // 结束时间
                if($k!=99){
                    $arr['start_at'] = date("Y-m-d", $v['acct_period_start']); // 开始时间
                    $arr['end_at'] = date("Y-m-d", $v['acct_period_end']); // 结束时间
                }
                $arr['bill_amount'] = $v['bill_entry_amount']; // 应收金额
                $arr['discount_amount'] = $v['prefer_entry_amount']; // 优惠金额
                $arr['pay_amount'] = $v['paid_entry_amount']; // 实收金额

//                // 如果是水费和电费则还需要查询使用量跟起始度数1
//                if ($v['cost_type'] == 2 || $v['cost_type'] == 3) {
//                    $water = Yii::$app->db->createCommand("SELECT use_ton, latest_ton, formula
//                        from ps_water_record where bill_id = {$v['bill_id']} ")->queryOne();
//                    if (!empty($water)) {
//                        $arr['start'] = $water['latest_ton']; // 起度
//                        $arr['end'] = $water['latest_ton'] + $water['use_ton']; // 止度
//                        $arr['use'] = $water['use_ton']; // 使用量
//                        $arr['formula'] = $water['formula']; // 收费标准
//                    }
//                }
                $total_money += $v['paid_entry_amount'];
                $arrList[] = $arr;
            }
            $room_comm['print_date'] = date("Y-m-d H:i", time());
//            $room_comm['community_id'] = $results[0]['community_id'];
            $room_comm['house_info'] = $results[0]['room_address'];
            $room_comm['house_area'] = $results[0]['charge_area'];
            $room_comm['total'] = sprintf("%.2f", $total_money);
            $room_comm['pay_date'] = !empty($income) ? date("Y年m月d日", $income['income_time']) : ''; // 收款日期
            $room_comm['payee_name'] = !empty($income) ? $income['payee_name'] : ''; // 收款人
            $room_comm['trade_no'] = !empty($income) ? $income['trade_no'] : ''; // 编号
//            $room_comm['company_id'] = '';
            $room_comm['pay_company'] = !empty($userinfo['corpName'])?$userinfo['corpName']:''; // 收款单位; // 收款单位
            $room_comm['pay_channel'] = !empty($income) ? BillIncomeService::$pay_channel[$income['pay_channel']] : ''; // 收款方式
            $room_comm['pay_note'] = !empty($income) ? $income['note'] : ''; // 收款备注

            $data['bill_list'] = $arrList; // 账单信息
            $data['room_data'] = $room_comm; // 模板信息+房屋信息

            return $this->success($data);
        } else {
            return $this->failed('暂无需打印的账单');
        }
    }

    //循环账单方法
    public function getForData($results){
        $resultList = [];
        $valiTime = 0;
        $valiKey = 0;
        foreach ($results as $key=>$item) {
            if(count($resultList)>=5){
                $qiList = $item;
                $qiList['cost_name'] =  '其他费用';
                $qiList['bill_entry_amount'] = $item['bill_entry_amount'];
                $qiList['paid_entry_amount'] = $item['paid_entry_amount'];
                $qiList['prefer_entry_amount'] = $item['prefer_entry_amount'];
                if(empty($resultList[99])){
                    $resultList[99] = $qiList;
                }else{
                    $resultList[99]['bill_entry_amount'] += $item['bill_entry_amount'];
                    $resultList[99]['paid_entry_amount'] += $item['paid_entry_amount'];
                    $resultList[99]['prefer_entry_amount'] += $item['prefer_entry_amount'];

                    $resultList[99]['bill_entry_amount'] = sprintf("%.2f",$resultList[99]['bill_entry_amount']);
                    $resultList[99]['paid_entry_amount'] = sprintf("%.2f",$resultList[99]['paid_entry_amount']);
                    $resultList[99]['prefer_entry_amount'] = sprintf("%.2f",$resultList[99]['prefer_entry_amount']);
                }
                continue;
            }
//            if($item['acct_period_start']==($valiTime+86400) && $resultList[$valiKey]['cost_id'] == $item['cost_id']){
            if((date('Y-m-d',$item['acct_period_start'])==date('Y-m-d',($valiTime+86400))) && $resultList[$valiKey]['cost_id'] == $item['cost_id']){
                $resultList[$valiKey]['acct_period_end'] = $item['acct_period_end'];
                $resultList[$valiKey]['bill_entry_amount'] += $item['bill_entry_amount'];
                $resultList[$valiKey]['paid_entry_amount'] += $item['paid_entry_amount'];
                $resultList[$valiKey]['prefer_entry_amount'] += $item['prefer_entry_amount'];

                $resultList[$valiKey]['bill_entry_amount'] = sprintf("%.2f",$resultList[$valiKey]['bill_entry_amount']);
                $resultList[$valiKey]['paid_entry_amount'] = sprintf("%.2f",$resultList[$valiKey]['paid_entry_amount']);
                $resultList[$valiKey]['prefer_entry_amount'] = sprintf("%.2f",$resultList[$valiKey]['prefer_entry_amount']);
                $valiTime = $item['acct_period_end'];
                continue;
            }
            $valiKey = $key;
            $valiTime = $item['acct_period_end'];
            $resultList[$valiKey] = $item;
        }
        return $resultList;
    }

    // 模板 配置 收据&催缴单打印用 $data的来源 TemplateService->printBillInfo && PrintService->billListNew
    public function templateIncome($data, $template_id)
    {
        $result = [];
        if (!empty($data)) {
            $num = PsTemplateBill::findOne($template_id)->num;
            $i = 0;
            if (!empty($num)) {
                // $data是按房屋的数组 每个房屋的都是独立页面（不同房屋的不能打印在同一页） 对应一个房屋如果超过设置的每页条数还需分页
                foreach ($data as $k => $v) { 
                    if (!empty($v['bill_list'])) {    
                        $page = ceil(count($v['bill_list']) / $num); // 计算分页
                        $start = 0; // 开始位置
                        $end = 0; // 结束位置
                        for($j = 0; $j < $page; $j++) {
                            $start = $j * $num; 
                            $end = $start + $num; 
                            
                            $table = self::_table($v, $template_id, $start, $end); // 表格区

                            $top = self::_top($v, $template_id, $i, $table['total_amount']); // 页眉区
                            $down = self::_down($v, $template_id, $table['total_amount']); // 页脚区

                            $result[$i]['top'] = $top['list'];
                            $result[$i]['list'] = $table['list'];
                            $result[$i]['table'] = $table['table'];
                            $result[$i]['down'] = $down['list'];
                            $i++;
                        }
                    }
                }
            }
        }
        return $result;
    }
    
    // 页眉区
    private function _top($data, $template_id, $page = 0, $total_amount)
    {
        $new_config = PsTemplateConfig::getList(['rows' => 99, 'type' => 1, 'template_id' => $template_id], 'field_name, name, width');
        
        foreach ($new_config as $k => $v) {
            $list[$k]['name'] = $v['name'];
            $list[$k]['field_name'] = $v['field_name'];
            $list[$k]['width'] = $v['width'];

            switch ($v['field_name']) {
                case 'title': // 模板标题
                    $list[$k]['value'] = PsTemplateBill::findOne($template_id)->name;
                    break;
                case 'number': // 打印编号
                    $company_id = $data['room_data']['company_id'];
                    $create_at = strtotime(date("Y-m-d"), time());
                    $print = Yii::$app->db->createCommand("SELECT * from ps_print_number where company_id = $company_id and create_at = $create_at;")->queryOne();
                    $list[$k]['value'] = date("YmdHi", time()) . sprintf("%02d", $print['number']).$page; 
                    break;
                case 'total': // 合计当前页
                    $list[$k]['value'] = $total_amount;
                    break;
                default: // 其他字段对应的值 取$data['room_data']里的数据
                    $list[$k]['value'] = $data['room_data'][$v['field_name']];
                    break;
            }
        }

        $result['list'] = !empty($list) ? $list : [];

        return $result;
    }
    
    // 表格区
    private function _table($data, $template_id, $start, $end)
    {
        $new_config = PsTemplateConfig::getList(['rows' => 99, 'type' => 2, 'template_id' => $template_id], 'field_name, name');
        $template_type = PsTemplateBill::findOne($template_id)->type; // 模板类型 1、通知单模板 2、收据模板

        $i = 0;
        $total_amount = 0;
        foreach ($data['bill_list'] as $key => $val) {
            if ($key >= $start && $key < $end) {
                $list[$i]['id'] = !empty($val['id']) ? $val['id'] : '';
                foreach ($new_config as $k => $v) {
                    $table[$k]['id'] = $k+1;
                    $table[$k]['name'] = $v['name'];
                    $table[$k]['field_name'] = $v['field_name'];
                    // 值取$data['bill_list']里对应的数据
                    $list[$i][$v['field_name']] = !empty($val[$v['field_name']]) ? $val[$v['field_name']] : '';
                }
                $i++;
                if ($template_type == 1) { // 通知单模板 没有实收金额 所以统计应收金额
                    $total_amount += $val['bill_amount'];
                } else { // 收据模板 统计实收金额
                    $total_amount += $val['pay_amount'];
                }
            }
        }

        $result['list'] = !empty($list) ? $list : []; // 表格区列表数据
        $result['table'] = !empty($table) ? $table : []; // 表格区字段
        $result['total_amount'] = $total_amount; // 当前页的合计金额 页眉和页脚需要用到

        return $result;
    }
    
    // 页脚区
    private function _down($data, $template_id, $total_amount)
    {
        $new_config = PsTemplateConfig::getList(['rows' => 99, 'type' => 3, 'template_id' => $template_id], 'field_name, name, width, note');
        
        foreach ($new_config as $k => $v) {
            $list[$k]['name'] = $v['name'];
            $list[$k]['field_name'] = $v['field_name'];
            $list[$k]['width'] = $v['width'];
            
            if ($v['field_name'] == 'note') { // 备注说明
                $list[$k]['value'] = !empty($v['note']) ? $v['note'] : '';
            } else if ($v['field_name'] == 'total') { // 合计 就计算当前页的合计 不是所有数据的合计
                $list[$k]['value'] = $total_amount;
            } else { // 其他字段对应的值 取$data['room_data']里的数据
                $list[$k]['value'] = !empty($data['room_data'][$v['field_name']]) ? $data['room_data'][$v['field_name']] : '';
            }
        }

        $result['list'] = !empty($list) ? $list : [];

        return $result;
    }

    // +------------------------------------------------------------------------------------
    // |----------------------------------     模板管理第二步     --------------------------
    // +------------------------------------------------------------------------------------

    // 模板 详情 第二步
    public function templateConfigShow($param)
    {
        $model['top'] = PsTemplateConfig::getList(['rows' => 99, 'type' => 1, 'template_id' => $param['id'], 'community_id' => $param['community_id']], 'id, width, name, field_name', 'SORT_ASC');
        $model['table'] = PsTemplateConfig::getList(['rows' => 99, 'type' => 2, 'template_id' => $param['id']], 'id, name, field_name', 'SORT_ASC');
        $model['down'] = PsTemplateConfig::getList(['rows' => 99, 'type' => 3, 'template_id' => $param['id']], 'id, width, name, field_name, note', 'SORT_ASC');

        return $this->success($model);
    }

    // 模板 新增 第二步
    public function templateConfigAdd($param)
    {
        if (!is_array($param['field_name_list'])) {
            return $this->failed('显示内容格式不对！');
        }

        $template = PsTemplateBill::getOne(['id' => $param['template_id']]);
        
        if (!$template) {
            return $this->failed('模板不存在！');
        }

        $trans = Yii::$app->getDb()->beginTransaction();
        
        try {
            if (!empty($param['field_name_list'])) {
                foreach ($param['field_name_list'] as $key => $val) {
                    if (!empty($val['field_name'])) {
                        PsTemplateConfig::deletes(['template_id' => $param['template_id'], 'type' => $param['type'], 'field_name' => $val['field_name']]);
                        $model = new PsTemplateConfig();
                        $model->setScenario('add');
                        $model->template_id = $param['template_id'];
                        $model->type = $param['type'];
                        $model->width = !empty($val['width']) ? $val['width'] : $param['width'];
                        $model->logo_img = $param['logo_img'];
                        $model->note = !empty($val['note']) ? $val['note'] : $param['note'];
                        $model->field_name = $val['field_name'];
                        $model->name = self::typeDropDown(['template_type' => $template['type'], 'type' => $param['type'], 'field_name' => $val['field_name']])['name'];

                        if (!$model->save()) {
                            return $this->failed($this->getError($model));
                        }
                    } else {
                        return $this->failed('显示内容格式不对！');
                    }
                }
            }

            $trans->commit();
            return $this->success();
        } catch (\Exception $e) {
            $trans->rollBack();
            return $this->failed($e->getMessage());
        }
    }

    // 显示内容 删除
    public function templateConfigDelete($param)
    {
        $model = PsTemplateConfig::getOne($param);

        if (!$model) {
            return $this->failed('数据不存在');
        }

        if (PsTemplateConfig::deleteOne($param)) {
            return $this->success();
        }

        return $this->failed();
    }

    // 显示内容下拉
    public function typeDropDown($param)
    {
        $model = [
            1 => [ // 通知单模板
                // 页眉区
                1 => [
                    'title' => ['name' => '标题', 'field_name' => 'title'],
                    'img' => ['name' => '生活号二维码', 'field_name' => 'img'],
                    'number' => ['name' => '编号', 'field_name' => 'number'],
                    'print_date' => ['name' => '打印日期', 'field_name' => 'print_date'],
                    'community_name' => ['name' => '小区名称', 'field_name' => 'community_name'],
                    'house_info' => ['name' => '房屋信息', 'field_name' => 'house_info'],
                    'house_area' => ['name' => '房屋面积', 'field_name' => 'house_area'],
                    'true_name' => ['name' => '住户姓名', 'field_name' => 'true_name'],
                    'total' => ['name' => '合计', 'field_name' => 'total']
                ],
                // 表格区
                2 => [ 
                    'house_info' => ['name' => '房屋信息', 'field_name' => 'house_info'],
                    'house_area' => ['name' => '房屋面积', 'field_name' => 'house_area'],
                    'pay_item' => ['name' => '收费项目', 'field_name' => 'pay_item'],
                    'formula' => ['name' => '收费标准', 'field_name' => 'formula'],
                    'start_at' => ['name' => '开始时间', 'field_name' => 'start_at'],
                    'end_at' => ['name' => '结束时间', 'field_name' => 'end_at'],
                    'start' => ['name' => '起度', 'field_name' => 'start'],
                    'end' => ['name' => '止度', 'field_name' => 'end'],
                    'use' => ['name' => '使用量', 'field_name' => 'use'],
                    'bill_amount' => ['name' => '应缴金额', 'field_name' => 'bill_amount']
                ],
                // 页脚区
                3 => [ 
                    'print_date' => ['name' => '打印日期', 'field_name' => 'print_date'],
                    'community_name' => ['name' => '小区名称', 'field_name' => 'community_name'],
                    'house_info' => ['name' => '房屋信息', 'field_name' => 'house_info'],
                    'true_name' => ['name' => '住户姓名', 'field_name' => 'true_name'],
                    'total' => ['name' => '合计', 'field_name' => 'total'],
                    'note' => ['name' => '说明', 'field_name' => 'note']
                ]
            ],

            2 => [ // 收据模板
                // 页眉区
                1 => [
                    'title' => ['name' => '标题', 'field_name' => 'title'],
                    'img' => ['name' => '生活号二维码', 'field_name' => 'img'],
                    'number' => ['name' => '编号', 'field_name' => 'number'],
                    'print_date' => ['name' => '打印日期', 'field_name' => 'print_date'],
                    'community_name' => ['name' => '小区名称', 'field_name' => 'community_name'],
                    'house_info' => ['name' => '房屋信息', 'field_name' => 'house_info'],
                    'house_area' => ['name' => '房屋面积', 'field_name' => 'house_area'],
                    'true_name' => ['name' => '住户姓名', 'field_name' => 'true_name'],
                    'total' => ['name' => '合计', 'field_name' => 'total'],
                    'pay_date' => ['name' => '收款日期', 'field_name' => 'pay_date'],
                    'payee_name' => ['name' => '收款人', 'field_name' => 'payee_name']
                ],
                // 表格区
                2 => [ 
                    'house_info' => ['name' => '房屋信息', 'field_name' => 'house_info'],
                    'house_area' => ['name' => '房屋面积', 'field_name' => 'house_area'],
                    'pay_item' => ['name' => '收费项目', 'field_name' => 'pay_item'],
                    'formula' => ['name' => '收费标准', 'field_name' => 'formula'],
                    'start_at' => ['name' => '开始时间', 'field_name' => 'start_at'],
                    'end_at' => ['name' => '结束时间', 'field_name' => 'end_at'],
                    'start' => ['name' => '起度', 'field_name' => 'start'],
                    'end' => ['name' => '止度', 'field_name' => 'end'],
                    'use' => ['name' => '使用量', 'field_name' => 'use'],
                    'bill_amount' => ['name' => '应缴金额', 'field_name' => 'bill_amount'],
                    'discount_amount' => ['name' => '优惠金额', 'field_name' => 'discount_amount'],
                    'pay_amount' => ['name' => '实收金额', 'field_name' => 'pay_amount']
                ],
                // 页脚区
                3 => [ 
                    'print_date' => ['name' => '打印日期', 'field_name' => 'print_date'],
                    'community_name' => ['name' => '小区名称', 'field_name' => 'community_name'],
                    'house_info' => ['name' => '房屋信息', 'field_name' => 'house_info'],
                    'true_name' => ['name' => '住户姓名', 'field_name' => 'true_name'],
                    'total' => ['name' => '合计', 'field_name' => 'total'],
                    'pay_date' => ['name' => '收款日期', 'field_name' => 'pay_date'],
                    'payee_name' => ['name' => '收款人', 'field_name' => 'payee_name'],
                    'pay_company' => ['name' => '收款单位', 'field_name' => 'pay_company'],
                    'pay_channel' => ['name' => '收款方式', 'field_name' => 'pay_channel'],
                    'pay_note' => ['name' => '收款备注', 'field_name' => 'pay_note'],
                    'note' => ['name' => '说明', 'field_name' => 'note']
                ]
            ]
        ];
        
        if (!empty($param['field_name'])) {
            return $model[$param['template_type']][$param['type']][$param['field_name']];
        } else {
            return $model[$param['template_type']][$param['type']];
        }
    }

    // +------------------------------------------------------------------------------------
    // |----------------------------------     生成默认模板     ----------------------------
    // +------------------------------------------------------------------------------------

    // 显示内容 默认 生成默认模板的时候用
    public function typeDefault($param)
    {
        $model = [
            1 => [ // 通知单模板
                // 页眉区
                1 => [
                    ['name' => '标题', 'field_name' => 'title'],
                    ['name' => '编号', 'field_name' => 'number'],
                    ['name' => '小区名称', 'field_name' => 'community_name', 'width' => 1],
                    ['name' => '房屋信息', 'field_name' => 'house_info', 'width' => 1],
                    ['name' => '房屋面积', 'field_name' => 'house_area', 'width' => 1],
                ],
                // 表格区
                2 => [ 
                    ['name' => '房屋信息', 'field_name' => 'house_info'],
                    ['name' => '房屋面积', 'field_name' => 'house_area'],
                    ['name' => '收费项目', 'field_name' => 'pay_item'],
                    ['name' => '开始时间', 'field_name' => 'start_at'],
                    ['name' => '结束时间', 'field_name' => 'end_at'],
                    ['name' => '起度', 'field_name' => 'start'],
                    ['name' => '止度', 'field_name' => 'end'],
                    ['name' => '使用量', 'field_name' => 'use'],
                    ['name' => '应缴金额', 'field_name' => 'bill_amount']
                ],
                // 页脚区
                3 => [ 
                    ['name' => '合计', 'field_name' => 'total', 'width' => 3],
                    ['name' => '小区名称', 'field_name' => 'community_name', 'width' => 1],
                    ['name' => '房屋信息', 'field_name' => 'house_info', 'width' => 1],
                    ['name' => '住户姓名', 'field_name' => 'true_name', 'width' => 1],
                    ['name' => '说明', 'field_name' => 'note', 'note' => '依据合同约定，逾期缴纳将产生违约金。', 'width' => 3],
                ]
            ],

            2 => [ // 收据模板
                // 页眉区
                1 => [
                    ['name' => '标题', 'field_name' => 'title'],
                    ['name' => '编号', 'field_name' => 'number'],
                    ['name' => '小区名称', 'field_name' => 'community_name', 'width' => 1],
                    ['name' => '房屋信息', 'field_name' => 'house_info', 'width' => 1],
                    ['name' => '房屋面积', 'field_name' => 'house_area', 'width' => 1],
                ],
                // 表格区
                2 => [ 
                    ['name' => '收费项目', 'field_name' => 'pay_item'],
                    ['name' => '开始时间', 'field_name' => 'start_at'],
                    ['name' => '结束时间', 'field_name' => 'end_at'],
                    ['name' => '使用量', 'field_name' => 'use'],
                    ['name' => '应缴金额', 'field_name' => 'bill_amount'],
                    ['name' => '优惠金额', 'field_name' => 'discount_amount'],
                    ['name' => '实收金额', 'field_name' => 'pay_amount']
                ],
                // 页脚区
                3 => [ 
                    ['name' => '合计', 'field_name' => 'total', 'width' => 3],
                    ['name' => '收款人', 'field_name' => 'payee_name', 'width' => 1],
                    ['name' => '收款单位', 'field_name' => 'pay_company', 'width' => 1],
                    ['name' => '收款日期', 'field_name' => 'pay_date', 'width' => 1],
                    ['name' => '收款备注', 'field_name' => 'pay_note', 'width' => 3]
                ]
            ]
        ];
        
        return $model[$param['template_type']][$param['type']];
    }

    // 初始化生成 默认模板
    public function templateDefault($community_id = 0)
    {
        if (empty($community_id)) { // 初始化 生成所有小区的默认模板
            $model = PsCommunityModel::find()->select('distinct(id)')->asArray()->all();
        } else if (!empty($community_id)) { // 新增新小区的时候 生成该小区的默认模板
            $model = PsCommunityModel::find()->select('distinct(id)')->where(['=', 'id', $community_id])->asArray()->all();
        }

        if ($model) {
            $trans = Yii::$app->getDb()->beginTransaction();
            try {
                $arr = ['1' => '通知单模板示例', '2' => '收据模板示例'];
                $type_arr = ['1' => '页眉', '2' => '表格', '3' => '页脚'];
                foreach ($model as $k => $v) { // 循环小区
                    $community_id = $v['id']; // 小区ID
                    $param = [];
                    foreach ($arr as $template_type => $template_name) { // 循环模板类型
                        $template = PsTemplateBill::find()->where(['=', 'community_id', $community_id])
                            ->andWhere(['=', 'type', $template_type])->one();
                        if (empty($template)) { // 小区没有对应类型的模板时生成默认模板
                            $param['community_id'] = $community_id; // 小区ID
                            $param['name'] = $template_name; // 模板名称
                            $param['type'] = $template_type; // 模板类型 1、通知单模板 2、收据模板
                            $param['layout'] = 1; // 打印布局 1、横向
                            $param['paper_size'] = 1; // 纸张大小 1、A4
                            $param['num'] = 10; // 内容数量
                            $model = new PsTemplateBill(['scenario' => 'add']);
                            
                            if (!$model->load($param, '') || !$model->validate()) {
                                return $this->failed($this->getError($model));
                            }
                            
                            if ($model->saveData('add', $param)) { // 模板新增成功 再 新增模板配置内容
                                $data['template_id'] = $model->attributes['id']; // 模板ID
                                foreach ($type_arr as $type => $value) {
                                    $data['type'] = $type; // 类型 1、页眉 2、表格 3、页脚
                                    $data['field_name_list'] = self::typeDefault(['template_type' => $template_type, 'type' => $type]);
                                    self::templateConfigAdd($data); // 新增模板配置内容
                                }
                            } else {
                                return $this->failed($this->getError($model));
                            }
                        }
                    }   
                }
                $trans->commit();
                return $this->success();
            } catch (\Exception $e) {
                $trans->rollBack();
                return $this->failed($e->getMessage());
            }
        }
    }

    // +------------------------------------------------------------------------------------
    // |----------------------------------     模板管理第一步     --------------------------
    // +------------------------------------------------------------------------------------

    // 模板 新增 编辑
    private function _templateSave($param, $scenario)
    {
        if (!empty($param['id'])) {
            if (!PsTemplateBill::getOne($param)) {
                return $this->failed('数据不存在！');
            }
        }

        $model = new PsTemplateBill(['scenario' => $scenario]);

        if (!$model->load($param, '') || !$model->validate()) {
            return $this->failed($this->getError($model));
        }

        if (!$model->saveData($scenario, $param)) {
            return $this->failed($this->getError($model));
        }
        // 成功返回 模板id和名称 模板新增第二步需要用
        $data['id'] = !empty($model->attributes['id']) ? $model->attributes['id'] : $param['id'];
        $data['name'] = $model->attributes['name'];

        return $this->success($data);
    }

    // 模板 新增
    public function templateAdd($param)
    {
        return $this->_templateSave($param, 'add');
    }

    // 模板 编辑
    public function templateEdit($param)
    {
        return $this->_templateSave($param, 'edit');
    }

    // 模板 列表
    public function templateList($param)
    {
        $list = PsTemplateBill::getList($param);

        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['type_desc'] = PsTemplateBill::getType($v['type']);
                $list[$k]['layout_desc'] = PsTemplateBill::getLayout($v['layout']);
                $list[$k]['paper_size_desc'] = PsTemplateBill::getPaperSize($v['paper_size']);
            }
        }

        $result['list']   = $list;
        $result['totals'] = PsTemplateBill::getTotals($param);

        return $result;
    }

    // 模板 删除
    public function templateDelete($param)
    {
        $model = PsTemplateBill::getOne($param);

        if (!$model) {
            return $this->failed('数据不存在');
        }

        if ($model['community_id'] != $param['community_id']) {
            //return $this->failed('没有权限删除此数据');
        }

        if (PsTemplateBill::deleteOne($param)) {
            return $this->success();
        }

        return $this->failed();
    }

    // 模板 详情
    public function templateShow($param)
    {
        $model = PsTemplateBill::getOne($param);

        if (!$model) {
            return $this->failed('数据不存在');
        }

        if ($model['community_id'] != $param['community_id']) {
            //return $this->failed('没有权限查看此数据');
        }

        return $this->success($model);
    }

    // 模板下拉 催缴单&&收据 打印的时候选择模板
    public function templateDropDown($param)
    {
        if (!$param['community_id']) {
            return $this->failed('小区id不能为空');
        }
        if (!$param['type']) {
            return $this->failed('模板类型不能为空');
        }

        $result['list'] = PsTemplateBill::getDropDown($param);

        return $this->success($result);
    }
}
