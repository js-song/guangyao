<?php
// +----------------------------------------------------------------------
// | 测试演示用CMUserList
// +----------------------------------------------------------------------
// | Author: seaboyer <seaboyer@163.com>
// | Date: 2018-09-06
// +----------------------------------------------------------------------

namespace app\api\model;

use app\api\model\CommonModel;
use think\Db;

class UserList extends BaseModel
{
	protected $table = 'b_1_user_list';

    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * @cc 测试演示方法
     *
     * @author seaboyer@163.com
     * @date 2018-09-06
     * @version 1.0
     */
    public function getUserList($where = [])
    {
        if(empty($where)){
            $res = Db::table($this->table)->find();
        }else{
            $res = Db::table($this->table)->where($where)->select();
        }
        //echo "lastsql = ".$this->getLastSql()."<br>";
    	return $res;
    }

    public function getRandUserInfo ()
    {
        Db::query("SELECT nickname,uid FROM b_1_user_list WHERE uid >= (SELECT floor(RAND() * (SELECT MAX(uid) FROM b_1_user_list))) ORDER BY uid LIMIT 20;");
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
     * @author seaboyer@163.com
     * @date 2018-10-11
     * @version 1.0
     */
    public function getRandUserList ($uid, $num = 20, $min = 10, $max = 100)
    {
        $one_max_id = $this->query("SELECT max(uid) as db_max_id FROM b_1_user_list WHERE uid >= 1");
        $i_rand_max = $num;
        $i_db_max = $i_rand_max * 2;
        $i_index = mt_rand(1, $one_max_id[0]['db_max_id'] - 100);
        $rand_user_list = $this->query("SELECT uid,nickname,avatar FROM b_1_user_list WHERE uid >= {$i_index}  AND uid <> {$uid} AND avatar = 2 limit {$i_db_max}");
        $i_all = count($rand_user_list);

        if ($i_all <> $i_db_max) {
            $rand_user_list = $this->query("SELECT uid,nickname,avatar FROM b_1_user_list WHERE uid <> {$uid} AND avatar = 2 order by uid desc limit {$i_db_max}");
        }

        $arr1 = array();
        $arr2 = array();
        if ($i_all == $i_db_max) {
            for($i = 0; $i < $i_rand_max; $i++){
                $arr2 = null;
                $arr2['uid'] = $rand_user_list[$i]['uid'];
                $arr2['name'] = $rand_user_list[$i]['nickname'];
                $arr2['avatar'] = get_user_avatar($rand_user_list[$i+$i_rand_max]['uid'],$rand_user_list[$i+$i_rand_max]['avatar']);
                $arr2['money'] = sprintf("%.2f", get_random_num($min, $max ,2));
                $arr1[] = $arr2;
            }
        } else {//测试库数据不足50个用这个方法打散一下头像和名称
            for($i = 0; $i < $i_all; $i++){
                $arr2 = null;
                $arr2['uid'] = $rand_user_list[$i]['uid'];
                $arr2['name'] = $rand_user_list[$i]['nickname'];
                $arr2['avatar'] = get_user_avatar($rand_user_list[$i_all - $i - 1]['uid'],$rand_user_list[$i_all - $i - 1]['avatar']);
                $arr2['money'] = sprintf("%.2f", get_random_num($min, $max ,2));
                $arr1[] = $arr2;
            }
        }

        return $arr1;
    }


    public function updateStock ($uid,$money_get)
    {
        $res = $this->execute("UPDATE {$this->table} SET stock = stock + {$money_get} WHERE uid = {$uid}");
        return $res;
    }

    public function getNickName ($uid)
    {
        $where[] = ['uid', '=', $uid];
        $res = Db::table($this->table)->where($where)->field('nickname')->find();
        return $res;
    }
}