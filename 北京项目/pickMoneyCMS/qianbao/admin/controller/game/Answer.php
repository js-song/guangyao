<?php
/**
 * 脑洞答题馆
 * User: zhushengli
 * Date: 18/9/17
 * Time: 下午1:13
 */
namespace app\admin\controller\game;

use app\admin\controller\BaseController;

use app\admin\model\game\AnswerModel;
use app\admin\model\game\GameModel;

class Answer extends BaseController
{
    protected $answer_model;
    protected $game_model;
    protected function initialize()
    {
        parent::initialize();
        $this->answer_model = new AnswerModel();
        $this->game_model = new GameModel();
    }

    /**
     * 获取题目列表分页数据[可选参数：状态；每页显示条数]
     * @return mixed
     */
    public function home()
    {
        //题目状态0:删除；1：启用；2：暂停,默认设置为0，是不显示删除的数据
        $status = $this->request->param('status',0,'intval');
        //每页显示条数
        $page_size = $this->request->param('pagesize',10,'intval');
        //分页列表数据
        $res = $this->answer_model->getListByPage($status,$page_size);
        $list = $this->dealAnswerList($res['list']);

        $this->assign('list',$list);
        $this->assign('empty','<tr><td colspan="8">暂无数据</td></tr>');
        $this->assign('page',$res['page']);

        return $this->fetch();
    }
    /**
     * 获取软删除题目分页数据
     * @return \think\response\Json
     */
    public function get_soft_del()
    {
        $soft_del_res = $this->answer_model->getListByPage(0,15);
        $list = $this->dealAnswerList($soft_del_res['list']);

        $this->assign('list',$list);
        $this->assign('page',$soft_del_res['page']);

        return json($list);
    }

    /**
     * 添加+更新操作
     * 无ID进行数据添加，有ID进行数据更新
     * @return \think\response\Json
     */
    public function do_answer()
    {
        $id = $this->request->param('id',0,'intval');
        //需要添加或修改的数据
//        $data = $this->request->param();
        $data = $this->request_param;//带过滤
        //id为0,添加数据
        if (empty($id)) {
            //验证数据
            $add_validate_res = $this->validate($data,'AnswerValidate.add');
            if ($add_validate_res !== true) {
                return json(['status'=>0,'msg'=>$add_validate_res]);
            } else {
                $add_data['q_title'] = $data['title'];
                $add_data['q_option'] = json_encode((object)$data['option']);
                $add_data['q_option_answer'] = $data['answer'];
                $add_data['q_tip'] = htmlspecialchars_decode($data['tip']);//脑洞
                $add_data['add_man'] = '1';//后期需要获取操作员ID
                $add_data['addtime'] = time();
                $add_data['status'] = 1;//默认启用
                unset($data);
                $add_res = $this->answer_model->add($add_data);
                if ($add_res) {
                    return json(['status'=>1,'msg'=>'添加成功']);
                } else {
                    return json(['status'=>0,'msg'=>'添加失败']);
                }
            }
        } else {//更新操作
            //验证数据
            $up_validate_res = $this->validate($data,'AnswerValidate.update');
            if ($up_validate_res !== true) {
                return json(['status'=>0,'msg'=>$up_validate_res]);
            } else {
                $up_data['q_title'] = $data['title'];
                $up_data['q_option'] = json_encode((object)$data['option']);
                $up_data['q_option_answer'] = $data['answer'];
                $up_data['q_tip'] = htmlspecialchars_decode($data['tip']);//脑洞
                $up_data['edit_man'] = '1';//后期需要获取操作员ID
                $up_data['edittime'] = time();
                unset($data);
                $up_res = $this->answer_model->edit($id,$up_data);
                if ($up_res) {
                    return json(['status'=>1,'msg'=>'更新成功']);
                } else {
                    return json(['status'=>0,'msg'=>'更新失败']);
                }
            }
        }
    }

    /**
     * 设置题目状态
     * ids(array),status(int)为必填参数
     * @return \think\response\Json
     */
    public function set_status()
    {
        $ids = $this->request->param('ids');
        $status = $this->request->param('status',1,'intval');
        $data['ids'] = $ids;
        $data['status'] = $status;
        $set_validate_res = $this->validate($data,'AnswerValidate.setStatus');
        if ($set_validate_res !== true) {
            return json(['status'=>0,'msg'=>$set_validate_res]);
        } else {
            $set_res = $this->answer_model->setStatus($ids,$status);
            if ($set_res) {
                return json(['status'=>1,'msg'=>'设置成功']);
            } else {
                return json(['status'=>0,'msg'=>'设置失败']);
            }
        }
    }

    /**
     * 获取一条题目数据
     * id为必要参数,且不为0
     * @return \think\response\Json
     */
    public function get_info()
    {
        $id = $this->request->param('id',0,'intval');
        $data['id'] = $id;
        $get_validate_res = $this->validate($data,'AnswerValidate.getInfo');
        if ($get_validate_res !== true) {
            return json(['status'=>0,'msg'=>$get_validate_res]);
        } else {
            $get_info = $this->answer_model->getOneInfo($id);
            $get_info['q_option'] = json_decode($get_info['q_option'],true);
            return json(['status'=>1,'data'=>$get_info]);
        }
    }

    /**
     * 删除一条题目数据
     * id(int)为必填参数
     * @return \think\response\Json
     */
    public function del()
    {
        $id = $this->request->param('id',0,'intval');
        $data['id'] = $id;
        $del_validate_res = $this->validate($data,'AnswerValidate.del');
        if ($del_validate_res !== true) {
            return json(['status'=>0,'msg'=>$del_validate_res]);
        } else {
            $del_res = $this->answer_model->del($id);
            if ($del_res) {
                return json(['status'=>1,'msg'=>'删除成功']);
            } else {
                return json(['status'=>0,'msg'=>'删除失败']);
            }
        }
    }

    /**
     * 答案组与答案处理
     * @param $data
     * @return mixed
     */
    protected function dealAnswerList($data)
    {
        if (empty($data)) {
            return [];
        } else {
            foreach ($data as $k=>$v) {
                $data[$k]['q_option'] = json_decode($v['q_option'],true);
                $data[$k]['q_option_answer'] = $data[$k]['q_option'][$v['q_option_answer']];
            }
            return $data;
        }
    }

    //脑洞答题规则配置
    public function config()
    {
        $game_id = 1;//脑洞答题ID
        $config_res = $this->game_model->getGameConfig($game_id);

        $this->assign('config',$config_res);

        return $this->fetch();
    }

    //更新答题规则配置
    public function up_config()
    {
        $game_id = 1;
        //每轮答题数
        $group_count = $this->request->param('group_count',8,'intval');
        //答题时间
        $delay_sec = $this->request->param('delay_sec',10,'intval');

        $data['group_count']    = $group_count;
        $data['delay_sec']      = $delay_sec;
        $up_res = $this->game_model->upConfig($data,$game_id);
        if ($up_res) {
            return json(['status'=>1,'msg'=>'更新成功']);
        } else {
            return json(['status'=>0,'msg'=>'更新失败']);
        }
    }
}