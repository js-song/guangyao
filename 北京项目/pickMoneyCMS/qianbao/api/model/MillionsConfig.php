<?php
// +----------------------------------------------------------------------
// | 百万红包活动Config
// +----------------------------------------------------------------------
// | Author: seaboyer <seaboyer@163.com>
// | Date: 2018-09-06
// +----------------------------------------------------------------------

namespace app\api\model;

use app\api\model\CommonModel;
use think\Db;

class MillionsConfig extends BaseModel
{
    protected $table = 'b_millions_config';
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

    public function getActivityMaxMoney ()
    {
//        $where['c_act_id'] = 1;
//        $where['status'] = 1;
        $where[] = ['c_act_id', '=', '1'];
        $where[] = ['status', 'in', '1, 8'];


        return $this->where($where)->max('c_money_up');
    }

    public function getPacketConfig ($act_id)
    {
//        $where['c_act_id'] = $act_id;
//        $where['status'] = 1;
        $where[] = ['c_act_id', '=', $act_id];
        $where[] = ['status', '=', '1'];
        $order = 'c_packet_no';
        if (sys_ver() == 1) {
            $where[] = ['id', '<', 10];
        }
        return $this->where($where)->field('id,c_money_up,c_money_down,c_radio,id,c_invite_value,c_invite_num,c_packet_no')->order($order)->select();
    }

    public function getActivityConfig ($id)
    {
        $where['id'] = $id;
        //$field = ['c_money_up, c_money_down, c_invite_num, c_invite_value'];
        return Db::table($this->table)->where($where)->field('c_money_up, c_money_down, c_invite_num, c_invite_value')->find();
    }

    public function getConfigById ($packet_id, $act_id)
    {
        $where['id'] = $packet_id;
        $where['c_act_id'] = $act_id;
        return Db::table($this->table)->where($where)->field('c_money_up,c_money_down,c_radio,id,c_invite_value,c_invite_num,c_packet_no')->find();
    }

    public function updateOpenNum ($id)
    {
        $where['id'] = $id;
        return Db::table($this->table)->where($where)->inc('c_all_friend_num')->update();
    }

    public function updateTaskNum ($id)
    {
        $where['id'] = $id;
        return Db::table($this->table)->where($where)->inc('c_all_task_num')->update();
    }

    public function updateConfigSuccessNum ($configid) {
        $where[] = ['id', '=', $configid];
        return Db::table($this->table)->where($where)->inc('c_all_task_success')->update();
    }
}