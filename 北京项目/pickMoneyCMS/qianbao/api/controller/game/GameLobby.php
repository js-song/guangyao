<?php

/**
 * @Author: "Hulkzero"
 * @Date:   2018-09-08 09:48:23
 * @Email: "hulkzero@163.com"
 * @Last Modified time: 2018-11-01 16:44:13
 * @explain 游戏大厅和一些游戏基类
 */

namespace app\api\controller\game;

use app\api\controller\BaseController;
use app\api\controller\RedPacket;

use Tbk\top\TopClient;
use Tbk\top\request\TbkItemGetRequest;

use app\api\model\game\GamePrize;
use app\api\model\game\GameList;
// use app\api\model\game\GameAdList;
use app\api\model\game\AdvConfig;
use app\api\model\game\GameRedpacketList;
use app\api\model\game\GameRedpacket;
use app\api\model\game\GameCount;
use app\api\model\game\GameCountDetail;

use app\api\model\CMApiCount;
use app\api\model\game\ThirdGame;

use Think\Db;

use DES\QbDES;

class GameLobby extends BaseController
{	
	protected $m_GamePrize;														// 游戏中我的奖品
	protected $m_AdvConfig;													    // 游戏中广告模型
	protected $m_GameRedpacketList;												// 游戏中红包模型
	protected $m_GameRedpacket;
    protected $m_ThirdGame;                                                     // 第三方游戏
    protected $m_GameList;                                                      // 游戏列表
    protected $m_GameCountDetail;
    protected $m_GameCount;

	// 淘宝客安卓key值
	protected $tbkAndroidAppKey = '25019369' ;
	// 淘宝客安卓key值
	protected $tbkAndroidSecret = '07acc452161b7607507560530557deec' ;
	// 淘宝客iOSkey值
	protected $tbkIOSAppKey = '25018961' ;
	// 淘宝客iOSkey值
	protected $tbkIOSSecret = '974c416048a3e301c0c58918fefc6a57' ;

    public function __construct()
    {
        parent::__construct();
        $this->m_GamePrize 	= new GamePrize();									//实例化 我的奖品 模型
        $this->m_AdvConfig = new AdvConfig();									//实例化 广告 模型
        $this->m_GameRedpacketList = new GameRedpacketList();					//实例化 红包 模型
        $this->m_GameRedpacket = new GameRedpacket();							//实例化 我的红包 模型

        $this->m_GameCount 			= new GameCount();							//实例化 统计表 模型
        $this->m_GameCountDetail 	= new GameCountDetail();					//实例化 统计详情表 模型
        $this->m_ThirdGame          = new ThirdGame();                          //实例化第三方游戏 模型
        $this->m_GameList           = new GameList();                           //实例化游戏列表模型
    }
	
	/**
	 * 进入游戏首页，并统计和跳转
	 * @Author   Hulkzero
	 * @DateTime 2018-09-17T16:45:36+0800
	 * @Email    hulkzero@163.com
	 * @return   [type]                   [description]
	 */
	public function entry_game()
	{
		$request_param	  = $this->request_param;								//获取paramData中的值
        $uid 			  = $this->uid;											//获取用户id

		$g_id = isset($request_param['g_id']) 	? intval($request_param['g_id'])	: 0;
		//g_id大于100是第三方游戏，从数据库中获取返回
		if ($g_id > 100) {
		    $third_game_id = $g_id - 100;
            $third_game = $this->m_ThirdGame->getOneGame($third_game_id);
            $arr_data = [
                'g_url'	=>	$third_game['g_url'],
            ];

            if ($this->request_type == 1) {
                sdk_return($arr_data, 1, '获取成功');
            } else {
                web_return($this->auth, $arr_data, 1, '获取成功');
            }
        } else {
	        // 进行数据统计
	        // 点击游戏红包，游戏红包-1操作
	        $memcache = get_memcache();
	        // 为了和老版本中保持一致，所以使用原来的命名方式
	        $str_game_rp = 'redpacket' . 'game' . $uid;                               
	        $game_red_count = $memcache->get($str_game_rp);
	        $g_url = config('server_domain')."h5/game.game_lobby/loading.html?gid=".$g_id."&time=".md5(time().$g_id);
	        $arr_data = [
							'g_url'	=>	$g_url,
						];
			// 进行信息的统计
			$this->count_data_detail($uid, $g_id, 1);
	  		
	  		if ($this->request_type == 1) {
		        if ($game_red_count !== false && $game_red_count > 1) {
		            // 进行-1操作
		            $memcache->decrement($str_game_rp);
		        }
	        	sdk_return($arr_data, 1, '获取成功');   
	  		} else {
	  			web_return($this->auth, $arr_data, 1, '获取成功');
	  		}
        }
        // 输出结果
	}

	/**
	 * APP首页中趣味游戏进入统计
	 * @Author   Hulkzero
	 * @DateTime 2018-10-11T11:24:04+0800
	 * @Email    hulkzero@163.com
	 * @return   [type]                   [description]
	 */
	public function index_game_entry()
	{
		$request_param	  = $this->request_param;								//获取paramData中的值
        $uid 			  = $this->uid;											//获取用户id

		$g_id = 0; 
		// 进行信息的统计
		$this->count_data_detail($uid, $g_id, 1);
  		
  		if ($this->request_type == 1) {
        	sdk_return('', 1, '获取成功');   
  		} else {
  			web_return($this->auth, '', 1, '获取成功');
  		}
	}

	/**
	 * @Author   Hulkzero
	 * @DateTime 2018-10-11T16:05:56+0800
	 * @Email    hulkzero@163.com
	 * @return   [type]                   [description]
	 */
	public function count_app_ad()
	{
		$request_param	  = $this->request_param;								//获取paramData中的值
        $uid 			  = $this->uid;											//获取用户id
        
		// $g_id = 0; 
		// // 进行信息的统计
		// $this->count_data_detail($uid, $g_id, 1);
  		
  		if ($this->request_type == 1) {
        	sdk_return('', 1, '获取成功');   
  		} else {
  			web_return($this->auth, '', 1, '获取成功');
  		}
	}

	/**
	 * 15秒游戏入口出现次数
	 * @Author   Hulkzero
	 * @DateTime 2018-10-11T11:32:18+0800
	 * @Email    hulkzero@163.com
	 * @return   [type]                   [description]
	 */
	public function fifteen_appear_game()
	{
		$request_param	  = $this->request_param;								//获取paramData中的值
        $uid 			  = $this->uid;											//获取用户id

		$g_id = isset($request_param['g_id']) 	? intval($request_param['g_id'])	: 0; 
        
		// 进行信息的统计,4十五秒飞出游戏入口出现次数
		$this->count_data_detail($uid, $g_id, 4);
  		
  		if ($this->request_type == 1) {
        	sdk_return('', 1, '获取成功');   
  		} else {
  			web_return($this->auth, '', 1, '获取成功');
  		}
	}


	/**
	 * 15秒游戏入口点击次数
	 * @Author   Hulkzero
	 * @DateTime 2018-10-11T11:32:18+0800
	 * @Email    hulkzero@163.com
	 * @return   [type]                   [description]
	 */
	public function fifteen_entry_game()
	{
		$request_param	  = $this->request_param;								//获取paramData中的值
        $uid 			  = $this->uid;											//获取用户id

		$g_id = isset($request_param['g_id']) 	? intval($request_param['g_id'])	: 0; 
        
        if ($g_id == 4) {
        	$next_g_id = 1;
        } else {
        	$next_g_id = $g_id + 1;
        }

        $g_url = config('server_domain')."h5/game.game_lobby/loading.html?gid=".$next_g_id."&time=".md5(time().$next_g_id);
        $arr_data = [
						'g_url'	=>	$g_url,
					];
		// 进行信息的统计,15秒游戏入口点击次数5
		$this->count_data_detail($uid, $g_id, 5);  
  		
  		if ($this->request_type == 1) {
        	sdk_return($arr_data, 1, '获取成功');   
  		} else {
  			web_return($this->auth, $arr_data, 1, '获取成功');
  		}
	}


	/**
	 * APP游戏中奖品的基类
	 * 
	 * @Author   Hulkzero
	 * @DateTime 2018-09-08T09:51:46+0800
	 * @Email    hulkzero@163.com
	 * @param    integer                  $game_type [description]
	 * @return   [type]                              [description]
	 */
	public function get_game_prize_list()
	{	
		$request_param	  = $this->request_param;											//获取paramData中的值
        $uid 			  = $this->uid;														//获取用户id

		$uid  = isset($uid) ? intval($uid) : 0;				
		$g_id = isset($request_param['g_id']) 	? intval($request_param['g_id'])	: 1;
		$page = isset($request_param['page']) 	? intval($request_param['page'])	: 1;
		$now_time = get_time();
		// 根据用户ID和游戏类型去数据表进行奖品信息的查询,初始为1页,每页显示20条
		$t_tablename = get_db_table_name('game_prize', $uid); 								//获取分表表名
  		$this->m_GamePrize->setTableName($t_tablename);        								//设置表名

		$prize_data = $this->m_GamePrize->getMyPrizeList($uid, $g_id, $page, $now_time);
		// 重新拼接数据
		$output_prize_data = [];
		foreach($prize_data as $v){
			// 根据 广告 和 红包 在 各自表中获取单个数据
			if ($v['p_type'] == 1) {														//获取广告的信息
				// $table = get_db_table_name('adv_config');
				$table = 'b_adv_config';
				$res = $this->m_GamePrize->getPrizeInform($table, $v['p_id']);

		    	$new_v['p_id'] 			= 	$v['id'];										//奖品id
			    $new_v['p_title'] 		= 	$res['ad_title'];								//奖品标题
			    $new_v['p_img'] 		= 	$res['ad_img'];									//奖品图片

		    	$memcache = get_memcache();
		    	$user_data = $memcache->get('user_data_' . $uid);
		    	$login_type = $user_data['login_type'];
		    	if ($login_type == 1) {  //安卓
		    		$new_v['p_url'] 		= 	$res['ad_url1'];	
		    	} elseif ($login_type == 2) {
		    		$new_v['p_url'] 		= 	$res['ad_url2'];
		    	} else {
		    		$new_v['p_url'] 		= 	$res['ad_url1'];
		    	}

			    $new_v['p_valid_time'] 	= 	date('Y-m-d H:i:s', $v['addtime']);				//有效时间
			    $new_v['p_type'] 		= 	$v['p_type'];									//类型 1广告，2红包
			    $new_v['p_status'] 		= 	$v['p_status'];									//状态 1未领取，2领取 ，3失效
			
			} elseif($v['p_type'] == 2) {													//获取红包的信息
				$table = get_db_table_name('game_redpacket', $uid); 						//获取分表表名	
				$res = $this->m_GamePrize->getPrizeInform($table, $v['p_id']);

				$new_v['p_id'] 			=	$v['id'];										//奖品id
			    $new_v['p_title'] 		= 	$res['red_money'].'元红包';						//奖品标题
			    $new_v['p_img'] 		= 	config('server_domain').'static/images/Lottery/ad/hongb.png';									//奖品图片
			    $new_v['p_url'] 		= 	'http://www.yuanmakj.com';						//奖品链接地址
			    $new_v['p_valid_time'] 	= 	date('Y-m-d H:i:s', $v['addtime']);;			//有效时间
			    $new_v['p_type'] 		= 	$v['p_type'];									//类型 1广告，2红包
			    $new_v['p_status'] 		= 	$v['p_status'];									//状态 1未领取，2领取 ，3失效
			    
			} else {
        		sdk_return('', 3, '获取失败');
			}		

			$output_prize_data[] = $new_v;
		}
		$arr_data = [
						'prize_data'	=>	$output_prize_data
					];
        // 输出结果
        sdk_return($arr_data, 1, '获取成功');
	}

	/**
	 * APP获取游戏奖品红包详情接口
	 * @Author   Hulkzero
	 * @DateTime 2018-09-08T13:52:36+0800
	 * @Email    hulkzero@163.com
	 * @param    integer             $itemid     	奖品的ID
	 * @param    int                 $prize_type 	奖品的类别
	 * @return   array                              奖品详情
	 */
	public function get_redpacket_detail()
	{
		$request_param	  = $this->request_param;											//获取paramData中的值
        $uid 			  = $this->uid;														//获取用户id
		$p_id   = isset($request_param['p_id']) 	? intval($request_param['p_id'])		: 1;
		
		// 根据奖品id，获取详细信息
		$t_tablename = get_db_table_name('game_prize', $uid); 											//获取分表表名
  		$prize_info = $this->m_GamePrize->getPrizeInform($t_tablename, $p_id);
  		if (empty($prize_info)) {
  			sdk_return('', 3, '红包已失效');
  		}
  		if ($prize_info['p_type'] == 2 && $prize_info['p_status'] == 1) {																//红包
  			$red_tablename = get_db_table_name('game_redpacket', $uid); 								//获取分表表名
  			$prize_redpacket_info = $this->m_GamePrize->getPrizeInform($red_tablename, $prize_info['p_id']);

  			$money = $prize_redpacket_info['red_money']/9 + $prize_redpacket_info['red_money'];			
            $c_RedPacket = new RedPacket();
			$res = $c_RedPacket->getUserStockPrice($uid, 6, $p_id, $money);	
			// 影响股价
			if ($res) {
				
	  			// 把股数存储到数据表
	  			$update_where = [
	  								'id' => $prize_redpacket_info['id'],
	  							];
	  			$update_param = [
	  								'red_stock'		=> $res['num_a'],
	  								'red_status' 	=>	2												//1未领取，2领取，3过期
	  							];
	  			$this->m_GamePrize->updateMyPrize($red_tablename, $update_where, $update_param);
				// 更新game_prize中的状态
				$game_prize_where = [
	  									'id' => $p_id,
	  								];
	  			$game_prize_param = [
	  									'p_status' 	=>	2,												//1未领取，2领取，3过期
	  								];					
	  			$this->m_GamePrize->updateMyPrize($t_tablename, $game_prize_where, $game_prize_param);
			} else {
				$arr_data = [
						'avatar'				=>	0,	
						'one_redpacket_money'	=>	0,
						'one_redpacket_stock'	=>	0
					];
				sdk_return($arr_data, 1, '领取失败');
			}
  		}

		// 根据红包id，在红包领取表中查询详细信息
		$table = get_db_table_name('game_redpacket', $uid); 								//获取分表表名	
		$res_redpacket = $this->m_GamePrize->getPrizeInform($table, $prize_info['p_id']);
		
		$avatar = get_user_avatar($uid, 2);
	
		$arr_data = [
						'avatar'				=>	$avatar,	
						'one_redpacket_money'	=>	$res_redpacket['red_money'],
						'one_redpacket_stock'	=>	$res_redpacket['red_stock']
					];
		// 定义成功状态语句
		$msg = '获取成功';	
        // 输出结果
        sdk_return($arr_data, 1, $msg);
	}	

	/**
	 * APP获取淘宝客商品列表
	 * @Author   Hulkzero
	 * @DateTime 2018-09-08T14:47:49+0800
	 * @Email    hulkzero@163.com
	 * @return   [type]                   [description]
	 */
	public function get_tbk_list($serch_keyword = '')
	{	
		// 初始化淘宝客
		include "../extend/Tbk/TopSdk.php";
		// 获取传递的数据
		$request_param 	= $this->request_param;
        $uid 			= $this->uid;
        $device_type	= isset($request_param['device_type'])	? intval($request_param['device_type']) : 0;	//设备类型
        $g_id 			= isset($request_param['g_id']) 		? intval($request_param['g_id']) 		: 0;	//游戏id
        $page 			= isset($request_param['page']) 		? intval($request_param['page']) 		: 1;	//第几页

        // 进行信息的统计,3再来一次的次数
		$this->count_data_detail($uid, $g_id, 3);

		/* 淘宝客数据返回 */
        //进行设备类型判断 1安卓 ，2ios
        if ($device_type == 1) {
            $appkey = $this->tbkAndroidAppKey;
            $secretKey = $this->tbkAndroidSecret;
        } elseif ($device_type == 2) {
            $appkey = $this->tbkIOSAppKey;
            $secretKey = $this->tbkIOSSecret;
        } else {
            // 暂时先返出这个
            $appkey = $this->tbkIOSAppKey;
            $secretKey = $this->tbkIOSSecret;
        }
        // 安卓暂时有问题，先使用ios，后期换2018.09.21
        // 暂时先返出这个
        $appkey = $this->tbkIOSAppKey;
        $secretKey = $this->tbkIOSSecret;

        $c = new \Tbk\top\TopClient();

        $c->appkey = $appkey;							//设置key值
        $c->secretKey = $secretKey;						//设置secret值

        $req = new \Tbk\top\request\TbkItemGetRequest();

        // 设置返回字段
        $req->setFields("num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url,seller_id,volume,nick");
        $req->setQ("手机壳");								//查询词 根据关键字进行查找，比如 $serch_keyword = 男装
        $req->setSort("tk_rate_des");					//des（降序），asc（升序），销量（total_sales），淘客佣金比率（tk_rate）
        $req->setPageNo($page);							//第几页 根据关键字获取淘宝客商品，默认是20条
        $req->setIsTmall("false");						//否商城商品，设置为true表示该商品是属于淘宝商城商品，设置为false或不设置表示不判断这个属性
        $resp = $c->execute($req);

        // 只有次数用完后才可以进入淘宝客看广告后，可以再玩一次游戏
        // 先判断缓存中可以用来抽奖的次数
		$redis = get_memcache();
		$now_time = get_time();																	//定义当前时间
		$redis_day = date('Y_m_d', $now_time);
		$redis_game_arr =  'redis_game_num_'.$redis_day.'_'.$uid.'_'.$g_id;						//定义缓存名称

        if (sys_ver() == 3) {
            $redis_valid_time = 60 * 60 * 24;
            $game_valid_time = 60 * 60 * 24;
        } else {
            $redis_valid_time = 60 * 20;            // 测试失效 Jason
            $game_valid_time = 60 * 20;             // 测试-有效期设置为10分钟 Jason 2018-10-30
        }

		// 已经存在，判断次数，为0时表示次数用完
		$get_redis_arr = $redis->get($redis_game_arr);
        //缓存中继续游戏的次数

		$tody_day = date('Ymd', $now_time);
        $game_again_key = 'game_again_'.$tody_day.'_'.$uid;
        $hava_again_game = $redis->get($game_again_key);

        //继续玩游戏次数
        $curr_game_again_count = $hava_again_game[$g_id]['again_count'];
		//缓存中有继续玩游戏的设置并且为0次时间
        if ($curr_game_again_count <= 0) {//继续玩游戏次数用完
            sdk_return($resp, 1, '再玩一次次数用完');
        } else {
            //继续玩游戏-1操作
            $hava_again_game[$g_id]['again_count']    = $curr_game_again_count - 1;
//            $hava_again_game[$g_id]['once_count']     = 1;//还能玩1次
            //更新对应游戏继续玩游戏次数
            $redis->set($game_again_key,$hava_again_game,false,$game_valid_time);
        }

        $now_count_down = $get_redis_arr['end_count_down_time'] - $now_time;
		$start_count_down_time = $get_redis_arr['start_count_down_time'];
		$end_count_down_time = $get_redis_arr['end_count_down_time'];
		$redis_arr_data = [
								'num' 					=>	1,							//初始化游戏次数
								'count_down_status'		=>	1,							//1倒计时，2不倒计时
								'count_down'			=>	$now_count_down,			//没有倒计时为0秒
								'start_count_down_time'	=>	$start_count_down_time,		//初始计时开始时间
								'end_count_down_time'	=>	$end_count_down_time,		//初始计时结束时间
							];

		$redis->set($redis_game_arr, $redis_arr_data, false, $redis_valid_time);		//重新设置redis的值

		// 定义输出
        sdk_return($resp, 1, '获取成功');
	}

	/**--------------------------下面是Web的方法操作------------------------------**/ 

	/**
	 * Web判断游戏可以使用的次数
	 * @Author   Hulkzero
	 * @DateTime 2018-09-10T17:15:25+0800
	 * @Email    hulkzero@163.com
	 * @return   [type]                   返回次数
	 */
	public function judge_game_num()
	{	
		// 获取传递的数据
		$request_param 	= $this->request_param;
        $uid 			= $this->uid;
        $g_id			= isset($request_param['game_type']) ?intval($request_param['game_type']) : 0;
        //judge_game_num_detail($uid, $g_id, $is_popout = 1, $is_down = 0, $status = 1, $is_prize = 0)
		$arr_data = $this->judge_game_num_detail($uid, $g_id, 1, 0, 1, 1);
		web_return($this->auth, $arr_data, 1, '获取成功');
	}

	/**
	 * 页面刷新，不弹框
	 * @Author   Hulkzero
	 * @DateTime 2018-09-22T18:16:12+0800
	 * @Email    hulkzero@163.com
	 * @return   [type]                   [description]
	 */
	public function get_refresh()
	{
		// 获取传递的数据
		$request_param 	= $this->request_param;
        $uid 			= $this->uid;
        $g_id			= isset($request_param['game_type']) ?intval($request_param['game_type']) : 0;

		$arr_data = $this->judge_game_num_detail($uid, $g_id, 2);
		web_return($this->auth, $arr_data, 1, '获取成功');
	}

	/**
	 * 存储 领取的奖品
	 * @Author   Hulkzero
	 * @DateTime 2018-09-20T15:14:36+0800
	 * @Email    hulkzero@163.com
	 * @return   [type]                   [description]
	 */
	public function receice_award()
	{
		// 获取传递的数据
		$request_param 	= $this->request_param;
        $uid 			= $this->uid;
        $p_id			= isset($request_param['p_id']) 	?	intval($request_param['p_id']) 	: 0;	//奖品的id
        // 根据奖品id，获取详细信息
		$t_tablename = get_db_table_name('game_prize', $uid); 											//获取分表表名
  		$prize_info = $this->m_GamePrize->getPrizeInform($t_tablename, $p_id);
  		if ($prize_info['p_type'] == 2) {																//红包
  			$red_tablename = get_db_table_name('game_redpacket', $uid); 								//获取分表表名
  			$prize_redpacket_info = $this->m_GamePrize->getPrizeInform($red_tablename, $prize_info['p_id']);
  			$money = $prize_redpacket_info['red_money']/9 + $prize_redpacket_info['red_money'];			
            $c_RedPacket = new RedPacket();
			$res = $c_RedPacket->getUserStockPrice($uid, 6, $p_id, $money);	
			// 影响股价
			if ($res) {
				
	  			// 把股数存储到数据表
	  			$update_where = [
	  								'id' => $prize_redpacket_info['id'],
	  							];
	  			$update_param = [
	  								'red_stock'		=> $res['num_a'],
	  								'red_status' 	=>	2												//1未领取，2领取，3过期
	  							];
	  			$this->m_GamePrize->updateMyPrize($red_tablename, $update_where, $update_param);
				// 更新game_prize中的状态
				$game_prize_where = [
	  									'id' => $p_id,
	  								];
	  			$game_prize_param = [
	  									'p_status' 	=>	2,												//1未领取，2领取，3过期
	  								];					
	  			$this->m_GamePrize->updateMyPrize($t_tablename, $game_prize_where, $game_prize_param);
				web_return($this->auth, '', 1, '领取成功');
			} else {
				web_return($this->auth, '', 0, '领取失败');
			}	
  		} else {
  			web_return($this->auth, '', 0, '领取失败');
  		}
	}


	/**
	 * 判断游戏次数缓存的方法
	 * @Author   Hulkzero
	 * @DateTime 2018-09-27T21:09:04+0800
	 * @Email    hulkzero@163.com
	 * @param 	 int 		$g_id 		  游戏id
	 * @param 	 int 		$is_popout	  是否展示广告，1展示，2不展示
	 * @param 	 int 		$is_down      是否进行倒计时，1倒计时，2不倒计时
	 * @return   [type]                   [description]
	 */
	public function judge_game_num_detail($uid, $g_id, $is_popout = 1, $is_down = 0, $status = 1, $is_prize = 0)
	{
		//先判是否有游戏配置缓存Jason 2018-10-28
        $game_conf_mem = get_memcache();
        $game_now_time = get_time();
        $game_mem_day = date('Ymd', $game_now_time);

        $game_conf_key = 'game_once_'.$game_mem_day.'_'.$uid;
        $game_again_key = 'game_again_'.$game_mem_day.'_'.$uid;

        if (sys_ver() == 3) {
            $game_again_times_valid_time = 60 * 60 * 24;    // 继续游戏缓存时间24小时
            $game_valid_time = 60 * 60 * 3;                 // 游戏次数缓存时间3小时
        } else {
            $game_again_times_valid_time = 60 * 20;
            $game_valid_time = 60 * 10;                     // 测试-有效期设置为10分钟 Jason 2018-10-30
        }

        $have_game_again_data = $game_conf_mem->get($game_again_key);//游戏继续玩次数缓存数据
        $have_game_times_data = $game_conf_mem->get($game_conf_key);//游戏次数

        if (empty($have_game_times_data) || $have_game_times_data['exp_time'] < $game_now_time) {//游戏次数重置
            $game_conf_res = $this->m_GameList->getGameConfig();
            $game_new_conf = [];
            foreach ($game_conf_res as $k=>$v) {
                $game_new_conf[$v['id']] = ['once_count'=>$v['once_count']];
            }
            //游戏次数过期时间
            $game_new_conf['exp_time'] = $game_valid_time + $game_now_time;
            //设置游戏次数缓存
            $game_conf_mem->set($game_conf_key,$game_new_conf,false,$game_valid_time);
            //设置完缓存从缓存中获取
            $game_have_times = $game_conf_mem->get($game_conf_key);
            $game_num = $game_have_times[$g_id]['once_count'];
            $have_game_times_data = $game_have_times ;
        } else {
            //有缓存读取游戏次数数据
            //游戏次数
            $game_have_times = $game_conf_mem->get($game_conf_key);
            $game_num = $have_game_times_data[$g_id]['once_count'];
        }

        /**
         * 游戏继续玩次数缓存
         **/
        if (empty($have_game_again_data) || $have_game_again_data['exp_time'] < $game_now_time ) {
            $game_conf_res = $this->m_GameList->getGameConfig();
            $game_again_conf = [];
            foreach ($game_conf_res as $kk=>$vv) {
                $game_again_conf[$vv['id']] = ['again_count'=>$vv['again_count']];
            }
            //继续玩游戏次数过去时间
            $game_again_conf['exp_time'] = $game_again_times_valid_time + $game_now_time;
            //设置继续玩游戏次数缓存
            $game_conf_mem->set($game_again_key,$game_again_conf,false,$game_again_times_valid_time);
        }

        //Jason新增

	    // 传递游戏的类别，根据用户名和类别在缓存中进行查找
		// 先判断缓存中可以用来抽奖的次数
		$redis = get_memcache();
		$now_time = get_time();																		//定义当前时间
		$redis_day = date('Y_m_d', $now_time);
		$redis_game_arr =  'redis_game_num_'.$redis_day.'_'.$uid.'_'.$g_id;							//定义缓存名称

        if (sys_ver() == 3) {
            $redis_valid_time = 60 * 60 * 3;   // 设置缓存有效时间
            $count_down = 60 * 60 * 3;         // 设置倒计时
        } else {
            $redis_valid_time = 60 * 10;        // 测试失效时间Jason
            $count_down = 60 * 10;          // 测试设置倒计时
        }

//		$game_num = 8;

		$redis_end_time = $now_time + $redis_valid_time;											//redis失效时间

		if ($is_popout == 1) {
			// 定义进入页面 弹出框 信息
			$popout_result_arr 	= $this->get_entry_game_popout($uid, $g_id, $status, $is_prize);
			$entry_game_popout 	= $popout_result_arr['entry_game_popout'];
			$result 			= $popout_result_arr['result'];
			$result_ad_id 		= $popout_result_arr['result_ad_id'];
		}
		

		if ($redis->get($redis_game_arr) !== false) {												//A非初次进入游戏
			// 已经存在，判断次数，为0时表示次数用完
			$get_redis_arr = $redis->get($redis_game_arr);
				// var_dump(json_encode(intval($get_redis_arr['num'])));
			if (intval($get_redis_arr['num']) > 0) {												//B1有游戏次数，不计时
				if ($is_down == 1) {
					// 对次数进行-1操作
					$now_num = $get_redis_arr['num'] - 1;
					if ($now_num == 0) {															//设定倒计时
						// 如果之前进行了倒计时，继续进行倒计时
						if ($get_redis_arr['count_down_status'] == 1) {
							$now_count_down 		= $get_redis_arr['end_count_down_time'] - $now_time;
							$start_count_down_time 	= $get_redis_arr['start_count_down_time'];
							$end_count_down_time	= $get_redis_arr['end_count_down_time'];
							$count_down_status		= 1;
							if ($now_count_down <= 0) {
								$now_num 				= $game_num;
								$count_down_status 		= 2;
								$now_count_down			= 0;
								$start_count_down_time 	= 0;
								$end_count_down_time 	= 0;
							}
							$redis_arr_data = [
												'num' 					=>	$now_num,					//初始化游戏次数
												'count_down_status'		=>	$count_down_status,			//1倒计时，2不倒计时
												'count_down'			=>	$now_count_down,			//倒计时
												'start_count_down_time'	=>	$start_count_down_time,		//计时开始时间
												'end_count_down_time'	=>	$end_count_down_time,		//计时结束时间
											];
						} else {
							$redis_arr_data = [
												'num' 					=>	$now_num,					//初始化游戏次数
												'count_down_status'		=>	1,							//1倒计时，2不倒计时
												'count_down'			=>	$count_down,				//没有倒计时为0秒
												'start_count_down_time'	=>	$now_time,					//初始计时开始时间
												'end_count_down_time'	=>	$now_time+$count_down,		//初始计时结束时间
											];
						}
					} else {
						$redis_arr_data = [
											'num' 					=>	$now_num,					//初始化游戏次数
											'count_down_status'		=>	2,							//1倒计时，2不倒计时
											'count_down'			=>	0,							//没有倒计时为0秒
											'start_count_down_time'	=>	0,							//初始计时开始时间
											'end_count_down_time'	=>	0,							//初始计时结束时间
										];
					}
					$redis->set($redis_game_arr, $redis_arr_data, false, $redis_valid_time);
                    //更新缓存游戏次数,新增Jason
                    $have_game_times_data[$g_id]['once_count']  = $redis_arr_data['num'];
                    $game_conf_mem->set($game_conf_key, $have_game_times_data, false, $game_valid_time);

				} else {
					if ($get_redis_arr['count_down_status'] == 1) {
						$now_num				= $get_redis_arr['num'];
						$now_count_down 		= $get_redis_arr['end_count_down_time'] - $now_time;
						$start_count_down_time 	= $get_redis_arr['start_count_down_time'];
						$end_count_down_time	= $get_redis_arr['end_count_down_time'];
						$count_down_status		= 1;
						if ($now_count_down <= 0) {
							$now_num 				= $game_num;
							$count_down_status 		= 2;
							$now_count_down			= 0;
							$start_count_down_time 	= 0;
							$end_count_down_time 	= 0;
						}
						$redis_arr_data = [
											'num' 					=>	$now_num,					//初始化游戏次数
											'count_down_status'		=>	$count_down_status,			//1倒计时，2不倒计时
											'count_down'			=>	$now_count_down,			//倒计时
											'start_count_down_time'	=>	$start_count_down_time,		//计时开始时间
											'end_count_down_time'	=>	$end_count_down_time,		//计时结束时间
										];
						$redis->set($redis_game_arr, $redis_arr_data, false, $redis_valid_time);
                        //更新缓存游戏次数,新增Jason
                        $have_game_times_data[$g_id]['once_count']  = $redis_arr_data['num'];
                        $game_conf_mem->set($game_conf_key, $have_game_times_data, false, $game_valid_time);
					}
				}

				$get_redis_arr = $redis->get($redis_game_arr);

				$arr_data = [																		//组合redis的值
							'num'  				=>  $get_redis_arr['num'],
							'count_down'		=>	$get_redis_arr['count_down'],					//倒计时
							'count_down_status' =>	$get_redis_arr['count_down_status'],			//1倒计时，2不倒计时
						];

				if ($is_popout == 1) {
					$arr_data = [																	//组合redis的值
									'p_id'				=>	$result,								//奖品列表中的id
									'p_ad_id'			=>	$result_ad_id,
									'entry_game_popout' =>  $entry_game_popout,
									'num'  				=>  $get_redis_arr['num'],
									'count_down'		=>	$get_redis_arr['count_down'],			//倒计时
									'count_down_status' =>	$get_redis_arr['count_down_status'],	//1展示，2不展示
								];
				}
                //游戏次数Jason
                $arr_data['game_times'] = $game_conf_mem->get($game_conf_key);
                //继续游戏次数Jason
                $arr_data['game_again_times'] = $game_conf_mem->get($game_again_key);
				return $arr_data;
			} else {																				//B2次数用尽
				if ($get_redis_arr['count_down_status'] == 1 && $get_redis_arr['end_count_down_time'] > $now_time) {								   		//B2->C1 有倒计时
					$now_count_down 		= $get_redis_arr['end_count_down_time'] - $now_time;
					$start_count_down_time 	= $get_redis_arr['start_count_down_time'];
					$end_count_down_time 	= $get_redis_arr['end_count_down_time'];
					$now_num 				= $get_redis_arr['num'];
					$redis_arr_data = [
											'num' 					=>	$now_num ,					//初始化游戏次数
											'count_down_status'		=>	1,							//1倒计时，2不倒计时
											'count_down'			=>	$now_count_down,			//没有倒计时为0秒
											'start_count_down_time'	=>	$start_count_down_time,		//初始计时开始时间
											'end_count_down_time'	=>	$end_count_down_time,		//初始计时结束时间
										];

					$count_down_status 	= 	$get_redis_arr['count_down_status'];						
					$re_num 			= 	$now_num;
					$msg 				= 	'您的次数已用尽，请稍后再试';
				} else {																			//B2->C1 倒计时已经结束，进行初始化
					// 进行缓存的初始化设置,有效期24小时,次数8次, 游戏次数在数据库读取，测试阶段写死方便测试
					$redis_arr_data = [
											'num' 					=>	$game_num,					//初始化游戏次数
											'count_down_status'		=>	2,							//1倒计时，2不倒计时
											'count_down'			=>	0,							//没有倒计时为0秒
											'start_count_down_time'	=>	0,							//初始计时开始时间
											'end_count_down_time'	=>	0,							//初始计时结束时间
										];

					$now_count_down 	= 0;
					$count_down_status 	= 2;
					$re_num 			= $game_num;
					$msg 				= '欢迎再来8次';
				}
				$redis->set($redis_game_arr, $redis_arr_data, false, $redis_valid_time);
                //更新缓存游戏次数,新增Jason
                $have_game_times_data[$g_id]['once_count']  = $redis_arr_data['num'];
                $game_conf_mem->set($game_conf_key, $have_game_times_data, false, $game_valid_time);

				$arr_data = [																		//组合redis的值
							'num'  				=>  $re_num,
							'count_down'		=>	$now_count_down,								//倒计时
							'count_down_status' =>	$count_down_status,								//1倒计时，2不倒计时
						];
				if ($is_popout == 1) {
					// 表示次数用完
					$arr_data = [
									'p_id'				=>	$result,								//奖品列表中的id
									'p_ad_id'			=>	$result_ad_id,
									'entry_game_popout' =>  $entry_game_popout,
									'num'				=>	$re_num,								//剩余次数
									'count_down'		=>	$now_count_down,						//倒计时
									'count_down_status' =>	$count_down_status,						//1倒计时，2不倒计时
								];
				}
                //游戏次数Jason
                $arr_data['game_times'] = $game_conf_mem->get($game_conf_key);
				//继续游戏次数Jason
                $arr_data['game_again_times'] = $game_conf_mem->get($game_again_key);
				return $arr_data;
			}
		} else {																					//①当天首次,进行初始化
			// 进行缓存的初始化设置,有效期24小时,次数8次, 游戏次数在数据库读取，测试阶段写死方便测试
			$redis_arr_data = [
									'num' 					=>	$game_num,							//初始化游戏次数
									'count_down_status'		=>	2,									//1倒计时，2不倒计时
									'count_down'			=>	0,									//没有倒计时为0秒
									'start_count_down_time'	=>	0,									//初始计时开始时间
									'end_count_down_time'	=>	0,									//初始计时结束时间
								];
			$redis->set($redis_game_arr, $redis_arr_data, false, $redis_valid_time);
            //更新缓存游戏次数,新增Jason
            $game_have_times[$g_id]['once_count']  = $redis_arr_data['num'];
            $game_conf_mem->set($game_conf_key, $game_have_times, false, $game_valid_time);

			$arr_data = [																			//组合redis的值
							'num'  				=>  $game_num,
							'count_down'		=>	0,												//倒计时
							'count_down_status' =>	2,												//1倒计时，2不倒计时
						];
			
			if ($is_popout == 1) {
				$arr_data = [																		//组合redis的值
								'p_id'				=>	$result,									//奖品列表中的id
								'p_ad_id'			=>	$result_ad_id,
								'entry_game_popout' =>  $entry_game_popout,
								'num'  				=>  $game_num,
								'count_down'		=>	0,											//倒计时
								'count_down_status' =>	2,											//1倒计时，2不倒计时
							];
			}

			//游戏次数Jason
            $arr_data['game_times'] = $game_conf_mem->get($game_conf_key);
			//继续游戏次数Jason
            $arr_data['game_again_times'] = $game_conf_mem->get($game_again_key);
			// 定义输出
			return $arr_data;
		}
	}

	/**
	 * 存储广告和红包的信息，并返回结果集
	 * @Author   Hulkzero
	 * @DateTime 2018-09-27T21:15:06+0800
	 * @Email    hulkzero@163.com
	 * @return   [type]                   [description]
	 */
	public function get_entry_game_popout($uid, $g_id, $status = 1, $is_prize = 0)
	{
		// 定义进入页面弹出框
		$entry_game_popout = $this->entry_game_popout($uid, $is_prize, $g_id);

		if ($entry_game_popout['tips_type'] == 1) {											//广告
			$ad_id = $entry_game_popout['ad_id'];
			$result = $this->save_my_uncollect_ad($uid, $g_id, $ad_id, $status);			//广告存入数据表
			$result_ad_id = 0;
		} else {																			//广告+红包
			$r_id	= $entry_game_popout['r_id'];											
			$red_money	= $entry_game_popout['red_money'];	
			$ad_id = $entry_game_popout['ad_id'];
			$result_ad_id = $this->save_my_uncollect_ad($uid, $g_id, $ad_id, $status);		//广告存入数据表
			$result = $this->save_my_uncollect_redpacket($uid, $g_id, $r_id, $red_money, $status);	//红包存入数据表
		}

		$arr_data = [
						'entry_game_popout' => 	$entry_game_popout,
						'result'			=>	$result,
						'result_ad_id'		=>	$result_ad_id
					];

		return $arr_data;
	}

	/**
	 * 将领到的广告 或者 广告+ 红包存储到数据库
	 * @Author   Hulkzero
	 * @DateTime 2018-09-21T11:17:32+0800
	 * @Email    hulkzero@163.com
	 * @return   结果集                   [返出结果集]
	 */
	public function save_my_uncollect_ad($uid, $g_id, $ad_id, $status = 1)
	{
		// 将奖品信息存储到用户对应的奖品表
		$t_tablename = get_db_table_name('game_prize', $uid); 							//获取分表表名
  		$this->m_GamePrize->setTableName($t_tablename);        							//设置表名
  		// 定义添加的数组
  		$save_data = [
  						'uid'			=>	$uid,
  						'p_id'			=>	$ad_id,
  						'g_id'			=>	$g_id,	
  						'p_type'		=>	1,											// 1广告
  						'p_status'		=>	$status,									// 1未领取
  						'p_valid_time'	=> 	get_time() + 60*60*24*3,
  						'addtime'		=> 	get_time()
  					];
  		// $res = $this->m_GamePrize->saveMyPrize($save_data);
  		$res = $this->m_GamePrize->insertInfo($save_data);								//获取插入id
  		
  		if ($res) {
  			return $res;
  		} else {
  			return false;
  		}	
	}

	/**
	 * 将领取到的游戏红包 存放数据表
	 * @Author   Hulkzero
	 * @DateTime 2018-09-21T14:12:13+0800
	 * @Email    hulkzero@163.com
	 * @return   结果集                   [存储成功还是失败]
	 */
	public function save_my_uncollect_redpacket($uid, $g_id, $r_id, $red_money, $status = 1)
	{
		$now_time = get_time();
		// 将奖品信息存储到用户对应的奖品表
		$t_tablename = get_db_table_name('game_redpacket', $uid); 						//获取分表表名
  		$this->m_GameRedpacket->setTableName($t_tablename);        						//设置表名
  		// 定义添加的数组
  		$save_data = [
						'r_id'				=>	$r_id,
						'uid'				=>	$uid,
						'red_money'			=>	$red_money,	
						'red_valid_time'	=> 	$now_time + 60*60*24*3,
						'red_status'		=>	$status,								// 1未领取
						'addtime'			=> 	$now_time
					];
  		// $this->m_GameRedpacket->saveMyGameRedpacket($save_data);						//存储到b_game_redpacket_u分表
  		$red_p_id = $this->m_GameRedpacket->insertInfo($save_data);						//获取插入id
  		// 定义数组,然后将数据插入到奖品表
  		$t_tablename = get_db_table_name('game_prize', $uid); 							//获取分表表名
  		$this->m_GamePrize->setTableName($t_tablename);        							//设置表名
  		// 定义添加的数组
  		$save_prize_data = [
	  						'uid'			=>	$uid,
	  						'p_id'			=>	$red_p_id,
	  						'g_id'			=>	$g_id,	
	  						'p_type'		=>	2,										// 1广告,2广告+红包
	  						'p_status'		=>	$status,								// 1未领取
	  						'p_valid_time'	=> 	$now_time + 60*60*24*3,
	  						'addtime'		=> 	$now_time
	  					];
  		// $res = $this->m_GamePrize->saveMyPrize($save_prize_data);
  		$res = $this->m_GamePrize->insertInfo($save_prize_data);						//获取插入id

  		if ($res) {
  			return $res;
  		} else {
  			return false;
  		}
	}



	/**
	 * 获取弹窗广告
	 * @Author   Hulkzero
	 * @DateTime 2018-09-13T15:34:41+0800
	 * @Email    hulkzero@163.com
	 * @return   [type]                   [description]
	 */
	public function entry_game_popout($uid = 0, $is_prize = 0, $game_id= 0)
	{
		// 根据后台的规则，弹出红包或者红包+广告
		//$prize_type = $this->ad_redpacket_popout();             // 弹出广告或者红包+广告
		$prize_type = $this->ad_redpacket_popout($game_id);     // 根据游戏ID获取弹出广告或者红包+广告的配置 Jason 2018-11-14 add

		$get_ad_popout = $this->get_game_ad_popout($uid, $game_id);									//弹出哪个广告
		//分为：小红包、大红包、和超级红包。小红包占比80%，金额浮动区间为：0.01-1元，
		//大额红包占比19%，金额浮动区间为：1-5元，超级红包：占比1%，金额浮动区间为：10-20元。

    	$memcache = get_memcache();
    	$user_data = $memcache->get('user_data_' . $uid);
    	$login_type = $user_data['login_type'];
    	if ($login_type == 1) {  //安卓
    		$get_ad_popout['ad_url'] 		= 	$get_ad_popout['ad_url1'];	
    	} elseif ($login_type == 2) {
    		$get_ad_popout['ad_url'] 		= 	$get_ad_popout['ad_url2'];
    	} else {
    		$get_ad_popout['ad_url'] 		= 	$get_ad_popout['ad_url1'];
    	}

		if (!empty($is_prize)) {
			$prize_type = 1;
		}
		if ($prize_type == 1) {//广告   1广告，2广告+红包,3广告+游戏机会
			// 数据库获取广告
			// 获取列表，每次均不一样,先弹出广告(随机和权重)，后是题目						
			$res = 	[
						'ad_id'				=>	$get_ad_popout['id'],
//						'ad_title'			=>	$get_ad_popout['ad_title'],
						'ad_title'			=>	!empty($get_ad_popout['ad_info']) ? $get_ad_popout['ad_info'] : '恭喜中奖，限时领取',
						'ad_img'			=>	$get_ad_popout['ad_img'],
						'ad_url'			=>	$get_ad_popout['ad_url'],
						'tips_type'			=>	1
					];		
		} else {																		//广告加红包
			// 数据库获取广告和红包 
			$redpacket_arr = $this->get_one_redpacket_money();
			//2018-09-26标注：红包存在的时候就分走了10%，在开的时候给股池
			$redpacket_money = $redpacket_arr['mt_rand'] * 0.9;
			$r_id = $redpacket_arr['r_id'];
			$res = 	[	
						'ad_id'				=>	$get_ad_popout['id'],
//						'ad_title'			=>	$get_ad_popout['ad_title'],
                        'ad_title'			=>	!empty($get_ad_popout['ad_info']) ? $get_ad_popout['ad_info'] : '恭喜中奖，限时领取',
						'ad_img'			=>	$get_ad_popout['ad_img'],
						'ad_url'			=>	$get_ad_popout['ad_url'],
						'r_id'				=>	$r_id,
						'red_money' 		=> 	$redpacket_money,
						'redpacket_money'	=>	$redpacket_money.'元红包',
						// 'redpacket_img'		=>	config('server_domain').'static/images/Lottery/ad/hongb.png',
						'redpacket_img'		=>	$get_ad_popout['ad_img'],
						'tips_type'			=>	$prize_type
					];
		}/* else {
    	    //增加一次中奖机会处理
            $now_time = get_time();				                                            //定义当前时间
            $memcache_day = date('Y_m_d', $now_time);
            $memcache_game_name =  'redis_game_num_'.$memcache_day.'_'.$uid.'_'.$game_id;   //定义缓存名称
            $redis_valid_time = 60*60*24;                                                   //设置缓存有效时间
            // 传递游戏的类别，根据用户名和类别在缓存中进行查找
            $memcache_game_arr = $memcache->get($memcache_game_name);
            //游戏次数缓存中加1
            $memcache_game_arr['num'] = $memcache_game_arr['num'] + 1;
            //更新缓存中游戏次数
            $memcache->set($memcache_game_name,$memcache_game_arr,$redis_valid_time);
            $res = 	[
                'ad_id'				=>	$get_ad_popout['id'],
                'ad_title'			=>	$get_ad_popout['ad_title'],
                'ad_img'			=>	$get_ad_popout['ad_img'],
                'ad_url'			=>	$get_ad_popout['ad_url'],
                'tips_type'			=>	$prize_type
            ];
    }*/
		return $res;
		
	}

	/**
	 * 弹框 广告或广告+红包权重分配
	 * @Author   Hulkzero
	 * @DateTime 2018-09-20T21:54:20+0800
	 * @Email    hulkzero@163.com
	 * @return   array                   弹出框信息
	 */
	public function ad_redpacket_popout($game_id)
	{
		//如果中奖数据是放在数据库里，这里就需要进行判断中奖数量
		//在中1、2、3等奖的，如果达到最大数量的则unset相应的奖项，避免重复中大奖
		//code here eg:unset($prize_arr['0'])
        $ad_prize_arr = $this->m_GameList->getGameAdPrizeRadio($game_id);// 数据库中获取对应游戏弹窗占比 Jason 2018-11-14 add
		$prize_arr = [
					    [
					    	'id'				=>	1,									//id 
					    	'prize_type_title'	=>	'弹广告',
//					    	'v'					=>	9,
					    	'v'					=>	$ad_prize_arr['type1_radio'],		//概率
					    	'prize_type'		=>	1									//1广告，2广告+红包，3广告+1次游戏机会
					    ],
					    [	
					    	'id'				=>	2,									//id 
					    	'prize_type_title'	=>	'弹广告+红包',
//					    	'v'					=>	1,
					    	'v'					=>	$ad_prize_arr['type2_radio'],       //概率
					    	'prize_type'		=>	2									//1广告，2广告+红包，3广告+1次游戏机会
					    ]/*,
                        [
                            'id'				=>	3,									//id
                            'prize_type_title'	=>	'弹广告+增加一次游戏机会',				//
                            'v'					=>	1,									//概率
                            'prize_type'		=>	3									//1广告，2广告+红包，3广告+1次游戏机会
                        ]*/
					];
		$end = 0;
        $new_arr1 = [];
        $new_arr2 = [];
		foreach ($prize_arr as $one) {
//            $prize_arr[$val['id']] = $val['v'];
            $new_arr1 = null;
		    $start = $end;
            $end = $one['v'] + $end;
            $new_arr1['start'] = $start;
            $new_arr1['end'] = $end;
            $new_arr2[$one['id']] = $new_arr1;
		}
		//$rid = $this->get_rand($prize_arr, $end); 										//根据概率获取奖项id
		$rid = $this->get_rand($new_arr2, $end); 										//根据概率获取奖项id
		//$prize_type = $prize_arr[$rid-1]['prize_type']; 								//被选中的数据
        foreach ($prize_arr as $one) {
            if ($one['id'] == $rid) {
                $prize_type = $one['prize_type'];
                break;
            }
        }

        //$prize_type = $prize_arr[$rid]['prize_type']; 								    //被选中的数据//整理逻辑上有bug//20181023
		return $prize_type;
	}


	/**
	 * 弹出框 广告权重分配（具体广告）
	 * @Author   Hulkzero
	 * @DateTime 2018-09-20T09:57:41+0800
	 * @Email    hulkzero@163.com
	 * @return   [type]                   [description]
	 */
	public function get_game_ad_popout($uid, $game_id)
	{
        /**
         * 原始代码
         */
        $shop_ad_arr = $this->m_AdvConfig->getAdList();								// 获取商户广告集合
        if(!empty($shop_ad_arr)){
            $return_arr = $shop_ad_arr[0];
        }
        $end = 0;
        $new_arr1 = [];
        $new_arr2 = [];
        foreach ($shop_ad_arr as $one) {
//            $shop_ad_arr[$val['id']] = $val['ad_radio'];
            $new_arr1 = null;
            $start = $end;
            $end = $one['ad_radio'] + $end;
            $new_arr1['start'] = $start;
            $new_arr1['end'] = $end;
            $new_arr2[$one['id']] = $new_arr1;
        }
        $rid = $this->get_rand($new_arr2, $end); 									//根据概率获取奖项id
        // $shop_ad_id = $shop_ad_arr[$rid-1]['id']; 									//获取被选中的孩子们
        // return $shop_ad_arr[$rid-1];

        foreach ($shop_ad_arr as $one) {
            if ($one['id'] == $rid) {
                $return_arr = $one;
                break;
            }
        }
        //uid、$game_id传过来，时间取当前，广告ID$rid
//        $memcache = get_memcache();
//        $mem_user_data = $memcache->get('user_data_' . $uid);
//        if(empty($mem_user_data)){
//            $mem_user_data = $this->>initUserData($uid);
//        }
//        $city_code = $mem_user_data['city_code'];
        $this->writeAdvShowLog($uid, $game_id, $rid, 0);

        return $return_arr;
	}

	/**
	 * 获取弹出框单个红包的金额
	 * @Author   Hulkzero
	 * @DateTime 2018-09-20T22:04:26+0800
	 * @Email    hulkzero@163.com
	 * @return   [type]                   [description]
	 */
	public function get_one_redpacket_money()
	{
		$get_game_redpacket_arr = $this->get_game_redpacket();
		if (empty($get_game_redpacket_arr)) {
            $get_game_redpacket_arr = $this->get_game_redpacket();
        }
		$hundred_min = $get_game_redpacket_arr['red_min'] * 1000;
		$hundred_max = $get_game_redpacket_arr['red_max'] * 1000;
		$hundred_rand = mt_rand($hundred_min, $hundred_max);
		$mt_rand_value = $hundred_rand * 0.001;
		$arr_data = [
						'r_id' 		=>	$get_game_redpacket_arr['id'],
						'mt_rand'	=>	$mt_rand_value
					];
		return $arr_data;
	}

	/**
	 * 获取弹出的 红包权重分配
	 * @Author   Hulkzero
	 * @DateTime 2018-09-20T21:42:58+0800
	 * @Email    hulkzero@163.com
	 * @return   [type]                   [description]
	 */
	public function get_game_redpacket()
	{
		$game_redpacket_arr = $this->m_GameRedpacketList->getRedpacketList();			// 获取设定的红包

        $end = 0;
        $new_arr1 = [];
        $new_arr2 = [];
		foreach ($game_redpacket_arr as $one) {
//            $game_redpacket_arr[$val['id']] = $val['red_weight'];
            $new_arr1 = null;
		    $start = $end;
            $end = $one['red_weight'] + $end;
            $new_arr1['start'] = $start;
            $new_arr1['end'] = $end;
            $new_arr2[$one['id']] = $new_arr1;
		}
		$rid = $this->get_rand($new_arr2, $end); 							    //根据概率获取奖项id
		// $game_redpacket_id = $game_redpacket_arr[$rid-1]['id']; 						//获取被选中的孩子们
		// return $game_redpacket_arr[$rid-1];

		foreach ($game_redpacket_arr as $one) {
		    if ($one['id'] == $rid) {
		    	$return_arr = $one;
		    	break;
		    }
		}
		return $return_arr;
	}

	/**
	 * 随机概率的计算
	 * @Author   Hulkzero
	 * @DateTime 2018-09-13T10:03:17+0800
	 * @Email    hulkzero@163.com
	 * @param    [type]                   $proArr [description]
	 * @return   [type]                           [description]
	 */
	public function get_rand($res_arr, $end = 0) {
        //概率数组循环
        $result = 0;
        if (count($res_arr) >= 1 && $end > 0) {
            $rand = mt_rand(1, $end);
            foreach ($res_arr as $k => $v) {
                if ($rand > $v['start'] && $rand <= $v['end']) {
                    $result = $k;
                    break;
                }
            }
        } else {
            $result = 0;
        }
     	return $result;
 	}

 	/**
 	 * 数据埋点插入到详情表
 	 * @Author   Hulkzero
 	 * @DateTime 2018-10-11T09:10:16+0800
 	 * @Email    hulkzero@163.com
 	 * @param    int                   	$uid    	[用户id]
 	 * @param    int                  	$gid    	[游戏id]
 	 * @param    integer                $c_type 	[数据埋点类别]
 	 * @return   [type]                           	[description]
 	 */
 	public function count_data_detail($uid, $g_id = 0, $c_type = 0)
 	{
 		// 进行信息的统计
		$t_tablename = 'b_game_count_detail_g'.$g_id; 									//获取分表表名
  		$this->m_GameCountDetail->setTableName($t_tablename);        					//设置表名
  		// 定义添加的数组
  		$save_data = [
  						'uid'			=>	$uid,
  						'c_type'		=>	$c_type,									//游戏入口每天点击次数
  						'addtime'		=> 	get_time()
  					];
  		$res = $this->m_GameCountDetail->countDetailInsert($save_data);					//获取插入id
  		return $res;
 	}


    /**
     * 写广告展示日志
     * @param $uid
     * @param $gameid
     * @param $adv_id
     * @param $city_code
     * @return   [type]                   [description]
     *
     * @Author   seaboyer
     * @DateTime 2018-10-18
     * @Email    seaboyer@163.com
     */
    public function writeAdvShowLog($uid, $game_id, $adv_id, $city_code = 0)
    {
        //缓存思路：先用缓存保存每次请求的值，然后定时n分钟后更新一下总数表，详细表累积1000条批量插入。
        //写总数
        //
        //写详细
        //

        if (!is_numeric($uid) || !is_numeric($game_id) || !is_numeric($adv_id)) {
            return;
        }

        if (empty($city_code)) {
            $memcache = get_memcache();
            $mem_user_data = $memcache->get('user_data_' . $uid);
            if (empty($mem_user_data)) {
                $mem_user_data = $this->initUserData($uid);
            }
            if (!empty($mem_user_data)) {
                $city_code = $mem_user_data['city_code'];
                $os = $mem_user_data['login_type'];
            } else {
                $city_code = 0;
                $os = 0;
            }
        }

        $t_adv_config = get_db_table_name('adv_config');
        $m_adv_config = new CMApiCount();
        $m_adv_config->setTableName($t_adv_config);
        $where = null;
        $where['id'] = $adv_id;
        $r1 = $m_adv_config->incField($where,'ad_show_count');//updateInfo();

        $t_adv_show = get_db_table_name('adv_show', $uid);
        $m_adv_show = new CMApiCount();
        $m_adv_show->setTableName($t_adv_show);
        $data = null;
        $data['l_type'] = 100+$game_id;
        $data['l_uid'] = $uid;
        $data['l_gid'] = $adv_id;
        $data['l_os'] = $os;
        $data['l_city'] = $city_code;
        $data['addtime'] = get_time();
        $r2 = $m_adv_show->insert($data);

/*
        $m_CountData 	= 	new CMApiCount();       //随便new一个common模型
        $now_time 		= 	get_time();
        $expire_time 	= 	get_global_data('expire_time');		//缓存有效时间

        if ($c_type >= 1 && $c_type <= 4) {
            //if(!empty($callback_function)){
            //   $params = array($c_type, $c_id, $increment, $uid, $now_time);
            //    call_user_func_array( $callback_function, $params);
            //}
            $t_all_count = get_db_table_name('game_count_g'.$c_type);
        } elseif ($c_type == '5') {
            $t_all_count = get_db_table_name('game_count_g5');
        } elseif ($c_type == '6') {
            $t_all_count = get_db_table_name('game_count_g6');
        } elseif ($c_type == '7') {
            $t_all_count = get_db_table_name('millions_count');
        } else {
            return;
        }

        $memcache = get_memcache();
        //--- 1.memcache写count统计表 ---//

        $mem_key = 'count_all_data_' . $c_type;
        $count_all_data = $memcache->get($mem_key);

        if (empty($count_all_data)) {						//初始缓存
            $time_str = date('Y-m-d H',$now_time);
            $time_previous 	=  	strtotime($time_str.":00:00"); 			    //前一个小时整对应秒数
            $time_next 		= 	$time_previous + 60 * 60;                     		//下一个小时整对应秒数

            $count_all_data['count_time'] = $time_next;
            $count_all_data['count_data'][$c_id] = $increment;
        } else {
            $count_time = $count_all_data['count_time'];
            //b.有缓存数据，没过期，data累加值
            if ($count_time > $now_time) {
                if (empty($count_all_data['count_data'][$c_id])) {
                    $count_all_data['count_data'][$c_id] = $increment;
                } else {
                    $count_all_data['count_data'][$c_id] = $count_all_data['count_data'][$c_id] + $increment;
                }
            } else {
                if (!empty($count_all_data['count_data'])) {
                    $arr_k = array();
                    $arr_v = array();
                    foreach ($count_all_data['count_data'] as $k=>$v){
                        $arr_k[] = 'c_'.$k;
                        $arr_v[] = $v;
                    }
                    $str_k = implode(',',$arr_k);
                    $str_v = implode(',',$arr_v);

                    $str_sql = "INSERT INTO {$t_all_count} (c_time, addtime, $str_k) VALUES ($count_time, $now_time, $str_v)";
                    $m_CountData->executeSql($str_sql);

                    unset($arr_k);//清理data数据
                    unset($arr_v);
                    $count_all_data = null;
                }
                //值初始化time和第一个值
                $time_str = date('Y-m-d H',$now_time);
                $time_previous 	=  	strtotime($time_str.":00:00");  					    //前一个小时整对应秒数
                $time_next 		= 	$time_previous + 60 * 60;                     				//下一个小时整对应秒数

                $count_all_data['count_time'] 			= $time_next;
                $count_all_data['count_data'][$c_id] 	= $increment;
            }
        }
        $memcache->set($mem_key, $count_all_data, false, $expire_time['d1']);
*/
    }


	/**
	 * 方法未找到函数
	 * @Author   Hulkzero
	 * @DateTime 2018-09-10T19:23:16+0800
	 * @Email    hulkzero@163.com
	 * @return   [type]                   [description]
	 */
	public function not_find_action()
	{

	}

    /**
     * 获取所有游戏次数和继续游戏次数 Jason 2018-10-29
     */
	public function get_game_all_times()
    {
        // 获取传递的数据
        //$request_param 	= $this->request_param;
        $uid 			= $this->uid;
        //$g_id			= isset($request_param['g_id']) ?intval($request_param['g_id']) : 0;

        //web_return($this->auth,['udi'=>$uid,'g_id'=>$g_id],1,'获取成功');// Test

        $memcache = get_memcache();                                         // 实例化memcache
        $now_time = get_time();                                             // 当前时间
        $memcache_day = date('Ymd', $now_time);

        $game_conf_key = 'game_once_'.$memcache_day.'_'.$uid;   // 游戏次数key
        $game_again_key = 'game_again_'.$memcache_day.'_'.$uid;   // 继续游戏key

        if (sys_ver() == 3) {
            $game_again_valid_time = 60 * 60 * 24;                                              //继续游戏缓存时间24小时
            $game_once_valid_time = 60 * 60 * 3;                                                //游戏次数缓存时间3小时
        } else {
            $game_again_valid_time = 60 * 20;           //测试-有效期设置为10分钟 Jason 2018-10-30
            $game_once_valid_time = 60 * 10;
        }

        // 游戏次数数据
        $have_game_times_data = $memcache->get($game_conf_key);             // 游戏次数
        $game_conf_res = null;
        if (empty($have_game_times_data) || $have_game_times_data['exp_time'] < $now_time) {// 无缓存或缓存失效
            $game_conf_res = $this->m_GameList->getGameConfig();
            $game_new_conf = [];
            foreach ($game_conf_res as $k=>$v) {
                $game_new_conf[$v['id']] = ['once_count'=>$v['once_count']];
            }
            //游戏次数过期时间
            $game_new_conf['exp_time'] = $game_once_valid_time + $now_time;
            //设置游戏次数缓存
            $memcache->set($game_conf_key, $game_new_conf,false, $game_once_valid_time);
            //设置完缓存从缓存中获取
            $game_have_once_times = $game_new_conf;
        } else {// 有缓存直接从缓存中读取
            $game_have_once_times = $have_game_times_data;
        }

        // 继续玩游戏次数数据
        $have_game_again_data = $memcache->get($game_again_key);            // 继续玩游戏次数
        if (empty($have_game_again_data) || $have_game_again_data['exp_time'] < $now_time ) {// 无缓存或缓存失效
            if(empty($game_conf_res)) {
                $game_conf_res = $this->m_GameList->getGameConfig();
            }
            $game_again_conf = [];
            foreach ($game_conf_res as $kk=>$vv) {
                $game_again_conf[$vv['id']] = ['again_count'=>$vv['again_count']];
            }
            //继续玩游戏次数过去时间
            $game_again_conf['exp_time'] = $game_again_valid_time + $now_time;
            //设置继续玩游戏次数缓存
            $memcache->set($game_again_key, $game_again_conf,false, $game_again_valid_time);
            $game_have_again_times = $game_again_conf;
        } else {
            $game_have_again_times = $have_game_again_data;
        }

        $return_data = [
            'game_times' => $game_have_once_times,
            'game_again_times'=> $game_have_again_times

        ];
        web_return($this->auth, $return_data, 1, '获取成功');

    }

//    public function get_user_game_times()
//    {
//        // 获取传递的数据
//        $uid 			= $this->uid;
//
//        $memcache = get_memcache();                                                         //实例化memcache
//        $now_time = get_time();                                                             //当前时间
//        $memcache_day = date('Ymd', $now_time);
//
//        if (sys_ver() == 3) {
//            $game_again_valid_time = 60 * 60 * 24;                                              //继续游戏缓存时间24小时
//            $game_once_valid_time = 60 * 60 * 3;                                                //游戏次数缓存时间3小时
//        } else {
//            $game_again_valid_time = 60 * 20;           //测试-有效期设置为10分钟 Jason 2018-10-30
//            $game_once_valid_time = 60 * 10;
//        }
//
//        //1.游戏次数数据
//        $game_conf_key = 'game_once_'.$memcache_day.'_'.$uid;                                    //游戏次数key
//        $have_game_times_data = $memcache->get($game_conf_key);                                  //游戏次数
//        if (empty($have_game_times_data) || $have_game_times_data['exp_time'] < $now_time) {     //无缓存或缓存失效
//            $game_conf_res = $this->m_GameList->getGameConfig();
//            $game_new_conf = [];
//            foreach ($game_conf_res as $one) {
//                $game_new_conf[$one['id']] = $one['once_count'];
//            }
//            $game_new_conf['exp_time'] = $game_once_valid_time + $now_time;//游戏次数过期时间
//            $memcache->set($game_conf_key,$game_new_conf,false,$game_once_valid_time);      //设置游戏次数缓存
//
//            $game_have_once_times = $game_new_conf;
//        } else {// 有缓存直接从缓存中读取
//            $game_have_once_times = $have_game_times_data;
//        }
//
//        //2.继续玩游戏次数数据
//        $game_again_key = 'game_again_'.$memcache_day.'_'.$uid;                                     //继续游戏key
//        $have_game_again_data = $memcache->get($game_again_key);                                    //继续玩游戏次数
//        if (empty($have_game_again_data) || $have_game_again_data['exp_time'] < $now_time ) {       //无缓存或缓存失效
//            if(empty($game_conf_res)){
//                $game_conf_res = $this->m_GameList->getGameConfig();
//            }
//            $game_again_conf = [];
//            foreach ($game_conf_res as $one) {
//                $game_again_conf[$one['id']] = $one['again_count'];
//            }
//            $game_again_conf['exp_time'] = $game_again_valid_time + $now_time;                      //继续玩游戏次数过去时间
//            $memcache->set($game_again_key, $game_again_conf,false, $game_again_valid_time);   //设置继续玩游戏次数缓存
//            $game_have_again_times = $game_again_conf;
//        } else {// 有缓存直接从缓存中读取
//            $game_have_again_times = $have_game_again_data;
//        }
//
//        $return_data = [
//            'once_times' => $game_have_once_times ,
//            'again_times'=> $game_have_again_times
//        ];
//
//        web_return($this->auth, $return_data, 1, '获取成功');
//    }

}
