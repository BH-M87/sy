<?php

/**
 * 添加成员 最多20个
 * @author auto create
 */
class OpenMemberAddDto
{
	
	/** 
	 * 手机号
	 **/
	public $mobile;
	
	/** 
	 * 国家码
	 **/
	public $state_code;
	
	/** 
	 * 长度4-32位之间，仅允许（字母 数字 _ -）
	 **/
	public $userid;	
}
?>