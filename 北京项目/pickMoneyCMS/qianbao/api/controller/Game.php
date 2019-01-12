<?php

/**
 * @Author: "Hulkzero"
 * @Date:   2018-09-10 13:41:22
 * @Email: "hulkzero@163.com"
 * @Last Modified time: 2018-10-12 17:06:32
 * @explain 游戏接口路由分发
 */
namespace app\api\controller;

use app\api\controller\BaseController;

use app\api\controller\game\Answer;
use app\api\controller\game\GameLobby;
use app\api\controller\game\Lottery;
use app\api\controller\game\Tongs;
use app\api\controller\game\WaterFlower;
use Think\Db;

class Game extends BaseController
{
    public function index()
    {
        $action = $this->request_action;
        // 进行类的实例化
        $answer         = new Answer();
        $game_lobby     = new GameLobby();
        $lottery        = new Lottery();
        $tongs          = new Tongs();
        $water_flower   = new WaterFlower();

        if(!empty($action)){
            switch ($action) {
                /**-------------------游戏公共接口-------------------**/ 
                case 'test':
                    $game_lobby->test();            //测试使用
                    break;
                case 'get_game_prize_list':                     //APP游戏中获取我的奖品列表接口
                    $game_lobby->get_game_prize_list();
                    break;
                case 'get_redpacket_detail':                    //APP获取游戏奖品红包详情接口              
                    $game_lobby->get_redpacket_detail();
                    break;
                case 'get_tbk_list':                            //APP获取游戏中的淘宝客商品列表
                    $game_lobby->get_tbk_list();
                    break;
                case 'post_who_play_games':                     //APP抛送谁在玩游戏，再和web端比对保安全
                    $game_lobby->post_who_play_games();
                    break;
                case 'entry_game':                              //APP 游戏红包 首页中进入游戏
                    $game_lobby->entry_game();
                    break;
                case 'index_game_entry':                        //APP趣味游戏入口，进入游戏统计
                    $game_lobby->index_game_entry();
                    break;
                case 'count_app_ad':                            //APP统计广告的点击次数
                    $game_lobby->count_app_ad();
                    break;
                case 'judge_game_num':                          //Web判断游戏可以使用的次数
                    $game_lobby->judge_game_num();
                    break;
                case 'receice_award':                           //Web用户领取游戏奖品
                    $game_lobby->receice_award();       
                    break;
                case 'get_refresh':                             //Web刷新页面，不弹框
                    $game_lobby->get_refresh();
                    break;
                case 'fifteen_appear_game':                     //Web15秒出现游戏入口跳跳框
                    $game_lobby->fifteen_appear_game();
                    break;
                case 'fifteen_entry_game':                      //Web点击出现游戏入口跳跳框
                    $game_lobby->fifteen_entry_game();
                    break;
                /**-------------------答题游戏   -------------------**/
                case 'entry_answer':
                    $answer->entry_answer();                    //Web答题游戏，游戏大厅是自己进入还是跳淘宝客
                    break;
                case 'get_question_list':                       //Web获取答题题目
                    $answer->get_question_list();
                    break;
                case 'get_next_question':                       //Web获取下一题
                    $answer->get_next_question();
                    break;
                case 'get_answer_last_reward':                  //答题结束领取奖励接口
                    $answer->get_answer_last_reward();
                    break;
                case 'get_answer_last_jump':                    //Web提交最后一题的跳转接口
                    $answer->get_answer_last_jump();
                    break;
                case 'get_answer_last_result':
                    $answer->get_answer_last_result();          //Web获取最后结果
                    break;
                /**-------------------刮刮乐游戏 -------------------**/ 
                case 'start_lottery':                           //Web开始刮奖，获取奖品
                    $lottery->start_lottery();
                    break;
                case 'get_lottery_result':                      //Web刮奖次数统计
                    $lottery->get_lottery_result();
                    break;
                /**-------------------抓手游戏  -------------------**/ 
                case 'start_tongs':                             //Web开始抓手
                    $tongs->start_tongs();
                    break;
                case 'get_tongs_result':                        //Web根据抓到的礼包类型，来获取结果
                    $tongs->get_tongs_result();
                    break;
                /**-------------------浇花      -------------------**/ 
                case 'start_water_flower':                      //Web开始浇花
                    $water_flower->start_water_flower(); 
                    break;
                /**-------------------进入游戏验证初始化      -------**/
                case 'init_game_param':
                    $this->init_game_param(); 
                    break;
                /**-------------------获取游戏次数和继续游戏次数     -------**/
                case 'get_game_all_times':
                    $game_lobby->get_game_all_times();
                    break;
                case 'get_user_game_times':
                    $game_lobby->get_user_game_times();
                    break;
                default:
                    # code...
                    break;
            }
        }else{
            $game_lobby->not_find_action();
        }
    }

    public function init_game_param()
    {
        $expire_time = get_global_data('expire_time');
        //用户通过app的ie进入游戏，由app调用此接口，告诉php服务器谁正在进入游戏，php收到后memcache存一下uid，auth等，方便后续web上行接口php服务器校验uid
        $req = $this->request_param;

        if(!empty($req['uid'])){
            $user_game_data = null;
            $user_game_data['uid'] = $req['uid'];
            $user_game_data['auth'] = $req['auth'];
            $user_game_data['device'] = $req['device'];
            $user_game_data['time'] = get_time();

            $memcache = get_memcache();
            $memcache->set('user_game_data_' . $req['uid'], $user_game_data, $expire_time['h2']);
        }
        sdk_return('ok');
    }
}
