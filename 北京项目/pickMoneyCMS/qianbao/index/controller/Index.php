<?php

namespace app\index\controller;

use app\api\model\MillionsShare;
use DES\QbDES;
//use DES\CryptDes;
use think\App;
use think\Controller;
use think\facade\Request;
//use think\cache\Driver\Memcache;
//use app\index\model\UserList;
//use app\index\model\1UserList;
use app\index\model\MUserList;
use think\Exception;
use think\db;

use app\api\model\WLockThread;
use app\api\model\WLockGoods;
use app\api\model\WLockOrder;

//use app\api\controller\game\GameLobby;
use app\api\controller\RedPacket;

use app\index\model\UserList;

class Index extends Controller
{
    protected $request;
    /**
     * @cc 调用不存在方法统一处理
     * @param void
     * @return void
     *
     * @author seaboyer@163.com
     * @date 2018-09-11
     * @version 1.0
     */
    public function __construct()//App $app = null)
    {
        //parent::__construct($app);
        $this->request = Request::instance()->param();
    }

    public function _empty()
    {
        return "<div style='text-align: center' align='center'>error</div>";
    }

    public function get_game_redpacket()
    {
        $game_redpacket_arr = array(array('id'=>1,'red_weight'=>30),array('id'=>2,'red_weight'=>70));//$this->m_GameRedpacketList->getRedpacketList();      // 获取设定的红包
        print_r($game_redpacket_arr);
        echo "<br>";
        $end = 0;
        $arr_list = [];
        foreach ($game_redpacket_arr as $one) {
            $arr = null;
            //$arr[$one['id']] = $one['red_weight'];
            $start = $end;
            $end = $one['red_weight'] + $end;
            $arr['id'] = $one['id'];
            $arr['start'] = $start;
            $arr['end'] = $end;
            $arr_list[] = $arr;
        }
        print_r($arr_list);
        echo "<br>";
        $rid = $this->get_rand($arr_list, $end);                   //根据概率获取奖项id
        // $game_redpacket_id = $game_redpacket_arr[$rid-1]['id'];             //获取被选中的孩子们
        // return $game_redpacket_arr[$rid-1];
        echo "rid=".$rid."<br>";
        $return_arr = null;
        foreach ($game_redpacket_arr as $one) {
            if ($one['id'] == $rid) {
                $return_arr = $one;
                break;
            }
        }
        //return $return_arr;
        print_r($return_arr);
    }

    public function get_rand($res_arr, $end = 0)
    {
        //概率数组循环
        $rand = mt_rand(1, $end);
        echo "rand=".$rand."<br>";
        print_r($res_arr);
        echo "<br>";
        foreach ($res_arr as $one) {
            if ($rand > $one['start'] && $rand <= $one['end']) {
                $result = $one['id'];
                break;
            }
        }

        return $result;
        }

    public function test_zcb(){

    }


    /**
     * 将十进制数字转换为二十六进制字母串
     *  12000000 = BAGTMM 开始
     * 308000000 = ZXZXHW 结束(约2.9亿)
     * @author seaboyer 20181022
     */
    function num2alpha($intNum, $isLower = false)
    {
        $num26 = base_convert($intNum, 10, 26);
        $add_code = $isLower ? 49 : 17;
        $result = '';
        for ($i = 0; $i < strlen($num26); $i++) {
            $code = ord($num26{$i});
            if ($code < 58) {
                $result .= chr($code + $add_code);
            } else {
                $result .= chr($code + $add_code - 39);
            }
        }
        return $result;
    }

    /**
     * 将二十六进制字母串转换为十进制数字
     * AAAAAA = 0
     * BAGTMM = BAGTMM
     * ZXZXHW = 308000000
     * @author  seaboyer 20181022
     */
    function alpha2num($strAlpha)
    {
        if (ord($strAlpha{0}) > 90) {
            $startCode = 97;
            $reduceCode = 10;
        } else {
            $startCode = 65;
            $reduceCode = -22;
        }
        $num26 = '';
        for ($i = 0; $i<strlen($strAlpha); $i++) {
            $code = ord($strAlpha{$i});
            if ($code < $startCode + 10) {
                $num26 .= $code - $startCode;
            } else {
                $num26 .= chr($code - $reduceCode);
            }
        }
        return (int)base_convert($num26, 26, 10);
    }

    function get_invite_code($id)
    {
        $arr_char = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $i_1 = mt_rand(0,25);
        $s_1 = $arr_char[$i_1];
        $i_2 = mt_rand(0,25);
        $s_2 = $arr_char[$i_2];
        $id_fix = $id + 12000000;
        $s_id = $this->num2alpha($id_fix);
        $s_all = $s_1 .$s_id[0].$s_id[1].$s_2;
        for ($j = 2; $j < 6; $j++) {
            $s_all .=  $s_id[$j];
        }
        return $s_all;
    }

    function check_invite_code($code)
    {
        $id = 0;
        if(strlen($code) == 8){
            $code_fix = '';
            for ($j = 0; $j < 8; $j++) {
                if ($j > 0 && $j <> 3) {
                    $code_fix .= $code[$j];
                }
            }

            $id_fix = $this->alpha2num($code_fix);
            $id = $id_fix - 12000000;
        }
        return $id;
    }



    public function test_call_back(){
        //$className = new Index();   //可用1   'Index';   //类名
        $className = $this;         //可用2
        $funName = "fnCallBack";    //类的方法名
        $params = array( '1' , '101', '2', '3', '1234567890');//传给参数的值
        call_user_func_array(array($className,$funName), $params);
    }

    public function fnCallBack($c_type, $c_id, $increment, $uid, $time)//$c_type, $c_id, $increment = 1, $uid
    {
        echo 'c_type:'.$c_type;
        echo "<br />\n";
        echo 'c_id:'.$c_id;
        echo "<br />\n";
        echo 'increment:'.$increment;
        echo "<br />\n";
        echo 'uid:'.$uid;
        echo "<br />\n";
        echo 'time:'.$time;
    }

    public function db_test()
    {
        $m_MillionsShare = new MillionsShare();
        $s_title = 'a';
        $i_day1 = 1;
        $i_day2 = 9;
        $where[] =  ['addtime','between', [$i_day1,$i_day2]];
        //$where[] = ['id',['gt', $i_day1], ['lt',$i_day2]];
        //$where[] = ['openid','like', '%'.$s_title.'%'];
        //$whereor[] = ['act_id','=', '1'];//,['con_id','=','1'],'or'
        //$whereor[] = ['con_id','=', '1'];
        $where[] = ['act_id|status', '>=','1'];
        //$m_MillionsShare->getAllLists($where,$whereor);
        //$m_MillionsShare->getAllList($where);
        //echo $m_MillionsShare->getLastSql();
//       $order=M('order_info');
//    $where['order_status']=5;
//    $where['shipping_status']=2;
//    $map['_complex'] = $where;
//    $map['order_status']=6;
//    $map['_logic'] = 'or';  
//    $final['_complex'] = $map;
//    $final['user_id']=1;
//   然后直接查询就可以了： $order->where($final)->select();
//      echo $order->where($final)->fetchSql(true)->select();

        $map[] = ['addtime','=','0'];
        $map2 = [['act_id','<=',30],['status','>=',50]];

        DB::table('b_millions_share')->where($map)->where(
            function ($q) use($map2) {
                $q->whereOr($map2);
            }
        )->select();
        echo DB::table('b_millions_share')->getLastSql();

    }

    public function test_db_lock_tp(){
        $uid = $this->request->param('uid',0);

        $m_WLockThread = new WLockThread();
        $m_WLockThread->startTrans();//开启事务
        $where = null;
        $where['id'] = '1';//待拆红包id取余数(结果是0用10)
        $one_lock_thread = $m_WLockThread->lock(true)->where($where)->find();//加锁查询
        if ($one_lock_thread) {
            //start执行你想进行的操作, 最后返回操作结果res
            $res = false;
            $m_WLockGoods = new WLockGoods();
            $m_WLockOrder = new WLockOrder();
            $where = null;
            $where['id'] = 1;
            $one_lock_goods = $m_WLockGoods->getInfo($where);
            if ($one_lock_goods['l_value'] > 0) {
                echo $one_lock_goods['l_value'];
                $m_WLockGoods->incField($where,'l_value',1,0);
                $data = null;
                $data['l_type'] = 1;
                $data['l_key'] = $uid;
                $data['l_value'] = $one_lock_goods['l_value'];
                $data['l_time'] = get_time();
                $res = $m_WLockOrder->insertInfo($data);
            } else {
                echo 0;
            }
            //end执行你想进行的操作, 最后返回操作结果res

            if ($res) {
                $m_WLockThread->commit();//事务提交
            } else {
                $m_WLockThread->rollback();//事务回滚
            }
        }

//        $m_WLockOrder1 = new WLockOrder();
//        $data0 = null;
//        $data0['l_type'] = 2;
//        $data0['l_key'] = $uid;
//        $res = $m_WLockOrder1->insertInfo($data0);

    }

    public function test_db_lock_php(){
        /*
        $pdo = new PDO('mysql:host=127.0.0.1;port=3306; dbname=test','root','123456');
        $pdo->beginTransaction();//开启事务
        $sql = "select l_value from b_w_lockfile where id = 1 for UPDATE ";//利用for update 开启行锁
        $res = $pdo->query($sql)->fetch();
        $l_value = $res['l_value'];

        if ($l_value > 0) {
            //$sql ="insert into `order` VALUES (null,$number)";
            //$order_id = $pdo->query($sql);
            $order_id = 1;
            if($order_id) {
                $sql = "update b_w_lockfile set `l_value`=`l_value` - 1 WHERE id = 1";
                if ($pdo->query($sql)) {
                    $pdo->commit();//提交事务
                } else {
                    $pdo->rollBack();//回滚
                }
            } else {
                $pdo->rollBack();//回滚
            }
        }
        */
    }

// 过滤掉emoji表情
    function filter_emoji($str)
    {
        $str = preg_replace_callback(
            '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str);

        return $str;
    }


    function get_str_width($str)
    {
        $str1 = $str;
        //1.emoji宽度
        $width1 = strlen($str1);
//        $str2 = preg_replace_callback(
//            '/./u',
//            function (array $match) {
//                return strlen($match[0]) >= 4 ? '' : $match[0];
//            },
//            $str1);
        $str2 = preg_replace("#(\\\ud[0-9a-f]{3})#i", "", $str1);
        $width_2 = strlen($str2);
        $width2 = 0;
        $width12 = 0;
        if ($width1 > $width_2) {
            $width2 = $width1 - $width_2;
            $width12 = round($width2 / 4) * 2;
        }


        //$emoji = "/[\u010000-\u10FFFF]/g";  // 4字节utf-16 = emoji
        //return preg_match($emoji, $text);


        //2.汉字宽度
        $width3 = 0;
        $width13 = 0;
        $preg = "/[\x{4e00}-\x{9fa5}]+/u";
        if (preg_match_all($preg, $str1, $matches)) {
            $str_han_zi = implode('', $matches[0]);
            //echo $str_han_zi."<br>";
            $width3 = strlen($str_han_zi);
            //echo $width3."<br>";
            $width13 = round($width3 * 2 / 3);
        }

        //3.中文标点字符的宽度
        //匹配这些中文标点符号 。 ？ ！ ， 、 ； ： “ ” ‘ ' （ ） 《 》 〈 〉 【 】 『 』 「 」 ﹃ ﹄ 〔 〕 … — ～ ﹏ ￥
        //$preg_pattern = "/[\u3002|\uff1f|\uff01|\uff0c|\u3001|\uff1b|\uff1a|\u201c|\u201d|\u2018|\u2019|\uff08|\uff09|\u300a|\u300b|\u3008|\u3009|\u3010|\u3011|\u300e|\u300f|\u300c|\u300d|\ufe43|\ufe44|\u3014|\u3015|\u2026|\u2014|\uff5e|\ufe4f|\uffe5]/";

        $regEx = '/[\p{P}]+/u';
        $preg_str = preg_replace($regEx,'', $str1);
        //echo $preg_str."<br>";
        $width40 = strlen($preg_str);
        $width4 = $width1 - $width40;
        $width14 = round($width4 * 2 / 3);

        //4.普通字符宽度
        $width11 = $width1 - $width2 - $width3 - $width4;

        //5.汇总整个字符宽度
        $str_width = $width14 + $width13 + $width12 + $width11;
        return $str_width;
    }

    public function index()
    {
//        $introduce_body = array(
//            array("body" => '1、新用户可通过分享邀请好友扫描二维码下载登录抢红包。'),
//            array("body" => '2、每邀请成功一个好友，红包领取范围扩大100m，可累计，最多50km。活动期间，每邀请一个好友，平台奖励随机专属红包，规则和开红包一样。'),
//            array("body" => '3、被邀请者成为新用户后，每开一个红包，邀请者都会随机得到一份红包奖励。')
//        );
//        var_dump($introduce_body);
        echo $_SERVER['DOCUMENT_ROOT']."<br>";
        //$search_type = 'AND d.ad_type in (1,6,7)';
        //$table_user_list = 'b_1_user_list';
        //echo "SELECT a.id,a.uid,a.money,a.num,a.from_uid,a.ad_id,b.nickname,c.nickname as from_nickname,a.ad_type,a.type,d.ad_img1,d.ad_text,a.addtime FROM b_1_my_ad_m0809_u2 as a left join {$table_user_list} as b on a.uid = b.uid left join {$table_user_list} as c on a.from_uid = c.uid left join b_1_ad as d on a.ad_id = d.id WHERE a.uid = 100082 {$search_type} ORDER BY id  DESC LIMIT 1";
        //$r = fopen($_SERVER['DOCUMENT_ROOT'].'/lockfile/r_0.txt', "r");
        //fwrite($r, '1');
        //var_dump($r);
        //fclose($r);
        //$c_RedPacket = new RedPacket();
        //$a = 0.03744;
        //$b = $a/9 + $a;
        //$c_RedPacket->getUserStockPriceTest(100001,6,6,$b);
//        $arr1 = ['price' => '0.02662658', 'num' => '838933.0792'];
//        //$memcache = get_memcache();
//        //$r = $memcache->set();
//        var_dump($arr1);
//        echo "<br>";
//        $str_serialize = serialize($arr1);
//        var_dump($str_serialize);
//        echo "<br>";
//        $arr2 = unserialize($str_serialize);
//        var_dump($arr2);
//        echo "<br>";
/*
        for($i=12000000;$i<12000010;$i++) {
            echo $this->num2alpha($i) ."<br>";
        }
//        echo "------------------------------------<br>";
//        for($i=15000000;$i<15000010;$i++) {
//            echo $this->num2alpha($i) ."<br>";
//        }
//        echo "------------------------------------<br>";
//        for($i=305000000;$i<305000010;$i++) {
//            echo $this->num2alpha($i) ."<br>";
//        }
        echo "------------------------------------<br>";
        for($i=308000000;$i<308000010;$i++) {
            echo $this->num2alpha($i) ."<br>";
        }

        echo $this->alpha2num('A') ."<br>";
        echo $this->alpha2num('ZZZZZ') ."<br>";
        $a = 'ZZZZZ';
        $a++;
        echo $a."<br>";
        echo $this->alpha2num('AAAAAA') ."<br>";
        echo $this->alpha2num('AAAAAB') ."<br>";
        echo $this->alpha2num('ZZZZZZ') ."<br>";
        echo $this->alpha2num('BAGTMM') ."<br>";
        echo $this->alpha2num('ZXZXHW') ."<br>";
        echo "------------------------------------<br>";
        echo  $this->get_invite_code(1)."<br>";
        echo $this->check_invite_code($this->get_invite_code(1))."<br>";
        echo  $this->get_invite_code(100)."<br>";
        echo $this->check_invite_code($this->get_invite_code(100))."<br>";
        echo  $this->get_invite_code(1000)."<br>";
        echo $this->check_invite_code($this->get_invite_code(1000))."<br>";
        echo  $this->get_invite_code(10000)."<br>";
        echo $this->check_invite_code($this->get_invite_code(10000))."<br>";
        echo "------------------------------------<br>";
        for($k=100;$k<110;$k++){
            echo  $this->get_invite_code($k)."<br>";
        }
        */
        echo "123456=".$this->get_str_width('123456')."<br>";
        echo "abcdef=".$this->get_str_width('abcdef')."<br>";
        echo "12中文5汉字6=".$this->get_str_width('12中文5汉字6')."<br>";
        echo "12中（5汉！6=".$this->get_str_width('12中（5汉！6')."<br>";
    }

    public function redis_list_bath()
    {
        $redis = get_redis_pro();
        if (!$redis->lLen('list_test_1')) {
            for ($i=1; $i<=10; $i++) {
                $redis->rPush('list_test_1', $i);
            }
        }
        print_r($redis->lRange('list_test_1', 0, -1));
	}

    public function redis_list_init()
    {
        $redis = get_redis_pro();
        if ($redis->lLen('list_test_2') <= 0) {
            for($i=1; $i<=10; $i++){
                $redis->rPush('list_test_2', $i);
            }
            $redis->set('list_test_user',null);
        }
        print_r($redis->lRange('list_test_2', 0, -1));
    }

    public function redis_list_add()
    {
        $request_value = Request::instance()->param();
        $uid = isset($request_value['uid']) ? $request_value['uid'] : 0;
        $redis = get_redis_pro();

        try{
            $redis_value = $redis->lPop('list_test_2');
            echo $redis_value." : ";
            if(!empty($redis_str)){
                $r_add = $redis->sAdd('set_test_3', $redis_value.':'.$uid);
                if(!empty($r_add)){
                    $redis->rPush('list_test_2',$redis_value);
                }
                echo $r_add." <br>";
            }
        }catch(Exception $e) {
            echo $e->getMessage();
        }

	}

    public function redis_list_show()
    {
        $redis_str = null;
        $redis = get_redis_pro();
        //var_dump($redis->get('list_test_user'));
        $r2 = $redis->sMembers('set_test_3');
        print_r($r2);
    }

    public function redis_list_clear()
    {
        $redis = get_redis_pro();
        $r2 = $redis->sMembers('set_test_3');
        foreach ($r2 as $one){
            $redis->sRem('set_test_3', $one);
        }

    }

    public function redis_set_bath()
    {
        $redis = get_redis_pro();
        $r = $redis->sCard('set_test_1');
        if(empty($r)){
            for ($i=1; $i<=100; $i++) {
                $j = mt_rand(1,10);
                $rr = $redis->sAdd('set_test_1', "A".$j); /* TRUE, 'key1' => {'member1'} */
                echo "A".$j." = ".$rr."<br>";
            }
        }
        $r2 = $redis->sMembers('set_test_1');
        print_r($r2);
        //$redis->sAdd('key1', 'member2'); /* TRUE, 'key1' => {'member1', 'member2'}*/
        //$redis->sAdd('key1', 'member2'); /* FALSE, 'key1' => {'member1', 'member2'}*/
    }

    public function redis_set_add()
    {
        $redis = get_redis_pro();
        $request_value = Request::instance()->param();
        $uid = $request_value['uid'];
        $r = $redis->sCard('set_test_2');
        if ($r <= 10) {
            $rr = $redis->sAdd('set_test_2', $uid); /* TRUE, 'key1' => {'member1'} */
        }
        //$r2 = $redis->sMembers('set_test_1');
        //print_r($r2);
        //$redis->sAdd('key1', 'member2'); /* TRUE, 'key1' => {'member1', 'member2'}*/
        //$redis->sAdd('key1', 'member2'); /* FALSE, 'key1' => {'member1', 'member2'}*/
    }

    public function redis_set_show()
    {
        $redis = get_redis_pro();
        $r2 = $redis->sMembers('set_test_2');
        print_r($r2);
    }

    public function redis_set_clear()
    {
        $redis = get_redis_pro();
        $r2 = $redis->sMembers('set_test_2');
        foreach ($r2 as $one) {
            $redis->sRem('set_test_2', $one);
        }

    }

    public function redis_set_read()
    {
        $redis = get_redis_pro();
        for ($i=1; $i<=10; $i++) {
            $redis->sRem('set_test_1', "A".$i);
        }
        $r2 = $redis->sMembers('set_test_1');
        print_r($r2);
    }

    public function index7()
    {
        echo __CLASS__;
        echo "<br>";
        echo __METHOD__;
        echo "<br>";
        echo __FILE__;
        echo "<br>";
        echo __LINE__;
        echo "<br>";
        echo __FUNCTION__;
        echo "<br>";
        echo PHP_VERSION;
        echo "<br>";
        echo PHP_OS;

        echo "<br>";
        echo "<br>";
        echo $this->format_stock_price_num('0.02179887654321', 4);
        echo "<br>";
        echo $this->format_price_num(2.3562 / 5.7979);
        echo "<br>";
        echo $this->format_price(6789.123456789123);
        echo "<br>";
        /*
                $m_UserList = new UserList();
                $where = array();
                $where['uid'] = 1;
                $list_userlist = $m_UserList->getUserList($where);
                var_dump($list_userlist);

                $m_UserList = new 1UserList();
                $where = array();
                $where['uid'] = 1;
                $list_userlist = $m_UserList->getUserList($where);
                var_dump($list_userlist);

                $m_MUserList = new MUserList();
                $where = array();
                $where['uid'] = 1;
                $list_userlist = $m_MUserList->getUserList($where);
                var_dump($list_userlist);

                $arr  = array('a' => 3, 'b' => 5, 'c' => 7);
                foreach ($arr as $key => &$val) {
                    //
                }
                print_r($arr);
                echo "<br>";

                foreach($arr as $key => $val) {
                    //print_r($arr);
                    //echo "<br>";
                }
                print_r($arr);
                echo "<br>";
        */

        //$user_list = db(1,"mysql://root:root@127.0.0.1/qianbao_fix")->table('b_1_user_list')->field('uid')->where("uid < 1000")->select();
        //$user_list = db(1,"mysql://qianbao_v2_test:qianbao_v2_test@drdsbggalu906z4opublic.drds.aliyuncs.com/qianbao_v2_test")->table('b_1_user_list')->field('uid')->where("uid < 500")->select();
        //$this->assign('draft',$draft);
        //return $this->fetch();
        //var_dump($user_list);

        //Db::connect('mysql://root:1234@127.0.0.1:3306/thinkphp#utf8');

        $a = ['a', 'b', 'c'];
        $b = ['b', 'c', 'd', 'e'];
        var_dump($a + $b);
        echo "<br>";
        var_dump(array_merge($a, $b));
        echo "<br>";

        $a = array(0, 1, 2, 3);
        $b = array(2, 3, 4, 5);
        $c = $a + $b;
        var_dump($c);
        echo "<br>";
        var_dump(array_merge($a, $b));
        echo "<br>";

        /*
        $a = array('a' => 'A', 'b' => 'B', 'c' => 'C');
        $b = array('b' => '1', 'c' => '2', 'd' => '3');
        $c = $a + $b;
        var_dump($c);
        echo "<br>";
        $c = array_merge($a, $b);
        var_dump($c);
        echo "<br>";

        $redis = get_redis();
        $redis -> set('redis_value','redis_value',60);
        $redis_str = $redis->get('redis_value');
        echo "from redis:" . $redis_str;
        echo "<br>";

        $memcache = get_memcache();
        $memcache -> set('memcache_value','memcache_value',60);
        $mem_str = $memcache -> get('memcache_value');
        echo "from memcache:" . $mem_str;
        echo "<br>";
        */

        $des = new QbDES();
        for($i=0; $i<5; $i++){
            $a = $des->encrypt($i);
            echo $a."<br>";
        }

        $a = 1;
        $b = '2a';
        var_dump(is_string($b));var_dump(is_numeric($b));
        //echo phpinfo();
        echo urlencode('3+BcmKa6jbanBH6/zMuSNtrifmgiiSXXyrNtHlF1Z51S3jeTY7h37lLlEYut8ikmY8L0BVSH6tWqqnj5Z70H/4e8Ix7aDkTi+4TuNHkezpc=');
    }

    public function test()
    {
        $arr = [];
        $arr['uid'] = '10';
        $arr['time'] = '1536940800';
        $arr['auth'] = 'ab123456ab123456';
        $a = $arr;
        $arr['uid'] = 1;
        $arr['time'] = 1536940800;
        $arr['auth'] = 'ab123456ab123456';
        $aa = $arr;
        // $b = sdk_encrypt($a);
		echo "one=".json_encode($a);
		echo '<br/>';
        $b = web_encrypt($a, 'ab123456ab123456');
        echo "web_encrypt: ".$b;
        echo "<br>web_decrypt:";
		//iBP4IN7d0l1Rz4OD7HRIENSAtwFLrYK3Hey6v0vBdHN5UhBmWjJOwXaSvD4TOLmvoVflQpLAjavrlEygLVkVdg==
        $c = web_decrypt($b,'ab123456ab123456');
        var_dump($c);
        echo "<br>";

        $d = sdk_encrypt($a);
        echo "sdk_encrypt : ".$d;
        echo '<br/>';
        echo urlencode($d);
        echo "<br>";
        echo "sdk_encrypt : ".urlencode($d);
        echo "<br>";
        $c = sdk_decrypt('cyi2iFi+HaMSizFXX6sduFbGDC8N/hs/b3wNOBy6mQ9F/1WbI0puWPiguJ4nd80v+FPFA6p5ernBiVAa3klFuw==');
        var_dump($c);
        echo "<br>";

    }

	public function arr_v2s($arr){
        $r_arr = $arr;
        if (is_array($arr)) {
            foreach ($arr as $k=>$v) {
                $r_arr[$k] = (string)$v;
            }
        }
        return $r_arr;
	}

    public function test2()
    {
		for ($i=0; $i<100; $i++) {
			$arr["uid"] = $i;
			$arr["time"] = 1536940800;
			$arr["auth"] = 'ab123456ab123456';
			$aa = $arr;//$this->arr_v2s($arr);
			echo "two=".json_encode($aa);
			echo '<br/>';
			$bb = web_encrypt($aa, 'ab123456ab123456');
			echo $bb;
			//echo '<br/>';
			//echo base64_decode($bb);
            echo '<br/>';
			$cc = web_decrypt($bb,'ab123456ab123456');
			var_dump($cc);
			echo '<br/>';
		}
	}

/*
	public function index2()
    {
		echo __CLASS__;
		echo "<br>";
		echo "<center>hello</center>";

        echo App::getRootPath().'vendor\\'."<br>";
        echo Env::get('think_path');

        //App::getThinkPath()
        echo phpinfo();
	
	}

    public function test()
    {
        $arr = [];
        $arr['uid'] = 1001;
        $arr['time'] = 1536940800;
        $arr['auth'] = 'ab123456ab123456';
        $a = $arr;
        // $b = sdk_encrypt($a);
        $b = web_encrypt($a, 'ab123456ab123456');
        echo "web_encrypt: ".$b;
        echo "<br>";
        $c = web_decrypt('iBP4IN7d0l1Rz4OD7HRIENSAtwFLrYK3Hey6v0vBdHN5UhBmWjJOwXaSvD4TOLmvoVflQpLAjavrlEygLVkVdg==','ab123456ab123456');
        var_dump($c);
        echo "<br>";

        $d = sdk_encrypt($a);
        echo "sdk_encrypt: ".$d;
        echo "<br>";
        echo "sdk_encrypt: ".urlencode($d);
        echo "<br>";
        $c = sdk_decrypt('cyi2iFi+HaMSizFXX6sduFbGDC8N/hs/b3wNOBy6mQ9F/1WbI0puWPiguJ4nd80v+FPFA6p5ernBiVAa3klFuw==');
        var_dump($c);
        echo "<br>";

    }
*/

    function format_stock_price_num($price, $weight = 8)//20180820之前用php的round，后来决定用抛弃法，不进位
    {
        echo "a";
        $price_fix = 0;
        if (!empty($price)) {
            echo "b";
            $arr_p = explode('.', $price);
            echo "c";
            if (count($arr_p) == 2) {
                echo "d";
                if ($weight == 8) {
                    echo "e";
                    $price_1 = $price * 100000000;
                    $arr_price = explode('.', $price_1);
                    $price_fix = $arr_price[0] * 0.00000001;
                } elseif ($weight == 4) {
                    $price_1 = $price * 10000;
                    $arr_price = explode('.', $price_1);
                    $price_fix = $arr_price[0] * 0.0001;
                }
            }
        }
        return $price_fix;
    }

    function format_price_num($price, $weight = 8)//20180821之前用php的round处理，后来决定用抛弃法不进位
    {
        $price_fix = $price;
        if (!empty($price)) {
            $arr_p = explode('.', $price);
            if (count($arr_p) == 2) {
                $price_fix = substr($price, 0, $weight + 2);
            }
        }
        return $price_fix;
    }

    function format_price($price, $weight = 8)//20180821之前用php的round处理，后来决定用抛弃法不进位
    {
        $price_fix = $price;
        if (!empty($price)) {
            $arr_p = explode('.', $price);
            if (count($arr_p) == 2) {
                $price_fix = $arr_p[0] . '.' . substr($arr_p[1], 0, $weight);
            }
        }
        return $price_fix;
    }

    function emojiDecode($str)
    {
        $text = json_encode($str); //暴露出unicode
        $text = preg_replace_callback('/\\\\\\\\/i', function ($str) {
            return '\\';
        }, $text); //将两条斜杠变成一条，其他不动
        return json_decode($text);
    }


    public function index3()
    {
        ini_set('display_errors', 'on');
        error_reporting(E_ALL);

        $sec = 60 * 60 * 2;
        set_time_limit($sec);                //执行时间无限
        //echo ini_get('memory_limit')."<br>";
        ini_set('memory_limit', '2048M');    //-1内存无限
        ini_set('max_execution_time', '3600');//mysql5.6 max_statement_time
        //echo ini_get('memory_limit')."<br>";

        $time_s = time();
        echo "star " . $time_s . "<br>";
        $conn = mysqli_connect('127.0.0.1', 'root', 'root', 'qianbao_m7');
        if (!$conn) {
            printf("Can't connect to MySQL Server. Errorcode: %s ", mysqli_connect_error());
            exit;
        } else {
            echo "连接上数据库" . time() . "<br/>";   // Close the connection 关闭连接//
        }
        mysqli_query($conn, 'set names utf8');    //解决中文乱码的问题
        $price_s = '0.02163328';
        $price_i = 2163328;
        $time_ss = 1533883361;
        $all_key = '(`price`,addtime)';
        for ($j = 1; $j <= 15984; $j++) {//15984

            $price_1 = $price_i + $j;
            $price_2 = $price_1 * 0.00000001;
            $time_ss = $time_ss + mt_rand(48, 62);
            $arr[] = "({$price_2},{$time_ss})";

            if (count($arr) >= 1000) {
                $all_value = implode(',', $arr);
                $r6 = mysqli_query($conn, "INSERT into b_1_stock_price {$all_key} values {$all_value}");
                $arr = null;
            }
        }
        if (count($arr) > 0) {
            $all_value = implode(',', $arr);
            $r6 = mysqli_query($conn, "INSERT into b_1_stock_price {$all_key} values {$all_value}");
            $arr = null;
        }

        $time_e = time();
        echo "end " . $time_e . "<br>";
        $run = $time_e - $time_s;
        echo "run=" . $run . " 秒<br>";
        ini_set('memory_limit', '512M'); //恢复默认内存
        set_time_limit(30);                //恢复超时
        ini_set('max_execution_time', '30');
    }


    public function index2()
    {
        echo 1 + '3 + 5';

        echo "<br>";

        $a = "aabbzz";
        $a++;
        echo $a;

        echo "<br>";

        $a = 1;
        $b = &$a;
        $c = $a++;
        echo $a . $b . $c;
    }

    public function index_1()
    {

        //$bq = '旧时光\ud83d\udc8b\ud83d\udc93'."<br>";
        //echo $this->emojiDecode($bq);
        //$bq = '\ud83d\ude18\ud83d\ude18'."<br>";
        //echo $this->emojiDecode($bq);

        ini_set('display_errors', 'on');
        error_reporting(E_ALL);

        $sec = 60 * 60 * 2;
        set_time_limit($sec);                //执行时间无限
        //echo ini_get('memory_limit')."<br>";
        ini_set('memory_limit', '2048M');    //-1内存无限
        ini_set('max_execution_time', '3600');//mysql5.6 max_statement_time 062118
        //echo ini_get('memory_limit')."<br>";

        $time_s = time();
        echo "star " . $time_s . "<br>";
        /*
            $arr = array();
            $all_key = " (uid,`month`,count_t1,money_t1,num_t1,count_t2,money_t2,num_t2) ";
            for($k = 7;$k <= 7;$k++){
                for($j = 0;$j <= 9;$j++){
                    // Connect to a MySQL server 连接数据库服务器 //
                    $conn = mysqli_connect( '127.0.0.1', 'root', 'root', 'qianbao_m7');
                    if (!$conn) {
                        printf("Can't connect to MySQL Server. Errorcode: %s ", mysqli_connect_error());
                         exit;
                    } else {
                        echo "连接上数据库".$k.':'.$j." ".time()."<br/>";   // Close the connection 关闭连接//
                    }
                    mysqli_query($conn,'set names utf8');    //解决中文乱码的问题

                    //for($m = 0;$m< 550;$m++){
                        if ($result = mysqli_query($conn, "SELECT uid from b_w_invite_m180{$k}_u{$j} where uid > 0 and uid < 55000 group by uid order by uid")){
                            // Fetch the results of the query 返回查询的结果
                            $arr = null;
                            while( $row = mysqli_fetch_assoc($result) )
                            {
                                $r1_c = $r1_m = $r1_n = $r2_c = $r2_m = $r2_n = 0;
                                $uid = $row['uid'];

                                $result0 = mysqli_query($conn, "select id,uid,type,money,num from b_w_invite_m180{$k}_u{$j} where uid = {$uid} and type = 1 order by id");
                                while( $r0 = mysqli_fetch_assoc($result0) ){

                                        $r1_c = $r1_c + 1;
                                        $r1_m = bcadd($r1_m , $r0['money'],4);
                                        $r1_n = bcadd($r1_n , $r0['num'] ,8);

                                }

                                $result0 = mysqli_query($conn, "select id,uid,type,money,num from b_w_invite_m180{$k}_u{$j} where uid = {$uid} and type = 2 and isopen = 0 order by id");
                                while( $r0 = mysqli_fetch_assoc($result0) ){

                                        $r2_c = $r2_c + 1;
                                        $r2_m = bcadd($r2_m , $r0['money'],4);
                                        $r2_n = bcadd($r2_n , $r0['num'] ,8);

                                }

                                $arr[] = "({$uid},20180{$k},{$r1_c},{$r1_m},{$r1_n},{$r2_c},{$r2_m},{$r2_n})";

                                if(count($arr) >= 100){
                                    $all_value = implode(',',$arr);
                                    $r6 = mysqli_query($conn, "INSERT into b_w_invite_count {$all_key} values {$all_value}");
                                    $arr = null;
                                }
                            }
                            if(count($arr) > 0){
                                $all_value = implode(',',$arr);
                                $r6 = mysqli_query($conn, "INSERT into b_w_invite_count {$all_key} values {$all_value}");
                                $arr = null;
                            }
                        }
                        // Destroy the result set and free the memory used for it 结束查询释放内存
                        if(!empty($result0)) mysqli_free_result($result0);
                        if(!empty($result)) mysqli_free_result($result);

                        // Close the connection 关闭连接//
                        mysqli_close($conn);
                        sleep(1);
                    //}
                }
            }
        */
        /*
            $arr = array();
            $all_key = " (uid,`month`,count_t1,money_t1,num_t1,count_t2,money_t2,num_t2) ";
            for($k = 7;$k <= 7;$k++){
                for($j = 0;$j <= 9;$j++){
                    // Connect to a MySQL server 连接数据库服务器 //
                    $conn = mysqli_connect( '127.0.0.1', 'root', 'root', 'qianbao_m7');
                    if (!$conn) {
                        printf("Can't connect to MySQL Server. Errorcode: %s ", mysqli_connect_error());
                         exit;
                    } else {
                        echo "连接上数据库".$k.':'.$j." ".time()."<br/>";   // Close the connection 关闭连接//
                    }
                    mysqli_query($conn,'set names utf8');    //解决中文乱码的问题

                    //for($m = 0;$m< 550;$m++){
                        if ($result = mysqli_query($conn, "SELECT uid from b_w_my_ad_m180{$k}_u{$j} where uid > 0 and uid < 55000 group by uid order by uid")){
                            // Fetch the results of the query 返回查询的结果
                            $arr = null;
                            while( $row = mysqli_fetch_assoc($result) )
                            {
                                $r1_c = $r1_m = $r1_n = $r2_c = $r2_m = $r2_n = 0;
                                $uid = $row['uid'];

                                $result0 = mysqli_query($conn, "select id,uid,type,money,num from b_w_my_ad_m180{$k}_u{$j} where uid = {$uid} order by id");

                                while( $r0 = mysqli_fetch_assoc($result0) ){

                                    if($r0['type'] == 1){
                                        $r1_c = $r1_c + 1;
                                        $r1_m = bcadd($r1_m , $r0['money'],4);
                                        $r1_n = bcadd($r1_n , $r0['num'] ,8);
                                    }elseif($r0['type'] == 2){
                                        $r2_c = $r2_c + 1;
                                        $r2_m = bcadd($r2_m , $r0['money'],4);
                                        $r2_n = bcadd($r2_n , $r0['num'] ,8);
                                    }

                                }
                                $arr[] = "({$uid},20180{$k},{$r1_c},{$r1_m},{$r1_n},{$r2_c},{$r2_m},{$r2_n})";

                                if(count($arr) >= 100){
                                    $all_value = implode(',',$arr);
                                    $r6 = mysqli_query($conn, "INSERT into b_w_my_ad_count {$all_key} values {$all_value}");
                                    $arr = null;
                                }
                            }
                            if(count($arr) > 0){
                                $all_value = implode(',',$arr);
                                $r6 = mysqli_query($conn, "INSERT into b_w_my_ad_count {$all_key} values {$all_value}");
                                $arr = null;
                            }
                        }
                        // Destroy the result set and free the memory used for it 结束查询释放内存
                        if(!empty($result0)) mysqli_free_result($result0);
                        if(!empty($result)) mysqli_free_result($result);

                        // Close the connection 关闭连接//
                        mysqli_close($conn);
                        sleep(1);
                    //}
                }
            }
        */
        /*
            $arr = array();
            $all_key = " (uid,`month`,count_t1,money_t1,num_t1,count_t2,money_t2,num_t2,count_t3,money_t3,num_t3,count_t4,money_t4,num_t4,count_t5,money_t5,num_t5) ";
            for($k = 7;$k <= 7;$k++){
                for($j = 7;$j <= 9;$j++){
                    // Connect to a MySQL server 连接数据库服务器 //
                    $conn = mysqli_connect( '127.0.0.1', 'root', 'root', 'qianbao_m7');
                    if (!$conn) {
                        printf("Can't connect to MySQL Server. Errorcode: %s ", mysqli_connect_error());
                         exit;
                    } else {
                        echo "连接上数据库".$k.':'.$j." ".time()."<br/>";   // Close the connection 关闭连接//
                    }
                    mysqli_query($conn,'set names utf8');    //解决中文乱码的问题

                    //for($m = 0;$m< 550;$m++){
                        if ($result = mysqli_query($conn, "SELECT uid from b_w_my_record_m180{$k}_u{$j} where uid > 0 and uid < 55000 group by uid order by uid")){
                            // Fetch the results of the query 返回查询的结果
                            $arr = null;
                            while( $row = mysqli_fetch_assoc($result) )
                            {
                                $r1_c = $r1_m = $r1_n = $r2_c = $r2_m = $r2_n = $r3_c = $r3_m = $r3_n = $r4_c = $r4_m = $r4_n = $r5_c = $r5_m = $r5_n = 0;
                                $uid = $row['uid'];

                                $result0 = mysqli_query($conn, "select id,uid,type,money,num from b_w_my_record_m180{$k}_u{$j} where uid = {$uid} order by id");

                                while( $r0 = mysqli_fetch_assoc($result0) ){

                                    if($r0['type'] == 1){
                                        $r1_c = $r1_c + 1;
                                        $r1_m = bcadd($r1_m , $r0['money'],4);
                                        $r1_n = bcadd($r1_n , $r0['num'] ,8);
                                    }elseif($r0['type'] == 2){
                                        $r2_c = $r2_c + 1;
                                        $r2_m = bcadd($r2_m , $r0['money'],4);
                                        $r2_n = bcadd($r2_n , $r0['num'] ,8);
                                    }elseif($r0['type'] == 3){
                                        $r3_c = $r3_c + 1;
                                        $r3_m = bcadd($r3_m , $r0['money'],4);
                                        $r3_n = bcadd($r3_n , $r0['num'] ,8);
                                    }elseif($r0['type'] == 4){
                                        $r4_c = $r4_c + 1;
                                        $r4_m = bcadd($r4_m , $r0['money'],4);
                                        $r4_n = bcadd($r4_n , $r0['num'] ,8);
                                    }elseif($r0['type'] == 5){
                                        $r5_c = $r5_c + 1;
                                        $r5_m = bcadd($r5_m , $r0['money'],4);
                                        $r5_n = bcadd($r5_n , $r0['num'] ,8);
                                    }

                                }
                                $arr[] = "({$uid},20180{$k},{$r1_c},{$r1_m},{$r1_n},{$r2_c},{$r2_m},{$r2_n},{$r3_c},{$r3_m},{$r3_n},{$r4_c},{$r4_m},{$r4_n},{$r5_c},{$r5_m},{$r5_n})";

                                if(count($arr) >= 100){
                                    $all_value = implode(',',$arr);
                                    $r6 = mysqli_query($conn, "INSERT into b_w_my_record_count {$all_key} values {$all_value}");
                                    $arr = null;
                                }
                            }
                            if(count($arr) > 0){
                                $all_value = implode(',',$arr);
                                $r6 = mysqli_query($conn, "INSERT into b_w_my_record_count {$all_key} values {$all_value}");
                                $arr = null;
                            }
                        }
                        // Destroy the result set and free the memory used for it 结束查询释放内存
                        if(!empty($result0)) mysqli_free_result($result0);
                        if(!empty($result)) mysqli_free_result($result);

                        // Close the connection 关闭连接//
                        mysqli_close($conn);
                        sleep(1);
                    //}
                }
            }
        */

        $time_e = time();
        echo "end " . $time_e . "<br>";
        $run = $time_e - $time_s;
        echo "run=" . $run . " 秒<br>";
        ini_set('memory_limit', '512M'); //恢复默认内存
        set_time_limit(30);                //恢复超时
        ini_set('max_execution_time', '30');
        //echo ini_get('memory_limit')."<br>";

    }

    public function db_count()
    {

        $arr = array();
        $all_key = " (uid,`month`,count_t1,money_t1,num_t1,count_t2,money_t2,num_t2,count_t3,money_t3,num_t3,count_t4,money_t4,num_t4,count_t5,money_t5,num_t5) ";
        for ($k = 5; $k <= 5; $k++) {
            for ($j = 0; $j <= 0; $j++) {
                // Connect to a MySQL server 连接数据库服务器 //
                $conn = mysqli_connect('127.0.0.1', 'root', 'root', 'qianbao_temp');
                if (!$conn) {
                    printf("Can't connect to MySQL Server. Errorcode: %s ", mysqli_connect_error());
                    exit;
                } else {
                    echo "连接上数据库" . $k . ':' . $j . " " . time() . "<br/>";   // Close the connection 关闭连接//
                }
                mysqli_query($conn, 'set names utf8');    //解决中文乱码的问题

                if ($result = mysqli_query($conn, "SELECT uid from b_w_my_record_m180{$k}_u{$j} where uid > 0 and uid < 55000 group by uid order by uid")) {
                    // Fetch the results of the query 返回查询的结果
                    $arr = null;
                    while ($row = mysqli_fetch_assoc($result)) {
                        $r1_c = $r1_m = $r1_n = $r2_c = $r2_m = $r2_n = $r3_c = $r3_m = $r3_n = $r4_c = $r4_m = $r4_n = $r5_c = $r5_m = $r5_n = 0;
                        $uid = $row['uid'];

                        $result0 = mysqli_query($conn, "select id,uid,type,money,num from b_w_my_record_m180{$k}_u{$j} where uid = {$uid} order by id");

                        while ($r0 = mysqli_fetch_assoc($result0)) {

                            if ($r0['type'] == 1) {
                                $r1_c = $r1_c + 1;
                                $r1_m = bcadd($r1_m, $r0['money'], 4);
                                $r1_n = bcadd($r1_n, $r0['num'], 8);
                            } elseif ($r0['type'] == 2) {
                                $r2_c = $r2_c + 1;
                                $r2_m = bcadd($r2_m, $r0['money'], 4);
                                $r2_n = bcadd($r2_n, $r0['num'], 8);
                            } elseif ($r0['type'] == 3) {
                                $r3_c = $r3_c + 1;
                                $r3_m = bcadd($r3_m, $r0['money'], 4);
                                $r3_n = bcadd($r3_n, $r0['num'], 8);
                            } elseif ($r0['type'] == 4) {
                                $r4_c = $r4_c + 1;
                                $r4_m = bcadd($r4_m, $r0['money'], 4);
                                $r4_n = bcadd($r4_n, $r0['num'], 8);
                            } elseif ($r0['type'] == 5) {
                                $r5_c = $r5_c + 1;
                                $r5_m = bcadd($r5_m, $r0['money'], 4);
                                $r5_n = bcadd($r5_n, $r0['num'], 8);
                            }

                        }
                        $arr[] = "({$uid},20180{$k},{$r1_c},{$r1_m},{$r1_n},{$r2_c},{$r2_m},{$r2_n},{$r3_c},{$r3_m},{$r3_n},{$r4_c},{$r4_m},{$r4_n},{$r5_c},{$r5_m},{$r5_n})";

                        if (count($arr) >= 100) {
                            $all_value = implode(',', $arr);
                            $r6 = mysqli_query($conn, "INSERT into b_w_my_record_count {$all_key} values {$all_value}");
                            $arr = null;
                        }
                    }
                    if (count($arr) > 0) {
                        $all_value = implode(',', $arr);
                        $r6 = mysqli_query($conn, "INSERT into b_w_my_record_count {$all_key} values {$all_value}");
                        $arr = null;
                    }
                }
                // Destroy the result set and free the memory used for it 结束查询释放内存
                if (!empty($result0)) mysqli_free_result($result0);
                if (!empty($result)) mysqli_free_result($result);

                // Close the connection 关闭连接//
                mysqli_close($conn);
                sleep(1);

            }
        }
    }

    public function img()
    {
        $imgpath = 'zt1.jpg';
        $f_info = @getimagesize($imgpath);
        switch ($f_info['mime']) {
            case 'image/pjpeg':
            case 'image/jpeg':
            case 'image/jpg':
                $src_img = imagecreatefromjpeg($imgpath);
                header('Content-Type:image/jpeg;');
                imagejpeg($img);
                break;
            case 'image/png':
                $src_img = imagecreatefrompng($imgpath);
                header('Content-Type:image/pgg;');
                imagepng($img);
                break;
            case 'image/gif':
                $src_img = imagecreatefromgif($imgpath);
                header('Content-Type:image/gif;');
                imagegif($img);
                break;
            case 'image/bmp':
                $src_img = imagecreatefrombmp($imgpath);
                header('Content-Type:image/bmp;');
                imagebmp($img);
                break;
            default :
                $src_img = imagecreatefromjpeg($imgpath);
                header('Content-Type:image/gif;');
                imagejpeg($img);
        }

    }

    public function create_table () {
        $tb_pre = 'b_';
        $DT_TIME = time();
        $next_month = 'm'.date('ym',strtotime('+1 month',$DT_TIME));
        $arr_sql = array();
        for($i = 0; $i < 10; $i++){
            $arr_sql[] =
                "CREATE TABLE IF NOT EXISTS `{$tb_pre}w_invite_{$next_month}_u{$i}` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
            `from_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '从谁那里获得',
            `money` decimal(16,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '去掉分成之后的最终金额',
            `user_money` decimal(16,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '直接获取的金额',
            `num` decimal(16,4) unsigned NOT NULL DEFAULT '0.0000',
            `isopen` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '注册红包是否可以打开  1可以打开 0不可以打开',
            `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '类型 1邀请分红奖励  2邀请注册奖励',
            `addtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
            PRIMARY KEY (`id`),
            KEY `addtime` (`addtime`) USING BTREE,
            KEY `uid` (`uid`) USING BTREE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

            $arr_sql[] =
                "CREATE TABLE IF NOT EXISTS `{$tb_pre}w_my_ad_{$next_month}_u{$i}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
            `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
            `money` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '收到的红包金额',
            `num` decimal(16,4) NOT NULL DEFAULT '0.0000' COMMENT '股数',
            `from_uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '红包来自那个用户',
            `ad_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '来自发红包的id，以便查询更多信息',
            `ad_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0系统红包,1广播,2祝福',
            `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '红包类型1广播2祝福',
            `addtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '获取到此红包的时间',
            PRIMARY KEY (`id`),
            KEY `ad_id` (`ad_id`) USING BTREE,
            KEY `addtime` (`addtime`) USING BTREE,
            KEY `uid` (`uid`) USING BTREE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

            $arr_sql[] =
                "CREATE TABLE IF NOT EXISTS `{$tb_pre}w_my_record_{$next_month}_u{$i}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
            `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
            `from_uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '来自ID',
            `money` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '收到的红包金额',
            `num` decimal(16,8) unsigned NOT NULL DEFAULT '0.00000000' COMMENT '股数',
            `addtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
            `type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '类别  1系统红包  2广播和祝福红包 3邀请人开红包奖励 4邀请人注册奖励 5提现',
            PRIMARY KEY (`id`),
            KEY `addtime` (`addtime`) USING BTREE,
            KEY `uid` (`uid`) USING BTREE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        }

        $arr_sql[] =
            "CREATE TABLE IF NOT EXISTS `{$tb_pre}w_api_{$next_month}` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `uid` int(11) NOT NULL DEFAULT '0',
        `api_action` smallint(6) NOT NULL DEFAULT '0' COMMENT '接口编码',
        `city_code` int(11) NOT NULL DEFAULT '0' COMMENT '城市编码',
        `status` tinyint(3) NOT NULL DEFAULT '77',
        `addtime` int(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        KEY `api_action` (`api_action`) USING BTREE
        ) ENGINE=InnoDB AUTO_INCREMENT=23463319 DEFAULT CHARSET=utf8mb4 COMMENT='api调用成功流水表';";

        foreach($arr_sql as $one){
            echo $one."<br>";
            //$db_connect->query($one);
        }
    }


    /**
     * @cc 随机取n个用户，错开nickname、avatar和随机金额
     *
     * @param $uid
     * @param int $num
     * @param int $min
     * @param int $max
     * @return array
     *
     * @author JF
     * @date 2018-11-15
     * @version 1.0
     */
    public function getRandUserList ()//$uid, $num = 20, $min = 10, $max = 100)
    {
        if (substr(md5($this->request['time']) , 0 , 8) == $this->request['sign']) {

            $uid = 1;
            $num = isset($this->request['num']) ? $this->request['num'] : 20;
            $min = isset($this->request['min']) ? $this->request['min'] : 10;
            $max = isset($this->request['max']) ? $this->request['max'] : 100;

            $memcache = get_memcache();
            $key = 'rand_user_list_'.$num.'_'.$min.'_'.$max;
            $arr = $memcache->get($key);
            if (!$arr) {
                $UserList = new UserList();
                $arr = $UserList->getRandUserList($uid, $num, $min, $max);
                $memcache->set($key, $arr, false, 30);
                return json_encode($arr);
            } else {
                return json_encode($arr);
            }
        }
    }

}
