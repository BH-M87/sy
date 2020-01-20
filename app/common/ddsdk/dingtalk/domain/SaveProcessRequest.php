<?php

/**
 * 入参
 * @author auto create
 */
class SaveProcessRequest
{
	
	/** 
	 * 企业应用id
	 **/
	public $agentid;
	
	/** 
	 * 审批模板描述
	 **/
	public $description;
	
	/** 
	 * true
	 **/
	public $disable_form_edit;
	
	/** 
	 * true
	 **/
	public $disable_stop_process_button;
	
	/** 
	 * true表示不带流程的模板
	 **/
	public $fake_mode;
	
	/** 
	 * 表单列表
	 **/
	public $form_component_list;
	
	/** 
	 * 设置模板是否隐藏，true表示隐藏
	 **/
	public $hidden;
	
	/** 
	 * 审批模板名称
	 **/
	public $name;
	
	/** 
	 * 审批模板唯一码
	 **/
	public $process_code;
	
	/** 
	 * 审批模板编辑跳转页。当fake_mode为true时，此参数失效。
	 **/
	public $template_edit_url;	
}
?>