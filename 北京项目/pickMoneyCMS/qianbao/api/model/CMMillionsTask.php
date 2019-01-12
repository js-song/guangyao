<?php
// +----------------------------------------------------------------------
// | 百万红包活动Task
// +----------------------------------------------------------------------
// | Author: seaboyer <seaboyer@163.com>
// | Date: 2018-09-06
// +----------------------------------------------------------------------

namespace app\api\model;

use app\api\model\CommonModel;
use think\Db;

class CMMillionsTask extends CommonModel
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

    public function getMillionsExpire ($uid)
    {
        return Db::table($this->table)->where('t_time_end', '<', time())->where('status', '<',4)->where('t_uid', $uid)->field('id,t_time_end')->select();
    }

    public function getIsExistUnderway ($uid)
    {
        $where['t_uid'] = $uid;
        $where['status'] = ['<', 5];
        return Db::query("SELECT id FROM {$this->table} WHERE t_uid = $uid AND status < 5");
        return Db::table($this->table)->where($where)->field('id')->find();
    }

    public function overPacketCount ($uid)
    {
        $where['t_uid'] = $uid;
        $where['t_day'] = date('Ymd', get_time());

        return $this->getCount($where);
    }

    public function ltOrGtGetCount ($uid, $type, $n, $date = null)
    {
        $where[] = ['t_uid', '=', $uid];
        $where[] = ['status', $type, $n];
        if ($date) {
            $where[] = ['t_day', '=', $date];
        }

        return $this->getCount($where);
    }

    public function packetIsOver ($uid)
    {
        $where['t_uid'] = $uid;
        $where['status'] = ['lt', 5];
        //$field = ['status, t_money_ready, t_money_friend, t_time_end, t_money_value, id'];
        return Db::query("SELECT status, t_money_ready, t_money_friend, t_time_end, t_money_value, id， t_ready_count, t_need_count FROM {$this->table} WHERE t_uid = $uid AND status < 5");
        //return Db::table($this->table)->where($where)->field('status, t_money_ready, t_money_friend, t_time_end, t_money_value, id')->find();
    }

    public function getAllMoneyGet ($uid) {

        $field = [
            'sum(t_money_ready)' => 'all_money'
        ];
        $where['t_uid'] = $uid;
        $where['status'] = 6;

        return Db::table($this->table)->where($where)->field($field)->find();
    }

    public function getCash ($uid)
    {
        $where['t_uid'] = $uid;
        $where['status'] = '4';
        $field = 't_money_value, t_config_id';
        //检查提现金额
        return Db::table($this->table)->where($where)->field($field)->find();
    }

    public function getConfigByID ($id)
    {
        $where['id'] = $id;
        return Db::table($this->table)->where($where)->field('t_config_id')->find();
    }

    public function updateExpire ($uid)
    {
        $where['t_uid'] = $uid;
        $where['status'] = 4;
        $where['t_time_end']  = ['<', get_time()];
        $data = [
          'status' => 7
        ];
        return Db::table($this->table)->where($where)->update($data);
    }

    public function getOpenNum ($id)
    {
        $where['id'] = $id;
        $field = ['t_ready_count, t_money_value, t_need_count, t_money_ready, status, t_time_end'];
        return Db::table($this->table)->where($where)->field($field)->find();
    }
}