<?php

namespace app\api\controller;

use app\api\controller\BaseController;

use app\api\model\MillionsConfig;
use app\api\model\CMMillionsFriend;
use app\api\model\MillionsActivity;
use app\api\model\CMMillionsTask;
use app\api\model\MillionsShare;
use app\api\model\UserInfo;
use app\api\model\UserList;
use app\api\model\UserTime;
use app\api\model\MillionsListRecord;
use app\api\model\MillionsList;

use app\api\model\game\GameRedpacket;
use app\api\controller\RedPacket;

use app\api\model\WLockThread;

use think\cache\driver\Memcache;
use think\facade\Request;



class Millions extends BaseController
{
    protected $m_CMMillionsConfig;
    protected $m_CMMillionsFriend;
    protected $m_CMMillionsActivity;
    protected $m_CMMillionsTask;
    protected $m_CMMillionsShare;
    protected $m_CMUserList;
    protected $m_CMUserInfo;
    protected $m_MillionsRedpacket;
    protected $m_WLockThread;
    protected $m_UserTime;
    protected $m_MillionsListRecord;
    protected $m_MillionsList;
    protected $act_id;


    public function __construct()
    {
        parent::__construct();

        $this->m_CMMillionsConfig = new MillionsConfig();
        $this->m_CMMillionsFriend = new CMMillionsFriend();
        $this->m_CMMillionsActivity = new MillionsActivity();
        $this->m_CMMillionsTask = new CMMillionsTask();
        $this->m_CMUserList = new UserList();
        $this->m_CMUserInfo = new UserInfo();
        $this->m_CMMillionsShare = new MillionsShare();
        $this->m_UserTime = new UserTime;
        $this->m_MillionsRedpacket = new GameRedpacket();
        $this->m_WLockThread = new WLockThread();
        $this->m_MillionsListRecord = new MillionsListRecord();
        $this->act_id = 1;//活动ID 默认为1
    }


	/**
     * @cc 类入口文件
     * @Author   seaboyer@163.com
     * @DateTime 2018-08-30
     * @return   [type]        [description]
     */
    public function index()
    {
        $action = $this->request_action;
        //设置此次活动id
        $one_act_id = $this->m_CMMillionsActivity->findLastActivity();
        if ($one_act_id) {
            $this->act_id = $one_act_id['id'];
        }

        if(!empty($action)){
            switch($action)
            {
                //获取用户百万红包状态
                case 'millions_user_status';
                    $this->millionsUserStatus();
                    break;
                //未领取红包页面
                case 'millions_get_packet';
                    $this->millionsGetPacket();
                    break;
                //拆红包（正在拆和已拆完）
                case 'millions_open_packet':
                    $this->millionsOpenPacket();
                    break;
                //首次拆红包
                case 'millions_first_packet':
                    $this->millionsFirstPacket();
                    break;
                //所有红包已领完界面
                case 'millions_all_get':
                    $this->millionsAllGet();
                    break;
                //转入红包                                               //未测
                case 'millions_get_cash';
                    $this->millionsGetCash();
                    break;
                //规则H5页接口
                case 'millions_rules_url':
                    $this->millionsRulesUrl();
                    break;
                //好友帮拆页
                case 'millions_friend_show':
                    $this->millionsFriendShow();
                    break;
                //帮拆                                                  //未测
                case 'millions_friend_open':
                    $this->millionsFriendOpen();
                    break;
                //广告控制
                case 'red_packet_ad_control':
                    $this->redPacketAdControl();
                    break;
                //帮拆并发测试
                case 'help_open_high_concurrency_test' :
                    $this->helpOpenHighConcurrencyTest();
                    break;
                //百万红包引导红包按钮
                case 'add_millions_guide' :
                    $this->addMillionsGuide();
                    break;
                default:
                    $this->millionsIndex();
                    break;
            }
        }else{
            $this->millionsIndex();
        }

    }

    public function millionsIndex ()
    {
        $this->echoErrorInfo();
    }
/**********************************************************************************************************************/
    /**
     * @cc 获取用户百万红包状态
     * @Author   89776730@qq.com
     * @DateTime 2018-09-03
     * @return   [type]        [description]
     */
    public function millionsUserStatus ()
    {
        $req = $this->request_param;
        $uid = $this->uid;
        $list_data = [];
        $now_time = time();
        if (!empty($req)) {
            //添加首页百万红包引导红包点击记录
            if (!empty($req['millions_id'])) {
                $millions_list_data = [
                    'uid' => $uid,
                    'packet_id' => $req['millions_id'],
                    'addtime' => $now_time,
                    'status' => '1'
                ];
                $this->m_MillionsListRecord->insertInfo($millions_list_data);
            }
            //埋点统计 百万红包点击次数加一
            $this->writeCountLog('7', '105');

            //埋点统计 百万红包点过的人数加一
            $one_is_clicked = $this->m_UserTime->is_clicked($uid);
            if (!$one_is_clicked['time1']) {
                $is_clicked_param = [
                    'uid' => $uid,
                    'time1' => $now_time
                ];
                $this->m_UserTime->insertInfo($is_clicked_param);
                $this->writeCountLog('7', '111');
            }
            //查询百万红包用户状态
            $t_millions_task = get_db_table_name('millions_task', $uid);    //获取分表表名
            $this->m_CMMillionsTask->setTableName($t_millions_task);        //设置表名
            $one_expire = $this->m_CMMillionsTask->getMillionsExpire($uid);
            if (!empty($one_expire)) {
                foreach ($one_expire as $k => $v) {
                    $param = [
                        'status' => 8,
                        'endtime' => $v['t_time_end']
                    ];
                    $this->m_CMMillionsTask->updateInfo($v['id'], $param);   //更正过期任务状态
                }
            }
            $list_underway = $this->m_CMMillionsTask->getIsExistUnderway($uid);
            //获取每日红包上限数
            $one_limit_everyday = $this->m_CMMillionsActivity->getActivityPacketLimit($this->act_id);
            $limit_everyday = $one_limit_everyday['a_user_limit'];
            if (empty($list_underway[0]['id'])) {                    //没有正在进行的任务(待拆 或 当日已领完)
                $get_packet_num = $this->m_CMMillionsTask->overPacketCount($uid);
                if ($get_packet_num < $limit_everyday) {
                    $user_status = 1;                                       //待拆
                } else {
                    $user_status = 5;                                       //当日已领完
                }
            } else {                                                //有正在进行的任务
                $user_status = 3;                                           //拆红包中（包括拆完待提现）
            }
            $list_data['user_status'] = $user_status;

            $this->writeApiLog($this->request_action, $this->uid, json_encode($req), json_encode($list_data), 1);
            sdk_return($list_data);
        } else {
            $this->writeApiLog($this->request_action, $this->uid, json_encode($req), json_encode($list_data), 6);
            sdk_return('', 6, 'request为空');
        }
    }
/**********************************************************************************************************************/
    /**
     * @cc 未领取红包页面
     * @Author   89776730@qq.com
     * @DateTime 2018-09-03
     * @return   [type]        [description]//
     */
    public function millionsGetPacket ()
    {
        $req = $this->request_param;
        $uid = $this->uid;
        $list_data = [];
        $now_time = time();
        $date = date('Ymd', $now_time);
        $memcache = get_memcache();
        if (!empty($req)) {
            //检查用户状态是否为 未领取红包状态（状态值没有小于5的 且大于5的小于3个）
            $t_millions_task = get_db_table_name('millions_task', $uid);//获取表名
            $this->m_CMMillionsTask->setTableName($t_millions_task);    //设置表名

            $num1 = $this->m_CMMillionsTask->ltOrGtGetCount($uid, 'lt' ,5);
            $num2 = $this->m_CMMillionsTask->ltOrGtGetCount($uid, 'gt' ,5, $date);
            //获取每日红包上限数
            $one_limit_everyday = $this->m_CMMillionsActivity->getActivityPacketLimit($this->act_id);
            $limit_everyday = $one_limit_everyday['a_user_limit'];
            if (($num1 == 0) || ($num2 < $limit_everyday)) {
                //获取本次活动可能获得的最大红包金额（在配置表所有红包类型中取最大值）
//                $millions_money_up = $this->m_CMMillionsConfig->getActivityMaxMoney();
//                $millions_money_up = round($millions_money_up, 2);
                $list_data['money_limit'] = "100";
                //获取陌生人领取信息列表(从b_user_list随机拼取)(改动)
                $one_roll_config = $this->m_CMMillionsActivity->getRollMinMaxCount($this->act_id);
                $roll_min = $one_roll_config['a_roll_min'];
                $roll_max = $one_roll_config['a_roll_max'];
                $roll_count = $one_roll_config['a_roll_count'];

                //全部用户从全局缓存中获取
                $key_rand_user_list = 'key_rand_user_list';
                $arr_res = $memcache->get($key_rand_user_list);

                if (empty($arr_res)) {
                    $rand_expire_time = 15;//缓存时间
                    $arr_res = $this->m_CMUserList->getRandUserList($uid, $roll_count, $roll_min, $roll_max);
                    $memcache->set($key_rand_user_list, $arr_res, false, $rand_expire_time);
                }
//                $user_info = [];
//                foreach ($arr_res as $k => $v) {//拼假数据
//                    $avatar = get_user_avatar($v['uid'], 2);
//                    $money = get_random_num(10, 100 ,2);
//                    $money = sprintf("%.2f",$money);
//                    $user_info['name'] = $v['nickname'];
//                    $user_info['avatar'] = $avatar;
//                    $user_info['money'] = "$money";
//                    $list_data['get_user_list'][] = $user_info;
//                }
                $list_data['get_user_list'] = $arr_res;
                $this->writeApiLog($this->request_action, $this->uid, json_encode($req), json_encode($list_data), 1);
                sdk_return($list_data);
            } else {
                $this->writeApiLog($this->request_action, $this->uid, json_encode($req), json_encode($list_data), 7);
                sdk_return('', 7, '状态已改变');       //状态已发生改变
            }
        }
    }
/**********************************************************************************************************************/
    /*
     * @cc 拆红包(正在拆 已拆完)
     * @Author   89776730@qq.com
     * @DateTime 2018-09-03
     * @return   [type]        [description]//
     */
    public function millionsOpenPacket ()
    {
        $req = $this->request_param;
        $uid = $this->uid;
        $list_data = [];
        if (!empty($req)) {

            //检查用户状态（正在拆或已拆完）
            $t_millions_task = get_db_table_name('millions_task', $uid);//获取表名
            $this->m_CMMillionsTask->setTableName($t_millions_task);              //设置表名
            $user_status = $this->m_CMMillionsTask->packetIsOver($uid);
            $user_status = $user_status[0];
            if ($user_status['status'] == 1 || $user_status['status'] == 3) {           //正在拆
                $list_data['is_over'] = 0;                                    //是否已拆完(未拆完)
                $user_status['t_money_ready'] = sprintf("%.2f",$user_status['t_money_ready']);
                $list_data['ready_money'] = $user_status['t_money_ready'];    //已拆金额
                $deviation_money = $user_status['t_money_value'] - $user_status['t_money_ready'];
                $deviation_money = sprintf("%.2f",$deviation_money);
                $list_data['deviation_money'] = $deviation_money;             //还差多少拆完
                $list_data['expire_time'] = $user_status['t_time_end'];       //过期时间戳
                //获取平分股比例
                $stock = $this->m_CMMillionsActivity->getStockRadio($this->act_id);
                $stock = $stock*100;
                $list_data['contribute_stock'] = $stock.'%';
                $list_data['deviation_count'] = $user_status['t_need_count'] - $user_status['t_ready_count'];
                //好友帮拆列表
                $list_data['friend_help_list'] = [];

                if ($user_status['status'] == 1) {  //好友未帮拆
                    $list_data['friend_help_list'] = [];
                } else {                            //好友已帮拆
                    $t_millions_friend = get_db_table_name('millions_friend');//获取好友帮拆列表表名
                    $this->m_CMMillionsFriend->setTableName($t_millions_friend);    //设置表名
                    $arr_friends = $this->m_CMMillionsFriend->getFriendsHelpList($user_status['id']);
                    foreach ($arr_friends as $k => $v) {
                        $money = sprintf("%.2f",$v['f_money']);
                        $one_name = $this->m_CMUserList->getNickName($v['f_friend_id']);
                        $name = $one_name['nickname'];
                        $friend_info = [
                            'avatar' => get_user_avatar($v['f_friend_id'], 2),
                            'money'  => $money,
                            'name'   => $name
                        ];
                        $list_data['friend_help_list'][] = $friend_info;
                    }
                }
                //获取陌生人领取信息列表(从b_user_list随机拼取)(改动)
                $one_roll_config = $this->m_CMMillionsActivity->getRollMinMaxCount($this->act_id);
                $roll_min = $one_roll_config['a_roll_min'];
                $roll_max = $one_roll_config['a_roll_max'];
                $roll_count = $one_roll_config['a_roll_count'];

                $arr_res = $this->m_CMUserList->getRandUserList($uid, $roll_count, $roll_min, $roll_max);
//                $user_info = [];
//                foreach ($arr_res as $k => $v) {//拼假数据
//                    $avatar = get_user_avatar($v['uid'], 2);
//                    $money = get_random_num(10, 100 ,2);
//                    $money = sprintf("%.2f",$money);
//                    $user_info['name'] = $v['nickname'];
//                    $user_info['avatar'] = $avatar;
//                    $user_info['money'] = "$money";
//                    $list_data['get_user_list'][] = $user_info;
//                }
                $list_data['others_get_list'] = $arr_res;

                sdk_return($list_data);
            } elseif ($user_status['status'] == 4) {                        //已拆完待提现
                $stock = $this->m_CMMillionsActivity->getStockRadio($this->act_id);
                $stock = $stock*100;
                $list_data['contribute_stock'] = $stock.'%';                //获取平分股比例
                $list_data['is_over'] = 1;                                  //是否已拆完(未拆完)
                $user_status['t_money_value'] = sprintf("%.2f", $user_status['t_money_value']);

                $list_data['ready_money'] = $user_status['t_money_value'];  //已拆金额
                $list_data['deviation_money'] = 0;                          //还差多少拆完
                $list_data['expire_time'] = 0;                              //过期时间戳
                $list_data['deviation_count'] = 0;
                //好友帮拆列表
                $t_millions_friend = get_db_table_name('millions_friend');//获取好友帮拆列表表名
                $this->m_CMMillionsFriend->setTableName($t_millions_friend);    //设置表名
                $arr_friends = $this->m_CMMillionsFriend->getFriendsHelpList($user_status['id']);
                foreach ($arr_friends as $k => $v) {
                    $money = sprintf("%.2f", $v['f_money']);
                    $one_name = $this->m_CMUserList->getNickName($v['f_friend_id']);
                    $name = $one_name['nickname'];
                    $friend_info = [
                        'avatar' => get_user_avatar($v['f_friend_id'], 2, 1),
                        'money'  => $money,
                        'name' => $name
                    ];
                    $list_data['friend_help_list'][] = $friend_info;
                }
                //获取陌生人领取信息列表(从b_user_list随机拼取)(改动)
                $one_roll_config = $this->m_CMMillionsActivity->getRollMinMaxCount($this->act_id);
                $roll_min = $one_roll_config['a_roll_min'];
                $roll_max = $one_roll_config['a_roll_max'];
                $roll_count = $one_roll_config['a_roll_count'];

                $arr_res = $this->m_CMUserList->getRandUserList($uid, $roll_count, $roll_min, $roll_max);
//                $user_info = [];
//                foreach ($arr_res as $k => $v) {//拼假数据
//                    $avatar = get_user_avatar($v['uid'], 2);
//                    $money = get_random_num(10, 100 ,2);
//                    $money = sprintf("%.2f",$money);
//                    $user_info['name'] = $v['nickname'];
//                    $user_info['avatar'] = $avatar;
//                    $user_info['money'] = "$money";
//                    $list_data['get_user_list'][] = $user_info;
//                }
                $list_data['get_user_list'] = $arr_res;
                $this->writeApiLog($this->request_action, $this->uid, json_encode($req), json_encode($list_data), 1);
                sdk_return($list_data);
            } else {
                $this->writeApiLog($this->request_action, $this->uid, json_encode($req), json_encode($list_data), 8);
                sdk_return('', 8, '状态错误');
            }
        }
    }
/**********************************************************************************************************************/
    /**
     * @cc 第一次拆红包
     * @Author   89776730@qq.com
     * @DateTime 2018-09-10
     * @return   [type]        [description]
     */
    public function millionsFirstPacket () {
        $req = $this->request_param;
        $uid = $this->uid;
        $list_data = [];
        $now_time = get_time();
        $memcache = get_memcache();
        if (!empty($req)) {
            //检查用户状态是否为 未领取红包状态（状态值没有小于5的 且大于5的小于每日上限）
            $t_millions_task = get_db_table_name('millions_task', $uid);//获取表名
            $this->m_CMMillionsTask->setTableName($t_millions_task);    //设置表名

            $date = date('Ymd', $now_time);

            $where[] = ['t_uid', '=', $uid];
            $where[] = ['status', '<', '5'];

            $num1 = $this->m_CMMillionsTask->getCount($where);
            $where = null;
            $where[] = ['t_uid', '=', $uid];
            $where[] = ['status', '>', '5'];
            $where[] = ['t_day', '=', $date];
            $num2 = $this->m_CMMillionsTask->getCount($where);
            //获取每日领取红包上限
            $one_packet_limit = $this->m_CMMillionsActivity->getActivityPacketLimit($this->act_id);
            $packet_limit = $one_packet_limit['a_user_limit'];
            if (($num1 == 0) && ($num2 < $packet_limit)) {
                //获取红包过期时长
                $where = null;
                $where['id'] = $this->act_id;
                $where['status'] = 1;
                $field = 'a_expire_time';
                $one_expire = $this->m_CMMillionsActivity->where($where)->field($field)->find();
                $expire = $one_expire['a_expire_time'];     //过期时间时长
                //获取红包配置表配置
                $list_config = $this->m_CMMillionsConfig->getPacketConfig($this->act_id);
                //根据配置概率获取红包配置ID
//                foreach ($list_config as $k => $v) {
//                    $radio[$v['id']] = $v['c_radio'];
//                }
//                $new_radio = [];
//                $end = 0;
//                foreach ($radio as $kk => $vv) {
//                    $start = $end;
//                    $end = $vv + $end;
//                    $new_radio[] = [
//                        'no' => $kk,
//                        'start' => $start,
//                        'end' => $end
//                    ] ;
//                }
//                $rand = mt_rand(0, 100);
//                foreach ($new_radio as $kkk => $vvv) {
//                    if ($rand > $vvv['start'] && $rand <= $vvv['end']) {
//                        $packet_no = $vvv['no'];
//                        break;
//                    }
//                }
                //根据配置概率获取红包配置ID
                $end = 0;
                foreach ($list_config as $k => $v) {
                    $start = $end;
                    $end = $v['c_radio'] + $end;
                    $r['id'] = $v['id'];
                    $r['c_radio'] = $v['c_radio'];
                    $r['start'] = $start;
                    $r['end'] = $end;
                    $res[] = $r;
                }
                $rand = mt_rand(1, $end);

                foreach ($res as $vv) {
                    if ($rand > $vv['start'] && $rand <= $vv['end']) {
                        $packet_no = $vv['id'];
                        break;
                    }
                }
                //根据红包配置id 获取红包配置
                $one_config  = $this->m_CMMillionsConfig->getConfigById($packet_no, $this->act_id);
                $money_up    = $one_config['c_money_up'];
                $money_down  = $one_config['c_money_down'];
                $money_radio = $one_config['c_invite_value'];
                $config_id   = $one_config['id'];
                $need_count  = $one_config['c_invite_num'];

                $money_value = get_random_num($money_down, $money_up, 2); //红包总金额
                //我拆的金额
                $radio_list = explode('|', $money_radio);
                $my_radio = $radio_list[0];
                $money_my = $my_radio / 100 * $money_value;
                $deviation_money = $money_value - $money_my;    //差多少拆完
                //插入任务记录
                $data = [
                    't_act_id'       => $this->act_id,
                    't_config_id'    => $config_id,
                    't_uid'          => $uid,
                    't_day'          => date('Ymd', $now_time),
                    't_money_value'  => $money_value,
                    't_money_my'     => $money_my,
                    't_money_ready'  => $money_my,
                    't_money_friend' => 0,
                    't_need_count'   => $need_count,
                    't_ready_count'  => 0,
                    'addtime'        => $now_time,
                    't_time_end'     => $now_time + $expire,
                    'status'         => 1
                ];
                $this->m_CMMillionsTask->save($data);
                //插入config表统计
                $this->m_CMMillionsConfig->updateTaskNum($config_id);

                //返回数据
                $money_my = sprintf("%.2f",$money_my);
                $deviation_money = sprintf("%.2f",$deviation_money);

                $list_data['is_over'] = 0;                          //是否领完
                $list_data['ready_money'] = $money_my;              //已领金额
                $list_data['deviation_money'] = $deviation_money;   //还差多少领完
                $list_data['expire_time'] = $data['t_time_end'];    //过期时间戳
                $list_data['contribute_stock'] = '';                //分股比例
                $list_data['friend_help_list'] = [];                //好友帮助列表
                $list_data['deviation_count'] = $need_count;                //差多少人帮拆完


                //获取陌生人领取信息列表(从b_user_list随机拼取)(改动)
                $one_roll_config = $this->m_CMMillionsActivity->getRollMinMaxCount($this->act_id);
                $roll_min = $one_roll_config['a_roll_min'];
                $roll_max = $one_roll_config['a_roll_max'];
                $roll_count = $one_roll_config['a_roll_count'];

                //全部用户从全局缓存中获取
                $key_rand_user_list = 'key_rand_user_list';
                $arr_res = $memcache->get($key_rand_user_list);

                if (empty($arr_res)) {
                    $rand_expire_time = 15;//缓存时间
                    $arr_res = $this->m_CMUserList->getRandUserList($uid, $roll_count, $roll_min, $roll_max);
                    $memcache->set($key_rand_user_list, $arr_res, false, $rand_expire_time);
                }



//                $user_info = [];
//                foreach ($arr_res as $k => $v) {//拼假数据
//                    $avatar = get_user_avatar($v['uid'], 2);
//                    $money = get_random_num(10, 100 ,2);
//                    $money = sprintf("%.2f",$money);
//                    $user_info['name'] = $v['nickname'];
//                    $user_info['avatar'] = $avatar;
//                    $user_info['money'] = "$money";
//                    $list_data['get_user_list'][] = $user_info;
//                }
                $list_data['others_get_list'] = $arr_res;
                sdk_return($list_data);
                //埋点统计 发包个数加一
                $this->writeCountLog('7', 101);
                $this->writeApiLog($this->request_action, $this->uid, json_encode($req), json_encode($list_data), 1);
                sdk_return($list_data);
            } else {
                $this->writeApiLog($this->request_action, $this->uid, json_encode($req), json_encode($list_data), 9);
                sdk_return('', 9, '状态不对');
            }
        }
    }
/**********************************************************************************************************************/
    /**
     * @cc 当天所有红包已领完
     * @Author   89776730@qq.com
     * @DateTime 2018-09-06
     * @return   [type]        [description]
     */
    public function millionsAllGet ()
    {
        $req = $this->request_param;
        $uid = $this->uid;
        $list_data = [];
        $now_time = get_time();
        if (!empty($req)) {
            //获取每日红包上限
            $one_limit_everyday = $this->m_CMMillionsActivity->getActivityPacketLimit($this->act_id);
            $limit_everyday = $one_limit_everyday['a_user_limit'];
            //判断今天三个红包是否已经全部获得
            $t_millions_task = get_db_table_name('millions_task', $uid);
            $this->m_CMMillionsTask->setTableName($t_millions_task);
            $where['t_uid'] = $uid;
            $where['t_day'] = date('Ymd', $now_time);
            $today_count = $this->m_CMMillionsTask->getCount($where);
            if ($today_count < $limit_everyday && $today_count > -1) {
                $this->writeApiLog($this->request_action, $this->uid, json_encode($req), json_encode($list_data), 10);
                sdk_return('', 10, '红包未领完');
            } else {
                //已获取金额
                $all_money = $this->m_CMMillionsTask->getAllMoneyGet($uid);
                //返回数据
                $list_data['packet_limit'] = $limit_everyday;
                $money = sprintf("%.2f", $all_money['all_money']);
                $list_data['money'] = $money;
                $this->writeApiLog($this->request_action, $this->uid, json_encode($req), json_encode($list_data), 1);
                sdk_return($list_data);
            }
        } else {
            $this->writeApiLog($this->request_action, $this->uid, json_encode($req), json_encode($list_data), 11);
            sdk_return('', 11, '请求错误');
        }
    }
/**********************************************************************************************************************/
    /**
     * @cc 百万红包规则H5页
     * @Author   89776730@qq.com
     * @DateTime 2018-09-06
     * @return   [type]        [description]
     */
    public function millionsRulesUrl ()
    {
        $req = $this->request_param;
        $uid = $this->uid;
        $list_data = [];
        if (!empty($req)) {
            $list_data = [
                'rules_url' => config('server_domain').'h5/rule.million_rule/home.html'
            ];
            $this->writeApiLog($this->request_action, $this->uid, json_encode($req), json_encode($list_data), 1);
            sdk_return($list_data);
        } else {
            $this->writeApiLog($this->request_action, $this->uid, json_encode($req), json_encode($list_data), 15);
            sdk_return('', 15, '请求错误');
        }
    }
/**********************************************************************************************************************/
    /**
     * @cc 好友帮拆页面
     * @Author   89776730@qq.com
     * @DateTime 2018-09-06
     * @return   [type]        [description]
     */
    public function millionsFriendShow ()
    {
        $req = $this->request_param;
        $uid = $this->uid;
        $list_data = [];
        if (!empty($req)) {
            //获取被拆人头像
            $unionid = $this->m_CMUserInfo->getUnionid($uid);//获取帮拆人unionid
            $unionid = $unionid['unionid'];
            $touid = $this->m_CMMillionsShare->getUidByUnionId($unionid);
            $touid = $touid['to_uid'];
            $list_data['to_avatar'] = get_user_avatar($touid, 2, 1);
            //被拆人昵称
            $one_nickname = $this->m_CMUserList->getNickName($touid);
            $to_nickname = $one_nickname['nickname'];
            $list_data['to_nickname'] = $to_nickname;
            //获取最高被拆金额
            $list_data['money'] = '100';
            //获取被拆人好友帮拆列表
//            $t_millions_friend = get_db_table_name('millions_friend');
//            $this->m_CMMillionsFriend->setTableName($t_millions_friend);
//            $arr_friend_list = $this->m_CMMillionsFriend->getFriendsListByUid($touid, $this->act_id);
//            if (empty($arr_friend_list)) {
//                $list_data['friend_list'] = [];
//            } else {
//                foreach ($arr_friend_list as $k => $v) {
//                    $where = null;
//                    $where['uid'] = $v['f_friend_id'];
//                    $avatar = get_user_avatar($v['f_friend_id'], 2);//好友头像
//                    $nick_name = $this->m_CMUserList->getInfoPro($where,'nickname', null);
//                    $nick_name = $nick_name['nickname'];
//                    $money = sprintf("%.2f", $v['f_money']);
//                    $list_data['friend_list'][] = [
//                        'avatar' => $avatar,
//                        'nickname' =>$nick_name,
//                        'money' => $money
//                    ];
//                }
//            }
            $this->writeApiLog($this->request_action, $this->uid, json_encode($req), json_encode($list_data), 1);
            sdk_return($list_data);
        } else {
            $this->writeApiLog($this->request_action, $this->uid, json_encode($req), json_encode($list_data), 16);
            sdk_return('', 16, '请求错误');
        }
    }
/**********************************************************************************************************************/
    /**
     * @cc 好友帮拆功能
     * @Author   89776730@qq.com
     * @DateTime 2018-09-10
     * @return   [type]        [description]
     */
    public function millionsFriendOpen ()
    {
        $req = $this->request_param;
        $uid = $this->uid;
        $list_data = [];
        $now_time = get_time();
        if (!empty($req)) {
            /******【领取新红包部分】******/
            //检查用户状态是否为 未领取红包状态（状态值没有小于5的 且大于5的小于3个）
            $t_tablename = get_db_table_name('millions_task', $uid);//获取表名
            $this->m_CMMillionsTask->setTableName($t_tablename);    //设置表名

            $where = null;
            if (!empty($uid)) $where[] = ['t_uid', '=', $uid];
            $where[] = ['status', '<', '5'];
            $num1 = $this->m_CMMillionsTask->getCount($where);
            $where = null;
            $date = date('Ymd', $now_time);
            $where[] = ['t_uid', '=', $uid];
            $where[] = ['status', '>', '5'];
            $where[] = ['t_day', '=', $date];
            $num2 = $this->m_CMMillionsTask->getCount($where);
            //获取每日领取红包上限
            $one_packet_limit = $this->m_CMMillionsActivity->getActivityPacketLimit($this->act_id);
            $packet_limit = $one_packet_limit['a_user_limit'];

            if (($num1 == 0) && ($num2 < $packet_limit)) {
                //获取红包过期时长
                $where = null;
                $where['id'] = $this->act_id;
                $where['status'] = 1;
                $field = 'a_expire_time';
                $arr_expire = $this->m_CMMillionsActivity->where($where)->field($field)->find();
                $expire = $arr_expire['a_expire_time'];     //过期时间时长
                //获取红包配置表配置
                $list_config = $this->m_CMMillionsConfig->getPacketConfig($this->act_id);
                //根据配置概率获取红包配置ID
                $radio = [];
                foreach ($list_config as $k => $v) {
                    $radio[$v['id']] = $v['c_radio'];
                }
                $new_radio = [];
                $end = 0;
                foreach ($radio as $kk => $vv) {
                    $start = $end;
                    $end = $vv + $end;
                    $new_radio[] = [
                        'no' => $kk,
                        'start' => $start,
                        'end' => $end
                    ] ;
                }
                $rand = mt_rand(1, 100);
                foreach ($new_radio as $kkk => $vvv) {
                    if ($rand > $vvv['start'] && $rand <= $vvv['end']) {
                        $packet_no = $vvv['no'];    //配置id
                        break;
                    }
                }

                //根据红包配置id 获取红包配置
                $one_config  = $this->m_CMMillionsConfig->getConfigById($packet_no, $this->act_id);
                $money_up    = $one_config['c_money_up'];
                $money_down  = $one_config['c_money_down'];
                $money_radio = $one_config['c_invite_value'];
                $config_id   = $one_config['id'];
                $need_count  = $one_config['c_invite_num'];
                $money_value = get_random_num($money_down, $money_up, 2); //红包总金额
                //我拆的金额
                $radio_list = explode('|', $money_radio);
                $my_radio = $radio_list[0];
                $money_my = $my_radio / 100 * $money_value;
                $money_my = round($money_my, 2);

                //插入任务记录
                $data = [
                    't_act_id' => $this->act_id,
                    't_config_id' => $config_id,
                    't_uid' => $uid,
                    't_day' => date('Ymd', $now_time),
                    't_money_value' => $money_value,
                    't_money_my' => $money_my,
                    't_money_ready' => $money_my,
                    't_money_friend' => 0,
                    't_need_count' => $need_count,
                    't_ready_count' => 0,
                    'addtime' => $now_time,
                    't_time_end' => $now_time + $expire,
                    'status' => 1
                ];
                $this->m_CMMillionsTask->save($data);
                //插入config表统计
                $this->m_CMMillionsConfig->updateTaskNum($config_id);
                //返回数据
                $money_my = sprintf("%.2f",$money_my);
                $money_value = sprintf("%.2f",$money_value);
                $list_data['ready_money'] = $money_my;              //已领金额
                $list_data['new_packet'] = $money_value;            //红包金额

                //埋点统计 发包个数加一
                $this->writeCountLog('7', 101);
            } else {
                sdk_return('', 0, '您已领取过');
            }


            /******【帮拆部分】******/
            //获取帮拆人unionid
            $arr_openid = $this->m_CMUserInfo->getUnionid($uid);
            if (empty($arr_openid)) {
                sdk_return('', '18', '未获取到帮拆人unionid');
            }
            $unionid = $arr_openid['unionid'];

            //获取被拆人uid
            $touid = $this->m_CMMillionsShare->getToUserId($unionid);
            if (empty($touid)) {
                sdk_return('', '19', '未获取到被拆人uid');
            }
            $touid = $touid['to_uid'];
            //获取被拆人昵称
            $where = null;
            $where['uid'] = $touid;
            $one_nickname = $this->m_CMUserList->getInfoPro($where, 'nickname');
            $list_data['to_name'] = $one_nickname['nickname'];


            //获取帮拆任务id
            $arr_taskid = $this->m_CMMillionsShare->getTaskId($unionid, $touid, $this->act_id);
            if (empty($arr_taskid)) {
                sdk_return('', '20', '帮拆状态错误');
            }
            $taskid = $arr_taskid['taskid'];
            //获取帮拆红包配置id
            $t_millions_task = get_db_table_name('millions_task', $touid);
            $this->m_CMMillionsTask->setTableName($t_millions_task);
            $arr_configid = $this->m_CMMillionsTask->getConfigByID($taskid);
            if (empty($arr_configid)) {
                sdk_return('', '21', '配置id获取失败');
            }
            $configid = $arr_configid['t_config_id'];
            //获取拆红包配置
            $arr_config = $this->m_CMMillionsConfig->getActivityConfig($configid);
            if (empty($arr_config)) {
                sdk_return('', '22', '配置获取失败');
            }

            //数据库锁
            $user_end_num = get_uid_end_num($taskid) + 10;
            $this->m_WLockThread->startTrans();//开启事务
            $where = null;
            $where['id'] = $user_end_num;
            $one_lock_thread = $this->m_WLockThread->lock(true)->where($where)->find();

            if ($one_lock_thread) {//加锁
                //查看是否帮拆过
                $t_millions_friend = get_db_table_name('millions_friend');
                $this->m_CMMillionsFriend->setTableName($t_millions_friend);
                $is_open = $this->m_CMMillionsFriend->isOpen($uid);
                if (!empty($is_open)) {
                    sdk_return('', '0', '您已经帮别人拆过红包了，每个新用户只能帮拆一次！');
                }
                $list_data['help_money'] = '0';
                $list_data['is_success'] = '0';
                //查看已经有几人帮拆与红包总金额
                $arr_task = $this->m_CMMillionsTask->getOpenNum($taskid);
                $ready_count = $arr_task['t_ready_count'];
                if ($arr_task['t_time_end'] >= $now_time) {
                    if ($arr_task['t_need_count'] > $arr_task['t_ready_count']) {
//                        $t_data = [
//                            't_ready_count' => $arr_task['t_need_count']
//                        ];
//                        $this->m_CMMillionsTask->updateInfo($taskid, $t_data);
//                        $list_data['help_money'] = '0';
//                        $list_data['is_success'] = '0';
                        //红包总金额
                        $money_value = $arr_task['t_money_value'];

                        //计算红包帮拆金额
                        if ($arr_task['t_ready_count'] == $arr_task['t_need_count'] - 1) {
                            //埋点统计 拆完个数加一
                            $this->writeCountLog('7', 102);

                            //埋点统计 拆完金额累加
                            $money_count = $money_value * 10000;//金额乘一万后存入，整型
                            $this->writeCountLog('7', 103, $money_count);

                            //配置表统计 此配置红包拆完数量加一(millions_config表c_all_task_success)
                            $this->m_CMMillionsConfig->updateConfigSuccessNum($configid);

                            $f_money = $arr_task['t_money_value'] - $arr_task['t_money_ready'];
                        } else {
                            $arr_money_value = explode('|', $arr_config['c_invite_value']);
                            $money_radio = $arr_money_value[$ready_count + 1];
                            $f_money = $money_radio / 100 * $money_value;
                        }
                        $f_money = sprintf("%.2f", $f_money);
                        //插入好友帮拆表数据
                        $f_data = [
                            'f_act_id' => $this->act_id,
                            'f_config_id' => $configid,
                            'f_task_id' => $taskid,
                            'f_uid' => $touid,
                            'f_friend_id' => $uid,
                            'f_money' => $f_money,
                            'addtime' => $now_time,
                        ];
                        $res0 = $this->m_CMMillionsFriend->insertInfo($f_data);
                        //更新任务表数据
                        $new_money = $arr_task['t_money_ready'] + $f_money;
                        $new_count = $arr_task['t_ready_count'] + 1;
                        $t_data = [
                            't_money_ready' => $new_money,
                            't_ready_count' => $new_count,
                        ];
                        $res1 = $this->m_CMMillionsTask->updateInfo($taskid, $t_data);
                        //更新红包配置表数据拆红包人数加一
                        $res2 = $this->m_CMMillionsConfig->updateOpenNum($configid);

                        //如果红包还未帮拆，帮拆完后状态由未帮拆改为正在拆
                        $res3 = true;
                        if ($arr_task['status'] == 1) {
                            $status_data = [
                                'status' => 3
                            ];
                            $res3 = $this->m_CMMillionsTask->updateInfo($taskid, $status_data);
                        }
                        //如果此次帮拆正好拆完此红包就把红包状态改为已完成
                        $res4 = true;
                        if ($arr_task['t_need_count'] == $new_count) {
                            $s_data = [
                                'status' => 4
                            ];
                            $res4 = $this->m_CMMillionsTask->updateInfo($taskid, $s_data);
                        }
                        //埋点统计 帮拆数加一
                        $this->writeCountLog('7', '108');

                        if ($res0 && $res1 && $res2 && $res3 && $res4) {
                            //提交事务
                            $this->m_WLockThread->commit();
                            //返回帮拆金额
                            $f_money = sprintf("%.2f", $f_money);
                            $list_data['help_money'] = $f_money;
                            $list_data['is_success'] = '1';
                        } else {
                            //回滚事务
                            $this->m_WLockThread->rollback();
                        }
                    }
                }
            }
            sdk_return($list_data);
        } else {
            sdk_return('', 22, '请求错误');
        }
    }
/**********************************************************************************************************************/
    /**
     * @cc 广告控制
     * @Author   89776730@qq.com
     * @DateTime 2018-09-19
     * @return   [type]        [description]
     */
    public function redPacketAdControl ()
    {
        $req = $this->request_param;
        $uid = $this->uid;
        $list_data = [];
        $type = $req['type'];
        $mark = $req['mark'];
        if (!empty($req)) {
            $arr_img_path = [
                [
                    'img_path' => 'http://pic38.nipic.com/20140225/3554136_195849520358_2.jpg',
                    'img_url'  => 'https://www.baidu.com'
                ],
                [
                    'img_path' => 'http://pic38.nipic.com/20140225/3554136_195849520358_2.jpg',
                    'img_url'  => 'https://www.baidu.com'
                ],
                [
                    'img_path' => 'http://pic38.nipic.com/20140225/3554136_195849520358_2.jpg',
                    'img_url'  => 'https://www.baidu.com'
                ]
            ];
            $list_data = [];
            switch ($type) {
                case '1'://开屏
                    $return_type = 1;//1腾讯 2自有
                    if ($return_type == 1) {
                        $list_data['ad_type'] = $return_type;
                        sdk_return($list_data);
                    } else {
                        $list_data['ad_type'] = $return_type;
                        $n = mt_rand(1, count($arr_img_path));
                        $list_data['img'] = $arr_img_path[$n]['img_path'];
                        $list_data['url'] = $arr_img_path[$n]['img_url'];
                        sdk_return($list_data);
                    }
                    break;
                case '2'://插屏
                    $return_type = 1;//1腾讯 2自有
                    if ($return_type == 1) {
                        $list_data['ad_type'] = $return_type;
                        sdk_return($list_data);
                    } else {
                        $list_data['ad_type'] = $return_type;
                        $n = mt_rand(1, count($arr_img_path));
                        $list_data['img'] = $arr_img_path[$n]['img_path'];
                        $list_data['url'] = $arr_img_path[$n]['img_url'];
                        sdk_return($list_data);
                    }
                    break;
                case '3'://原生
                    $return_type = 1;//1腾讯 2自有
                    if ($return_type == 1) {
                        $list_data['ad_type'] = $return_type;
                        sdk_return($list_data);
                    } else {
                        $list_data['ad_type'] = $return_type;
                        $n = mt_rand(1, count($arr_img_path));
                        $list_data['img'] = $arr_img_path[$n]['img_path'];
                        $list_data['url'] = $arr_img_path[$n]['img_url'];
                        sdk_return($list_data);
                    }
                    break;
            }
        } else {
            sdk_return('', 22, '请求错误');
        }
    }
/**********************************************************************************************************************/
    /*
     * 转入红包
     * */
    public function millionsGetCash ()
    {
        $req = $this->request_param;
        $uid = $this->uid;
        $list_data = [];
        $now_time = time();
        if (!empty($req)) {
            $c_RedPacket = new RedPacket();

            $t_millions_task = get_db_table_name('millions_task', $uid);
            $this->m_CMMillionsTask->setTableName($t_millions_task);
            //获取提现总金额
            $one_all_money = $this->m_CMMillionsTask->getCash($uid);
            $all_get_money = $one_all_money['t_money_value'];
            $config_id = $one_all_money['t_config_id'];
            $r = $c_RedPacket->getUserStockPrice($uid, '7', $config_id, $all_get_money);
            if (!empty($r)) {
                //新增股数
                //$add_stock = format_num($all_get_money * 0.9 / $r['price']);
                $add_stock = format_num($r['num_a']);
                //将任务状态转为已提现
                $t_millions_task = get_db_table_name('millions_task', $uid);
                $this->m_CMMillionsTask->setTableName($t_millions_task);
                $one_taskid = $this->m_CMMillionsTask->getIsExistUnderway($uid);
                $taskid = $one_taskid[0]['id'];
                $data = [
                    'status' => 6,
                    'endtime' => $now_time
                ];
                $this->m_CMMillionsTask->updateInfo($taskid, $data);

                //埋点统计 转入红包金额
                $this->writeCountLog('7', '106', $all_get_money);

                $list_data['add_stock'] = $add_stock;
                $list_data['is_success'] = 1;
                $this->writeApiLog($this->request_action, $this->uid, json_encode($req), json_encode($list_data), 1);
                sdk_return($list_data);
            } else {
                $list_data['add_stock'] = 0;
                $list_data['is_success'] = 0;
                $this->writeApiLog($this->request_action, $this->uid, json_encode($req), json_encode($list_data), 1);
                sdk_return($list_data);
            }
        } else {
            $this->writeApiLog($this->request_action, $this->uid, json_encode($req), json_encode($list_data), 14);
            sdk_return('', 14, '请求错误');
        }
    }
/**********************************************************************************************************************/
    /*
     * 帮拆并发测试接口
     * @Author   89776730@qq.com
     * @DateTime 2018-09-27
     * */
    public function helpOpenHighConcurrencyTest ()
    {
        $req = $this->request_param;
        $uid = $this->uid;
        $list_data = [];
        $now_time = get_time();
        if (!empty($req)) {
            /*【领取新红包部分】*/
            //检查用户状态是否为 未领取红包状态（状态值没有小于5的 且大于5的小于3个）
            $t_tablename = get_db_table_name('millions_task', $uid);//获取表名
            $this->m_CMMillionsTask->setTableName($t_tablename);    //设置表名

            $where = null;
            if (!empty($uid)) $where['t_uid'] = $uid;
            $where[] = ['status', '<', 5];
            $num1 = $this->m_CMMillionsTask->getCount($where);
            $where[] = ['status', '>', 5];
            $num2 = $this->m_CMMillionsTask->getCount($where);
            //获取每日领取红包上限
            $one_packet_limit = $this->m_CMMillionsActivity->getActivityPacketLimit($this->act_id);
            $packet_limit = $one_packet_limit['a_user_limit'];

            if (($num1 == 0) && ($num2 < $packet_limit)) {
                //获取红包过期时长
                $where = null;
                $where['id'] = $this->act_id;
                $where['status'] = 1;
                $field = 'a_expire_time';
                $arr_expire = $this->m_CMMillionsActivity->where($where)->field($field)->find();
                $expire = $arr_expire['a_expire_time'];     //过期时间时长
                //获取红包配置表配置
                $list_config = $this->m_CMMillionsConfig->getPacketConfig($this->act_id);
                //根据配置概率获取红包配置ID
                foreach ($list_config as $k => $v) {
                    $radio[$v['id']] = $v['c_radio'];
                }
                $new_radio = [];
                $end = 0;
                foreach ($radio as $kk => $vv) {
                    $start = $end;
                    $end = $vv + $end;
                    $new_radio[] = [
                        'no' => $kk,
                        'start' => $start,
                        'end' => $end
                    ] ;
                }
                $rand = mt_rand(0, 100);
                foreach ($new_radio as $kkk => $vvv) {
                    if ($rand > $vvv['start'] && $rand <= $vvv['end']) {
                        $packet_no = $vvv['no'];    //配置id
                        break;
                    }
                }

                //根据红包配置id 获取红包配置
                $one_config  = $this->m_CMMillionsConfig->getConfigById($packet_no, $this->act_id);
                $money_up    = $one_config['c_money_up'];
                $money_down  = $one_config['c_money_down'];
                $money_radio = $one_config['c_invite_value'];
                $config_id   = $one_config['id'];
                $need_count  = $one_config['c_invite_num'];
                $money_value = mt_rand($money_down, $money_up); //红包总金额
                //我拆的金额
                $radio_list = explode('|', $money_radio);
                $my_radio = $radio_list[0];
                $money_my = $my_radio / 100 * $money_value;
                $money_my = round($money_my, 2);

                //插入任务记录
                $data = [
                    't_act_id' => $this->act_id,
                    't_config_id' => $config_id,
                    't_uid' => $uid,
                    't_day' => date('Ymd', $now_time),
                    't_money_value' => $money_value,
                    't_money_my' => $money_my,
                    't_money_ready' => $money_my,
                    't_money_friend' => 0,
                    't_need_count' => $need_count,
                    't_ready_count' => 0,
                    'addtime' => $now_time,
                    't_time_end' => $now_time + $expire,
                    'status' => 1
                ];
                $this->m_CMMillionsTask->insertInfo($data);
                //插入config表统计
                $this->m_CMMillionsConfig->updateTaskNum($config_id);
                //返回数据
                $money_my = sprintf("%.2f",$money_my);
                $money_value = sprintf("%.2f",$money_value);
                $list_data['ready_money'] = $money_my;              //已领金额
                $list_data['new_packet'] = $money_value;            //红包金额
            } else {
                sdk_return('', 21, '您已领取过');
            }


            /*【帮拆部分】*/
            //获取被拆人uid
            $touid = 100324;
            $t_tablename = get_db_table_name('millions_task', $touid);//获取表名
            $this->m_CMMillionsTask->setTableName($t_tablename);    //设置表名

            //获取被拆人昵称
            $list_data['to_name'] = '我有的是红包';
            //获取帮拆任务id
            $taskid = 167;
            //获取帮拆红包配置id
            $configid = 3;
            //获取拆红包配置
            $arr_config = $this->m_CMMillionsConfig->getActivityConfig($configid);
            if (empty($arr_config)) {
                sdk_return('', '22', '配置获取失败');
            }

            $user_end_num = get_uid_end_num($taskid);
            $this->m_WLockThread->startTrans();//开启事务
            $where = null;
            $where['id'] = empty($user_end_num) ? 10 : $user_end_num;
            $one_lock_thread = $this->m_WLockThread->lock(true)->where($where)->find();//加锁查询

            if ($one_lock_thread) {//加锁
                //查看是否帮拆过
                $t_millions_friend = get_db_table_name('millions_friend');
                $this->m_CMMillionsFriend->setTableName($t_millions_friend);
                $is_open = $this->m_CMMillionsFriend->isOpen($uid);
                if (!empty($is_open)) {
                    sdk_return('', '0', '您已经帮别人拆过红包了，每个新用户只能帮拆一次！');
                }

                $list_data['help_money'] = '0';
                $list_data['is_success'] = '0';
                //查看已经有几人帮拆与红包总金额
                $arr_task = $this->m_CMMillionsTask->getOpenNum($taskid);
                $ready_count = $arr_task['t_ready_count'];
                if ($arr_task['t_time_end'] >= $now_time) {

                    if ($arr_task['t_need_count'] > $arr_task['t_ready_count']) {
                        //$t_data = ['t_ready_count' => $arr_task['t_need_count']];
                        //$this->m_CMMillionsTask->updateInfo($taskid, $t_data);
                    //} else {
                        //红包总金额
                        $money_value = $arr_task['t_money_value'];

                        //计算红包帮拆金额
                        if ($arr_task['t_ready_count'] == $arr_task['t_need_count'] - 1) {
                            $f_money = $arr_task['t_money_value'] - $arr_task['t_money_ready'];
                        } else {
                            $arr_money_value = explode('|', $arr_config['c_invite_value']);
                            $money_radio = $arr_money_value[$ready_count + 1];
                            $f_money = $money_radio / 100 * $money_value;
                        }
                        $f_money = round($f_money, 2);
                        //插入好友帮拆表数据
                        $f_data = [
                            'f_act_id' => $this->act_id,
                            'f_config_id' => $configid,
                            'f_task_id' => $taskid,
                            'f_uid' => $touid,
                            'f_friend_id' => $uid,
                            'f_money' => $f_money,
                            'addtime' => $now_time,
                        ];
                        $res0 = $this->m_CMMillionsFriend->insertInfo($f_data);
                        //更新任务表数据
                        $new_money = $arr_task['t_money_ready'] + $f_money;
                        $new_count = $arr_task['t_ready_count'] + 1;
                        $t_data = [
                            't_money_ready' => $new_money,
                            't_ready_count' => $new_count,
                        ];
                        $res1 = $this->m_CMMillionsTask->updateInfo($taskid, $t_data);
                        //更新红包配置表数据拆红包人数加一
                        $res2 = $this->m_CMMillionsConfig->updateOpenNum($configid);
                        //如果拆完此红包就把红包状态改为已完成
                        $res3 = true;
                        if ($arr_task['t_need_count'] == $new_count) {
                            $s_data = [
                                'status' => 4
                            ];
                            $res3 = $this->m_CMMillionsTask->updateInfo($taskid, $s_data);
                        }
                        //如果红包还未帮拆，帮拆完后状态由未帮拆改为正在拆
                        $res4 = true;
                        if ($arr_task['status'] == 1) {
                            $status_data = [
                                'status' => 3
                            ];
                            $res4 = $this->m_CMMillionsTask->updateInfo($taskid, $status_data);
                        }
                        if ($res0 && $res1 && $res2 && $res3 && $res4) {
                            //返回帮拆金额
                            $f_money = sprintf("%.2f", $f_money);
                            $list_data['help_money'] = $f_money;
                            $list_data['is_success'] = '1';
                            //提交事务
                            $this->m_WLockThread->commit();
                        } else {
                            //回滚事务
                            $this->m_WLockThread->rollback();
                        }
                    }
                }
            }
            sdk_return($list_data);
        } else {
            sdk_return('', 22, '请求错误');
        }
    }
/**********************************************************************************************************************/
    /*
     * 百万红包引导红包按钮
     * */
    public function addMillionsGuide ()
    {
        $req = $this->request_param;
        //$uid = $this->uid;
        $list_data = [];
        $now_time = time();
        if (!empty($req)) {
            $this->m_MillionsList = new MillionsList();
            $data = [
                'addtime' => $now_time,
                'status' => 1
            ];
            $this->m_MillionsList->insertInfo($data);
            $this->writeApiLog($this->request_action, $this->uid, json_encode($req), json_encode($list_data), 1);
            sdk_return($list_data);
        } else {
            $this->writeApiLog($this->request_action, $this->uid, json_encode($req), json_encode($list_data), 23);
            sdk_return('', 23, '请求错误');
        }
    }
}
