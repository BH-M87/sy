<?php

/**
 * 请求参数
 * @author auto create
 */
class OpenApiUpdateReimbursementRq
{
	
	/** 
	 * 审批人列表
	 **/
	public $audit_list;
	
	/** 
	 * corp id
	 **/
	public $corpid;
	
	/** 
	 * 审批单号
	 **/
	public $flow_no;
	
	/** 
	 * 审批操作时间
	 **/
	public $operate_time;
	
	/** 
	 * 审批状态
	 **/
	public $status;
	
	/** 
	 * 第三方报销单id
	 **/
	public $thirdparty_flow_id;	
}
?>