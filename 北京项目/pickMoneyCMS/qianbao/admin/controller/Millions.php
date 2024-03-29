<?php
/**
 * 活动管理-百万红包
 * Date: 18/9/17
 * Time: 上午10:43
 */
namespace app\admin\controller;

use Think\Cache;
use think\Controller;

use app\admin\model\MillionsModel;

class Millions extends BaseController
{
    protected $millions_model;
    protected function initialize()
    {
        parent::initialize();
        $this->millions_model = new MillionsModel();
    }

    //红包配置列表
    public function config()
    {
        //活动ID
        $id = $this->request->param('id',1,'intval');
        //当前活动信息
        $activity_info = $this->millions_model->getActivityInfo($id,'a_user_limit');
        //红包规则设置
        $act_config_list = $this->millions_model->getConfigList($id);
        //红包金额衰减百分比数据处理toArray
        foreach ($act_config_list as $k=>$v) {
            $act_config_list[$k]['c_invite_value'] = explode('|',$v['c_invite_value']);
            //统计数组长度
            $act_config_list[$k]['invite_value_count'] = count($act_config_list[$k]['c_invite_value']);
            //拆包率
            if ($v['c_all_friend_num'] == 0 || $v['c_all_share_num'] == 0) {
                $act_config_list[$k]['open_rate'] = 0;
            } else {
                $act_config_list[$k]['open_rate'] = round(($v['c_all_friend_num']/$v['c_all_share_num'])*100,2);
            }

        }

        $this->assign('act_config_list',$act_config_list);
        $this->assign('empty','<span class="config-empty">暂无数据</span>');
        $this->assign('id',$id);
        $this->assign($activity_info);

        return $this->fetch();
    }

    //拆包记录列表
    public function task()
    {
        //活动ID
        $id = $this->request->param('id',1,'intval');
        //分表ID
        $table_id = $this->request->param('table_id',0,'intval');
        //用户状态
        $status = $this->request->param('status','');
        //开始时间
        $start_time = $this->request->param('start_time','');
        //结束时间
        $end_time = $this->request->param('end_time','');
        //关键词
        $keywords = $this->request->param('keywords','');
        //每页显示条数
        $page_size = $this->request->param('page_size',10,'intval');

        //缓存中有直接读取缓存中用户昵称数据
        $user_list_cache = cache('nickname_list');
        if (!empty($user_list_cache)) {
            $user_list = $user_list_cache;
        } else {
            //用户昵称信息
            $user_list = $this->millions_model->getUserList();
        }

        $new_user_list = [];
        foreach ($user_list as $uk=>$uv){
            $new_user_list[$uv['uid']] = $uv['nickname'];
        }

        $status_arr = [
            1=>'未帮拆',
            3=>'拆包中',
            4=>'已拆完',
            5=>'待提现',
            6=>'已提现',
            8=>'已过期',
            9=>'已删除'
        ];

        if (!empty($keywords)) {
            //关键词（用户昵称）匹配用户uid
            if (array_search($keywords,$new_user_list)) {
                $uid = array_search($keywords,$new_user_list);
            } else {
                $uid = 2;//非用户列表中数据
            }
        } else {
            $uid = 0;
        }

        //拆包记录列表
        $task_list = $this->millions_model->getTaskListByPage($table_id,$id,$status,get_time($start_time.' 00:00:00'),get_time($end_time.' 23:59:59'),$uid,$page_size);
        //活动总人数和总邀请人数
        $count_res = $this->millions_model->getNumById($id);
        foreach ($task_list['list'] as $key=>$val) {
            $task_list['list'][$key]['addtime'] = date('Y-m-d H:i:s',$val['addtime']);
            $task_list['list'][$key]['nickname'] = !empty($new_user_list[$val['t_uid']]) ? $new_user_list[$val['t_uid']] : '获取中';
            $task_list['list'][$key]['status'] = !empty($status_arr[$val['status']]) ? $status_arr[$val['status']] : '未知';
        }

        //返回表单提交数据
        $form_data = [
            'table_id'  => $table_id,
            'status'    => $status,
            'start_time'=> $start_time,
            'end_time'  => $end_time,
            'keywords'  => $keywords,
        ];

        $this->assign('form_data',$form_data);
        $this->assign('task_list',$task_list['list']);
        $this->assign('id',$id);
        $this->assign('empty','<tr><td colspan="8">暂无数据</td></tr>');
        $this->assign('count_res',$count_res);
        $this->assign('page',$task_list['page']);

        return $this->fetch();
    }

    //内容配置
    public function content_config()
    {
        //活动ID
        $id = $this->request->param('id',1,'intval');

        $this->assign('id',$id);
        return $this->fetch();
    }

    //发送引导红包
    public function send_red_pack()
    {
        $data['addtime'] = get_time();//红包发布时间
        $data['status'] = 1;//状态
        $add_id = $this->millions_model->sendRedPack($data);
        if ($add_id) {
            $memcache = get_memcache();
            $millions_guide_key = 'millions_guide_list';
            $millions_guide_time = 60 * 60 * 24;
            $millions_guide_data = [
                'id'        => $add_id,
                'addtime'   => $data['addtime']
            ];
            $memcache->set($millions_guide_key,$millions_guide_data,false,$millions_guide_time);
            return json(['status'=>1,'msg'=>'发送成功']);
        } else {
            return json(['status'=>0,'msg'=>'发送失败']);
        }
    }

    /**
     * 更新对应活动每日拆红包上限
     * @return \think\response\Json
     */
    public function do_up_limit()
    {
        $data = $this->request->param();
        $up_user_limit_validate = $this->validate($data,'MillionsValidate.upUserLimit');
        if ($up_user_limit_validate !== true) {
            return json(['status'=>0,'msg'=>$up_user_limit_validate]);
        } else {
            $upData['a_user_limit'] = $data['user_limit'];
            $up_user_limit_res = $this->millions_model->upActivityById($data['id'],$upData);
            if ($up_user_limit_res) {
                return json(['status'=>1,'msg'=>'更新成功']);
            } else {
                return json(['status'=>0,'msg'=>'更新失败']);
            }
        }
    }

    /**
     * 新增红包配置
     * @return \think\response\Json
     */
    public function add_config()
    {
        $data = $this->request->param();
        $add_config_validate_res = $this->validate($data,'MillionsValidate.addConfig');
        if ($add_config_validate_res !== true) {
            return json(['status'=>0,'msg'=>$add_config_validate_res]);
        } else {
            if ($data['price_max'] < $data['price_min']) {
                return json(['status'=>0,'msg'=>'请填写正确红包金额上限']);
            }

            if (array_sum($data['invite']) != 100) {
                return json(['status'=>0,'msg'=>'请填写正确的金额衰减']);
            };

            //红包比重计算(参数：对应活动ID，状态为启用，返回字段)
            $act_config_radio = $this->millions_model->getConfigList($data['id'],1,'c_radio');
            $new_act_config_radio = [];
            foreach ($act_config_radio as $k=>$v) {
                $new_act_config_radio[] = $v['c_radio'];
            }

            if($data['radio'] + array_sum($new_act_config_radio) > 100) {
                return json(['status'=>0,'msg'=>'请填写正确的红包比重']);
            }

            $add_data['c_act_id'] = $data['id'];
            $add_data['c_title'] = $data['title'];
            $add_data['c_money_up'] = $data['price_max'];
            $add_data['c_money_down'] = $data['price_min'];
            $add_data['c_invite_num'] = $data['invite_num'];
            $add_data['c_invite_value'] = implode('|',$data['invite']);
            $add_data['c_radio'] = $data['radio'];
            $add_data['addtime'] = time();
            $add_data['status'] = 1;
            unset($data);
            $add_res = $this->millions_model->addConfig($add_data);
            if ($add_res) {
                return json(['status'=>1,'msg'=>'添加成功']);
            } else {
                return json(['status'=>0,'msg'=>'添加失败']);
            }
        }
    }

    //活动红包配置修改
    public function up_config()
    {
        $data = $this->request_param;
        $up_config_validate = $this->validate($data,'MillionsValidate.upConfig');
        if ($up_config_validate !== true) {
            return json(['status'=>0,'msg'=>$up_config_validate]);
        } else {
            if ($data['price_max'] < $data['price_min']) {
                return json(['status'=>0,'msg'=>'请填写正确红包上限金额']);
            }
            $up_data['c_money_up'] = $data['price_max'];
            $up_data['c_money_down'] = $data['price_min'];
            $up_data['id'] = $data['c_id'];
            unset($data);
            $up_res = $this->millions_model->upConfig($up_data);
            if ($up_res) {
                return json(['status'=>1,'msg'=>'修改成功']);
            } else {
                return json(['status'=>0,'msg'=>'修改失败']);
            }
        }
    }

    /**
     * 软删除、撤销红包配置
     * id、status 为必选参数
     * @return \think\response\Json
     */
    public function set_status()
    {
        $data = $this->request->param();
        $set_status_validate_res = $this->validate($data,'MillionsValidate.setConfigStatus');
        if ($set_status_validate_res !== true) {
            return json(['status'=>0,'msg'=>$set_status_validate_res]);
        } else {
            $set_status_res = $this->millions_model->setConfigStatus($data['c_id'],$data['status']);
            if ($set_status_res) {
                return json(['status'=>1,'msg'=>'操作成功']);
            } else {
                return json(['status'=>0,'msg'=>'操作成功']);
            }
        }
    }

    /**
     * 删除红包配置
     * @return \think\response\Json
     */
    public function delConfig()
    {
        $data = $this->request->param();
        $del_config_validate_res = $this->validate($data,'MillionsValidate.delConfig');
        if ($del_config_validate_res !== true) {
            return json(['status'=>0,'msg'=>$del_config_validate_res]);
        } else {
            $del_res = $this->millions_model->delConfig($data['c_id']);
            if ($del_res) {
                return json(['status'=>1,'msg'=>'操作成功']);
            } else {
                return json(['status'=>0,'msg'=>'操作失败']);
            }
        }
    }

    public function getTaskInfo()
    {
        $table_id = $this->request->param('table_id',0,'intval');
        $act_id = $this->request->param('act_id',0,'intval');
        $config_id = $this->request->param('config_id',0,'intval');
        $uid = $this->request->param('uid',0,'intval');
        $task_info = $this->millions_model->getTaskInfo($table_id,$act_id,$config_id,$uid);
        return json($task_info);
    }
}