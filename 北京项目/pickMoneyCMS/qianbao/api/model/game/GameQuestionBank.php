<?php

/**
 * @Author: "Hulkzero"
 * @Date:   2018-09-19 19:30:23
 * @Email: "hulkzero@163.com"
 * @Last Modified time: 2018-10-10 19:04:00
 */
namespace app\api\model\game;

use app\api\model\CommonModel;
use think\Db;

class GameQuestionBank extends CommonModel
{   

    /**
     * 随机获取指定条数题目
     * 未分表，使用base基类
     * @Author   Hulkzero
     * @param $table [表名]
     * @DateTime 2018-09-15T17:05:37+0800
     * @Email    hulkzero@163.com
     * @param $num [获取题目数量，默认为8条]
     * @return mixed
     */
    public function getQuestionList($table, $num)
    {
    	// $count = Db::table($table)->count();
    	// $min = Db::table($table)->min('id');
    	// if ($count < $num) {
    	// 	$num = $count;
    	// }
    	// $i = 1;
    	// $flag = 0;
    	// $arr= [];
    	// while ($i <= $num) {
    	// 	$rund_num = rand($min, $count);
    	// 	if ($flag != $rund_num) {
    	// 		if (!in_array($rund_num, $arr)) {
    	// 			$arr[] = $rund_num;
    	// 			$flag = $rund_num;
    	// 		} else {
    	// 			$i--;
    	// 		}
    	// 		$i++;
    	// 	}
    	// }
    	// $res = Db::table($table)->where('id', 'in', $arr)->select();
        $res = Db::query("SELECT * FROM ".$table."  WHERE status = 1 ORDER BY RAND() LIMIT ".$num);
    	return $res;
    }

    /**
     * 获取答题游戏相关配置信息
     * @param $game_id [游戏ID]
     * @return array|false|null|\PDOStatement|string|\think\Model
     */
    function getGameConfig($game_id)
    {
        return Db::table('b_game_list')->where('id','=',$game_id)->field('delay_sec,group_count')->find();
    }

}
