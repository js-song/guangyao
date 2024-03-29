<?php

namespace app\work\controller;

use think\Controller;
use think\facade\Request;
use think\Exception;

use app\work\model\WorkPlan as WorkPlanModel;
//use app\work\controller\BaseController;

class WorkPlan extends BaseController
{

    protected $m_WorkPlanModel;
    public function __construct()
    {
        parent::__construct();

        $this->m_WorkPlanModel = new WorkPlanModel();
    }

       /**
     * @cc 调用不存在方法统一处理
     * @param void
     * @return void
     *
     * @author seaboyer@163.com
     * @date 2018-10-24
     * @version 1.0
     */
    public function _empty()
    {
        return "<div style='text-align: center' align='center'>error</div>";
    }

    public function index()
    {
        $id = Request::instance()->param('id','trim',0);

        $where = null;
//        $where[] = ['id','>',0];
//        $list_work_plan = $this->m_WorkPlanModel->getPageList($where,15);
//        $list_work_plan = WorkPlanModel::all();
        $this->assign('list_work_plan', null);
//        print_r($list_work_plan);

//        $page = $list_work_plan->render();
//        $this->assign('page', $page);

        $one_work_plan = null;
//		if (!empty($id)) {
//		    $where = null;
//		    $where['id'] = $id;
//            $one_work_plan = $this->m_WorkPlanModel->getInfo($where);
//        }
        $this->assign('one_work_plan', $one_work_plan);


        $this->assign('arr_api_list', $one_work_plan);

		return $this->fetch();
    }
	
    public function task_plan_edit()
    {

		
        return $this->fetch();
    }

}
