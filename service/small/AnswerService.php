<?php
namespace service\small;

use app\models\Answer;
use app\models\PsAppMember;
use app\models\PsAppUser;
use app\models\PsMember;
use common\core\F;
use common\MyException;
use Yii;
use service\BaseService;


Class AnswerService extends BaseService
{

    public $start = '2019-11-05';
    public $end = '2019-11-15';

    public $choose_answer_list = [
        [
            'problem' => '室内不得存放超过( )公斤的汽油',
            'answer' => ['A、1.5','B、1','C、0.5 '],
            'right' => 'C',
            'type' => '1'
        ],
        [
            'problem' => '检查煤气等燃气灶具是否漏气应该采取的正确方法是( )',
            'answer' => ['A、用鼻子闻','B、用火试','C、用肥皂水刷到软管和接口处检查'],
            'right' => 'C',
            'type' => '1'
        ],
        [
            'problem' => '当打开房门闻到很浓的燃气气味时，要迅速( )，防止引发爆燃事故',
            'answer' => ['A、打开电灯查找漏气部位','B、打开门窗通风','C、点火查看'],
            'right' => 'B',
            'type' => '1'
        ],
        [
            'problem' => '家庭中使用燃气设施和用具时，正确的做法是( )',
            'answer' => ['A、自行更换、拆改燃气的管道','B、燃气使用完毕后不用关闭总阀门','C、经常检查燃气灶具及管道，不擅自安装、拆改','D、使用液化气罐时罐内残留的液体可以倒在下水道里'],
            'right' => 'C',
            'type' => '1'
        ],
        [
            'problem' => '居民家庭应配备哪种消防器材( )',
            'answer' => ['A、沙土、水缸、水桶','B、逃生绳','C、防毒面具'],
            'right' => 'A',
            'type' => '1'
        ],
        [
            'problem' => '发生火灾逃生时，要尽量贴近地面撤离，主要原因是( )',
            'answer' => ['A、看得清地上有无障碍物','B、燃烧产生的有毒热烟在离地面近的地方浓度较小，可降低中毒几率','C、以免碰着别人'],
            'right' => 'B',
            'type' => '1'
        ],
        [
            'problem' => '由于行为人的过失引起火灾，造成严重后果的行为，构成( )',
            'answer' => ['A、纵火罪','B、失火罪','C、玩忽职守罪'],
            'right' => 'B',
            'type' => '1'
        ],
        [
            'problem' => '我国每年的“119”消防宣传活动日是( )',
            'answer' => [' A、11月9日','B、1月19日','C、9月11日 '],
            'right' => 'A',
            'type' => '1'
        ],
        [
            'problem' => '遇到火灾时，正确的做法是( )',
            'answer' => ['A、沉着、冷静，迅速正确逃生','B、不用着急，等待消防队救援','C、迅速坐电梯逃生'],
            'right' => 'A',
            'type' => '1'
        ],
        [
            'problem' => '如果睡觉时被烟火呛醒，正确的逃生方式是( )',
            'answer' => ['A、立即寻找逃生通道，迅速逃生','B、往床底下钻','C、穿好衣服再走'],
            'right' => 'A',
            'type' => '1'
        ],
        [
            'problem' => '发生火灾后，如何正确报火警( )',
            'answer' => ['A、讲清着火单位、详细地址、着火物质及火势大小','B、讲清着火单位、详细地址、着火物质及火势大小，是否有人被困，留下报警人 姓名及联系方式 ','C、告知着火方位后迅速挂断电话，返回火场'],
            'right' => 'B',
            'type' => '1'
        ],
        [
            'problem' => '单位发生火灾，你首先应( )',
            'answer' => ['A、及时拨打119火警电话并通知单位消防负责人','B、先自行扑救，救不了时再报火警','C、只拨打单位内部报警电话，不拨打119火警电话'],
            'right' => 'A',
            'type' => '1'
        ],
        [
            'problem' => '谎报火警是违法行为。谎报火警的，最高可以处( )日拘留',
            'answer' => ['A、3','B、5','C、8','D、10'],
            'right' => 'D',
            'type' => '1'
        ],
        [
            'problem' => '公安消防队扑救火灾是否收取费用( )',
            'answer' => ['A、收取成本费用','B、按照出动车辆数和扑救时间收费','C、对单位收费，对个人不收费','D、不收取任何费用'],
            'right' => 'D',
            'type' => '1'
        ],
        [
            '个人损坏、挪用或擅自拆除、停用消防设施、器材，埋压、圈占、遮挡消火栓的， 处( )处罚',
            'answer' => ['A、警告或五百元以下罚款','B、十日以下行政拘留','C、劳动教养'],
            'right' => 'A',
            'type' => '1'
        ],
    ];

    public $judge_answer_list = [
        [
            'problem' => '报警人拨打火灾报警电话后，应该到门口或街上等候消防车到来',
            'answer' => ['true','false'],
            'right' => 'true',
            'type' => '2'
        ],
        [
            'problem' => '要做到及时控制和消灭初起火灾，主要是依靠公安消防队',
            'answer' => ['true','false'],
            'right' => 'false',
            'type' => '2'
        ],
        [
            'problem' => '禁止携带易燃易爆危险物品进入公共场所或乘坐交通工具',
            'answer' => ['true','false'],
            'right' => 'true',
            'type' => '2'
        ],
        [
            'problem' => '电器开关时的打火、过热发红的铁器和电焊产生的火花都可能是引火源',
            'answer' => ['true','false'],
            'right' => 'true',
            'type' => '2'
        ],
        [
            'problem' => '电加热设备使用时必须有人员在场，离开时要切断电源',
            'answer' => ['true','false'],
            'right' => 'true',
            'type' => '2'
        ],
        [
            'problem' => '配电箱内所用的保险丝应该越粗越好',
            'answer' => ['true','false'],
            'right' => 'false',
            'type' => '2'
        ],
        [
            'problem' => '法人单位的法定代表人或者非法人单位的主要负责人是单位的消防安全责任人，对本单位的消防安全工作全面负责',
            'answer' => ['true','false'],
            'right' => 'true',
            'type' => '2'
        ],
        [
            'problem' => '当单位的安全出口上锁、遮挡，或者占用、堆放物品影响疏散通道畅通时，单位应当责令有关人员当场改正并督促落实',
            'answer' => ['true','false'],
            'right' => 'true',
            'type' => '2'
        ],
        [
            'problem' => '着火后应自己先扑救，无法灭火时再打"119"',
            'answer' => ['true','false'],
            'right' => 'false',
            'type' => '2'
        ],
        [
            'problem' => '扔掉烟头两小时后再着火可以不用负责任',
            'answer' => ['true','false'],
            'right' => 'false',
            'type' => '2'
        ],
        [
            'problem' => '设置禁火标志的部位不能使用明火，但不限制吸烟',
            'answer' => ['true','false'],
            'right' => 'false',
            'type' => '2'
        ],
        [
            'problem' => '公共娱乐场所内严禁带入和存放易燃易爆物品',
            'answer' => ['true','false'],
            'right' => 'true',
            'type' => '2'
        ],
        [
            'problem' => '商住楼内的公共娱乐场所与居民住宅可以共用一个安全出口',
            'answer' => ['true','false'],
            'right' => 'false',
            'type' => '2'
        ],
        [
            'problem' => '公安消防队扑救火灾，不向发生火灾的单位、个人收取任何费用',
            'answer' => ['true','false'],
            'right' => 'true',
            'type' => '2'
        ],
        [
            'problem' => '灯具的开关、插座和照明器靠近可燃物时，应采取隔热、散热等保护措施',
            'answer' => ['true','false'],
            'right' => 'true',
            'type' => '2'
        ],
    ];

    /**
     * 获取用户信息
     * @author yjh
     * @param $params
     * @return int|mixed
     * @throws MyException
     */
    public function getUserInfo($params)
    {
        $app_member = PsAppMember::find()->where(['app_user_id' => $params['user_id']])->one();
        if ($app_member) {
            $member = PsMember::find()->where(['id' => $app_member['member_id']])->one();
            if (empty($member['mobile'])) {
                throw new MyException('该用户没有绑定手机号');
            } else {
                return $member;
            }
        } else {
            throw new MyException('该用户没有绑定手机号');
        }
    }

    /**
     * 获取用户分数
     * @author yjh
     * @param $member_id
     * @return int|mixed
     */
    public function getUserGrade($member_id)
    {
        $answer = Answer::find()->where(['member_id' => $member_id])->one();
        if (empty($answer['grade'])) {
            return -1;
        } else {
            return $answer['grade'];
        }
    }


    /**
     * 保存答题分数
     * @author yjh
     * @param $params
     * @throws MyException
     */
    public function addGrade($params)
    {
        if (strtotime($this->end) < time()) {
            throw new MyException('活动已截止');
        }
        $member = $this->getUserInfo($params);
        $answer = Answer::find()->where(['member_id' => $member['id']])->one();
        if ($answer) {
            throw new MyException('该用户已经答题过');
        }
        $answer = new Answer();
        $answer->member_id = $member['id'];
        $answer->grade = $params['grade'];
        $answer->app_user_id = $params['user_id'];
        $answer->created_at = time();
        $answer->save();
    }

    /**
     * 获取排行榜
     * @author yjh
     * @param $params
     * @return array
     */
    public function getTopInfo($params)
    {
        $own = [];
        //可能没绑定手机号去看排行榜
        try {
            $member = $this->getUserInfo($params);
            $user = $this->getUserTop($member['id']);
            $own['avatar'] = '';
            if (!empty($user['app_user_id'])) {
               $app_user = PsAppUser::find()->where(['id' => $user['app_user_id']])->one();
               $own['avatar'] = $app_user['avatar'];
            }
            if (!empty($user['member_id'])) {
                $member = PsMember::find()->where(['id' => $user['member_id']])->one();
                $own['name'] = $member['name'];
            }
            $own['top'] = $user['top'];
            $own['grade'] = $user['grade'];
        } catch (MyException $e) {
            $own['top'] = 0;
            $own['grade'] = 0;
        }
        $list = $this->getTopList(20);
        foreach ($list as &$v) {
            $app_user = PsAppUser::find()->where(['id' => $v['app_user_id']])->one();
            $member = PsMember::find()->where(['id' => $v['member_id']])->one();
            $v['name'] = F::processUserName($member['name']);
            $v['avatar'] = $app_user['avatar'];
        }
        return ['list' => $list,'own' => $own];
    }


    /**
     * 获取指定人排行榜
     * @author yjh
     * @param $member_id
     * @return array|\yii\db\ActiveRecord
     */
    public function getUserTop($member_id)
    {
        $all = $this->getTopList();
        $found_key = array_search($member_id, array_column($all, 'member_id'));
        //如果没答题
        if ($found_key !== false) {
            return $all[$found_key];
        } else {
            return ['top' => 0,'grade' => 0,'app_user_id' => 0,'member_id' => 0];
        }
    }

    /**
     * 获取排行列表
     * @author yjh
     * @param bool $limit
     * @return array|\yii\db\ActiveQuery|\yii\db\ActiveRecord[]
     */
    public function getTopList($limit = false)
    {
        $all = Answer::find()->orderBy('grade desc,created_at desc,app_user_id,member_id');
        if ($limit) {
            $all = $all->limit($limit)->asArray()->all();
        } else {
            $all = $all->asArray()->all();
        }
        if (!empty($all)) {
            foreach ($all as $k => &$v) {
                $v['top'] = ++$k;
            }
        }
        return $all;
    }

    public function getList()
    {
        $choose_data = [];
        $judge_data = [];
        $choose = array_rand($this->choose_answer_list,5);
        foreach ($choose as $v) {
            $choose_data[] = $this->choose_answer_list[$v];
        }
        $judge = array_rand($this->judge_answer_list,5);
        foreach ($judge as $vv) {
            $judge_data[] = $this->judge_answer_list[$vv];
        }
        $data = array_merge($choose_data,$judge_data);
        shuffle($data);
        return ['list' => $data];
    }

    public function getTime()
    {
        if (strtotime($this->end) < time()) {
            $type = 1; //过期
        } else {
            $type = 2;
        }
        return ['start' => $this->start,'end' => $this->end,'type' => $type];
    }
}