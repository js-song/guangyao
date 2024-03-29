<?php

/**
 * @Author   Hulkzero
 * @DateTime 2018-08-31T11:24:48+0800
 * @Email    hulkzero@163.com
 * $Explain  来捡钱game游戏大厅接口信息
 */

namespace app\h5\controller\question;

use think\Controller;
use think\Request;
use think\Cache;

use app\util\WeixinUtil;
use app\h5\model\question\QuestionModel;

class Question extends Controller
{   

    protected $question_model;
    public function __construct()
    {
        parent::__construct();
        $this->question_model = new QuestionModel();
        
    }
    function maximum($a,$b,$c){
        return $a > $b ? ($a > $c ? $a : $c) : ($b > $c ? $b : $c);
    }
    /**
     * 显示游戏大厅详细信息
     *
     * @return \think\Response
     */
    public function home()
    {
        
        // 数据统计
        
        // 进入活动的人数
        $userip = $this->request->ip();
        if(!empty($userip)){
            // if(sys_ver()==1){
                // 实例化缓存
                $memcache = get_memcache();
                $mem_valid_time = 30;
                $memcache_key = 'home_count_'.$userip;
                $mem_list = $memcache->get($memcache_key);
                //缓存中没有取数据库
                if (empty($mem_list)) {
 
                    $new_list = array(
                        "userip"=>$userip,
                        "createtime"=>time()
                    );
                    //更新缓存
                    $memcache->set($memcache_key,$new_list,false,$mem_valid_time);
                    
                    $mem_list = $memcache->get($memcache_key);

                    $roll_data = $mem_list;
                   
                } else {
                    $roll_data = $mem_list;//缓存中的数据
                    
                }
            // }

            // 是否存在记录
            $where = array();
            $where['ip'] = $roll_data['userip'];
            $where['type'] = 1; //进入活动记录
            $Status = $this->question_model->getipStatus($where);

            if(!$Status){
                // 第一次进入活动，增加用户记录，即：进入活动人数
                $data = array();
                $data['ip'] = $roll_data['userip'];
                $data['type'] = 1;
                $data['createtime'] = $roll_data['createtime'];
                $info = $this->question_model->addIp($data);
            }

            $reward_id = $this->request->param("reward_id",0,'intval');
         
            if(!empty($reward_id)){
                
                // 统计装备识别人数
                $where = array();
                $where['reward_id'] = $reward_id;
                $where['ip'] = $roll_data['userip'];
                $where['type'] = 2; //识别
                $info = $this->question_model->addRewardData($reward_id,$where,$roll_data['createtime']);
            }
            
        }
        
        //模板变量赋值
        $this->assign('title','游戏大厅');
        //模板输出
        return $this->fetch('question/home');
    }

    /**
     * 下载页
     *
     * @return \think\Response
     */
    public function download()
    {
        $userip = $this->request->ip();
        if(!empty($userip)){
            // if(sys_ver()==1){
                // 实例化缓存
                $memcache = get_memcache();
                $mem_valid_time = 30;
                $memcache_key = 'download_count_'.$userip;
                $mem_list = $memcache->get($memcache_key);
                //缓存中没有取数据库
                if (empty($mem_list)) {
 
                    $new_list = array(
                        "userip"=>$userip,
                        "createtime"=>time()
                    );
                    //更新缓存
                    $memcache->set($memcache_key,$new_list,false,$mem_valid_time);
                    
                    $mem_list = $memcache->get($memcache_key);

                    $roll_data = $mem_list;
                   
                } else {
                    $roll_data = $mem_list;//缓存中的数据
                    
                }
            // }
            $where = array();
            $where['ip'] = $roll_data['userip'];
            $where['type'] = 3; //下载页打开
            $Status = $this->question_model->getipStatus($where);

            if(!$Status){
                // 第一次打开下载页，增加用户记录
                $data = array();
                $data['ip'] = $roll_data['userip'];
                $data['type'] = 3;
                $data['createtime'] = $roll_data['createtime'];
                $info = $this->question_model->addIp($data);
            }
        }

        $back = $this->request->param("back",0,'intval');
        $this->assign('back',$back);
        //模板变量赋值
        $this->assign('title','来捡钱APP下载');
        //模板输出
        return $this->fetch('question/download');
    }

    /**
     * 获取答案
     * @return [type] [description]
     */
    public function answer(){
        $data = $this->request->param();
        $data = $data['sum'];
       
        $res = array_count_values($data);
        $a = isset($res['A']) ? $res['A'] : 0;
        $b = isset($res['B']) ? $res['B'] : 0;
        $c = isset($res['C']) ? $res['C'] : 0;
        $max = max($res);
        $where = "";
        
        if($a==$max){

            $where = "grade=5";
            
        }
        if($b==$max){
            if(!empty($where)){
                $where .= " OR grade=3";
            }else{
                $where = "grade=3";
            }
        }
        if($c==$max){
            if(!empty($where)){
                $where .= " OR grade=1";
            }else{
                $where = "grade=1";
            }
        }
        $rewardlist = $this->question_model->getRewardList($where);
        $rand = array_rand($rewardlist,1);
        $res = $rewardlist[$rand];
        
        $userip = $this->request->ip();
        if(!empty($userip)){

            // if(sys_ver()==1){
                // 实例化缓存
                $memcache = get_memcache();
                $mem_valid_time = 30;
                $memcache_key = 'answer_count_'.$userip;
                $mem_list = $memcache->get($memcache_key);
                //缓存中没有取数据库
                if (empty($mem_list)) {
 
                    $new_list = array(
                        "userip"=>$userip,
                        "createtime"=>time()
                    );
                    //更新缓存
                    $memcache->set($memcache_key,$new_list,false,$mem_valid_time);
                    
                    $mem_list = $memcache->get($memcache_key);

                    $roll_data = $mem_list;
                   
                } else {
                    $roll_data = $mem_list;//缓存中的数据
                    
                }
            // }
            
            $where = array();
            $where['ip'] = $roll_data['userip'];
            $where['type'] = 2; //打开弹层
            $Status = $this->question_model->getipStatus($where);

            if(!$Status){
                // 第一次打开弹层，增加用户记录
                $data = array();
                $data['ip'] = $roll_data['userip'];
                $data['type'] = 2;
                $data['createtime'] = $roll_data['createtime'];
                $info = $this->question_model->addIp($data);
            }

            $reward_id = $res['reward_id'];
            if(!empty($reward_id)){
                // 统计装备生成人数
                $where = array();
                $where['reward_id'] = $reward_id;
                $where['ip'] = $roll_data['userip'];
                $where['type'] = 1; //生成
                $this->question_model->addRewardData($reward_id,$where);
                
            }
        }

        return $res['pic_url'];
    }
    
    /**
     * 点击下载按钮-统计人数
     */
    public function download_btn(){
        $userip = $this->request->ip();
        if(!empty($userip)){

            // if(sys_ver()==1){
                // 实例化缓存
                $memcache = get_memcache();
                $mem_valid_time = 30;
                $memcache_key = 'download_btn_count_'.$userip;
                $mem_list = $memcache->get($memcache_key);
                //缓存中没有取数据库
                if (empty($mem_list)) {
 
                    $new_list = array(
                        "userip"=>$userip,
                        "createtime"=>time()
                    );
                    //更新缓存
                    $memcache->set($memcache_key,$new_list,false,$mem_valid_time);
                    
                    $mem_list = $memcache->get($memcache_key);

                    $roll_data = $mem_list;
                   
                } else {
                    $roll_data = $mem_list;//缓存中的数据
                    
                }
            // }
            // 
            $where = array();
            $where['ip'] = $roll_data['userip'];
            $where['type'] = 4; //打开弹层
            $Status = $this->question_model->getipStatus($where);

            if(!$Status){
                // 第一次打开弹层，增加用户记录
                $data = array();
                $data['ip'] = $roll_data['userip'];
                $data['type'] = 4;
                $data['createtime'] = $roll_data['createtime'];
                $info = $this->question_model->addIp($data);
                
            }
        }
        return true;
    }

    /**
     * 微信公众平台接口配置
     * @return
     */
    public function WeixinUtil(){
        $WeixinUtil = new WeixinUtil();
        return $WeixinUtil->check();
    }

    /**
     * 获取微信 signature
     * @return array
     */
    public function get_signature(){
        
        $url = $this->request->param('url');
        
        $root['url'] = $url;
        //获取access_token，并缓存
        // $file = $_SERVER['DOCUMENT_ROOT'].'/uploads/wx_cache/access_token';//缓存文件名access_token
        $appid = 'wx21c889308c7f57b8';//'wx602bbd164a42fbeb'; // 填写自己的appid
        $secret = 'b5344c3f242d6efb1f0289f1d5b013ca';//'bfa01623188c93f8b53ed90f0e0c6864'; // 填写自己的appsecret
        // $expires = 3600;//缓存时间1个小时
        if(sys_ver()==3){
            $memcache = get_memcache();
            $mem_valid_time = 3600;

            //获取access_token，并缓存
            $memcache_key = 'sign_access_token';
            $mem_list = $memcache->get($memcache_key);
            //缓存中没有取数据库
            if (empty($mem_list)) {

                $token = null;

                if (!$token || strlen($token) < 6) {
                $res = file_get_contents("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."");
                $res = json_decode($res, true);
                $token = $res['access_token'];
                }

                $new_list = $token;
                //更新缓存
                $memcache->set($memcache_key,$new_list,false,$mem_valid_time);
               
            } else {
                $token = $mem_list;//缓存中的数据
            }

            //获取jsapi_ticket，并缓存
            $memcache_key = 'sign_jsapi_ticket';
            $mem_list = $memcache->get($memcache_key);
            //缓存中没有取数据库
            if (empty($mem_list)) {

                $jsapi_ticket = null;

                if (!$jsapi_ticket || strlen($jsapi_ticket) < 6) {
                $ur = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=$token&type=jsapi";
                $res = file_get_contents($ur);
                $res = json_decode($res, true);
                $jsapi_ticket = $res['ticket'];
                }

                $new_list = $jsapi_ticket;
                //更新缓存
                $memcache->set($memcache_key,$new_list,false,$mem_valid_time);
               
            } else {
                $jsapi_ticket = $mem_list;//缓存中的数据
            }
    
        }

        $timestamp = time();//生成签名的时间戳
        $metas = range(0, 9);
        $metas = array_merge($metas, range('A', 'Z'));
        $metas = array_merge($metas, range('a', 'z'));
        $nonceStr = '';
        for ($i=0; $i < 16; $i++) {
        $nonceStr .= $metas[rand(0, count($metas)-1)];//生成签名的随机串
        }

        $string1="jsapi_ticket=".$jsapi_ticket."&noncestr=".$nonceStr."&timestamp=".$timestamp."&url=".$url."";
        $signature=sha1($string1);
        $root['appid'] = $appid;
        $root['nonceStr'] = $nonceStr;
        $root['timestamp'] = $timestamp;
        $root['signature'] = $signature;

        echo json_encode($root);
    }


}
