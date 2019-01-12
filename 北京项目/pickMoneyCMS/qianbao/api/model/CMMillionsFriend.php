<?php
// +----------------------------------------------------------------------
// | 百万红包活动Friend
// +----------------------------------------------------------------------
// | Author: seaboyer <seaboyer@163.com>
// | Date: 2018-09-06
// +----------------------------------------------------------------------
namespace app\api\model;

use app\api\model\CommonModel;
use think\Db;

class CMMillionsFriend extends CommonModel
{
    protected function initialize()
    {
        parent::initialize();
    }

    public function getByWhereField($where = [], $field = [], $type = 'find') {
        if ($type == 'find') {
            return Db::table($this->table)->where($where)->field($field)->find();
        } elseif($type == 'select') {
            return Db::table($this->table)->where($where)->field($field)->select();
        }
    }

    public function getFriendsHelpList ($taskid)
    {
        $where['f_act_id'] = 1;
        $where['f_task_id'] = $taskid;
        return Db::table($this->table)->where($where)->field('f_friend_id,f_money')->select();
    }

    public function getFriendsListByUid ($uid, $act_id)
    {
        $where['f_uid'] = $uid;
        $where['f_act_id'] = $act_id;
        return Db::table($this->table)->where($where)->field('f_friend_id, f_money')->select();
    }

    public function isOpen ($uid)
    {
        $where['f_friend_id'] = $uid;
        return Db::table($this->table)->where($where)->find();
    }


}