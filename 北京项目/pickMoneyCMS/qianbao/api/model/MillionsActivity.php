<?php
// +----------------------------------------------------------------------
// | 百万红包活动Activity
// +----------------------------------------------------------------------
// | Author: seaboyer <seaboyer@163.com>
// | Date: 2018-09-06
// +----------------------------------------------------------------------

namespace app\api\model;

use app\api\model\CommonModel;
use think\Db;

class MillionsActivity extends BaseModel
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

    public function getStockRadio ($id)
    {
        $where['id'] = $id;
        $where['status'] = 1;
        $tax = $this->where($where)->field('a_system_tax')->find();
        return $tax['a_system_tax'];
    }

    public function getActivityPacketLimit ($id)
    {
        $where[] = ['id', '=', $id];
        $where[] = ['status', '=', '1'];

        return $this->where($where)->field('a_user_limit')->find();
    }

    public function getRollMinMaxCount ($act_id) {
        $where[] = ['id', '=', $act_id];
        $where[] = ['status', '=', '1'];

        return $this->where($where)->field('a_roll_min,a_roll_max,a_roll_count')->find();
    }

    public function findLastActivity () {

        $where[] = ['status', '=', '1'];

        return $this->where($where)->field('id')->order('id','DESC')->find();
    }
}