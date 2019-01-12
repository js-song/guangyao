<?php

/**
 * @Author   Hulkzero
 * @DateTime 2018-08-31T11:24:48+0800
 * @Email    hulkzero@163.com
 * $Explain  来捡钱game游戏大厅接口信息
 */

namespace app\h5\controller\game;

use think\Controller;
use think\Request;

use app\h5\model\game\GamePrizeList;

class GameLobby extends Controller
{   
    // 定义奖品列表
    protected $m_GamePrize;
    protected $uid;

    public function __construct()
    {
        parent::__construct();

        $this->m_GamePrizeList = new GamePrizeList();

        $this->uid = $this->request->param('uid',0,'intval');
        //todo 后续可以带上其他sign等做一下校验
 
        $is_debug = 1;//1调试
    }

    /**
     * 显示游戏大厅详细信息
     *
     * @return \think\Response
     */
    public function home()
    {
        //模板变量赋值
        $this->assign('title','游戏大厅');
        //模板输出
        return $this->fetch('game/GameLobby/home');
    }

    /**
     * 页面跳转
     * @Author   Hulkzero
     * @DateTime 2018-09-27T15:14:13+0800
     * @Email    hulkzero@163.com
     * @return   页面跳转
     */
    public function loading()
    {
        //模板变量赋值
        $this->assign('title','游戏加载中...');
        //模板输出
        return $this->fetch('game/GameLobby/loading');
    }

    /**
     * 查找可玩游戏
     * @return mixed
     */
    public function no_times()
    {

        $this->assign('title','游戏加载中...');

        return $this->fetch('game/GameLobby/untimes');
    }

    public function get_prize_list($prize_type = 0)
    {   
        // if (empty($prize_type)) return false;
        // 实例化Model类
        // 获取结果集
        $res = $this->m_GamePrizeList->getPrizeList();
        return $res;
    }

}
