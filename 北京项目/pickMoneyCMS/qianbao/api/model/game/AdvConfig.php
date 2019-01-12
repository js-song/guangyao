<?php

/**
 * @Author: "Hulkzero"
 * @Date:   2018-10-19 11:36:14
 * @Email: "hulkzero@163.com"
 * @Last Modified time: 2018-10-19 16:43:39
 */
namespace app\api\model\game;

use app\api\model\BaseModel;
use think\Db;

class AdvConfig extends BaseModel
{
	protected $table = 'b_adv_config';

    protected function initialize()
    {
        parent::initialize();
    }

    public function getAdList()
    {
        // 定义查询字段
        // $field = ['id', 'ad_title', 'ad_type', 'android_url', 'ios_url', 'ad_img', 'ad_url', 'weight', 'ad_status', 'addtime'];
        $field = ['id', 'ad_position_id', 'ad_list_id', 'ad_title', 'ad_page', 'ad_img', 'ad_url1', 'ad_url2', 'ad_radio', 'ad_show_count', 'ad_info' ,'status', 'addtime'];
        // 定义查询语句
        $where = [
                    'status'    =>  1,
                    'ad_position_id' =>	8,
                 ];
        // 查询使用联合查询
        $res = Db::table($this->table)
        		->field($field)
                ->where($where)
                ->select();
        return $res;
    }

}