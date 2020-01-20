<?php

/**
 * 返回值
 * @author auto create
 */
class OpenProjectDto
{
	
	/** 
	 * 开放组织id
	 **/
	public $corp_id;
	
	/** 
	 * 创建组织时返回，用corpId+corpSecret可以换取token
	 **/
	public $corp_secret;
	
	/** 
	 * 创建时间
	 **/
	public $create_time;
	
	/** 
	 * 创建人
	 **/
	public $creator;
	
	/** 
	 * 描述
	 **/
	public $desc;
	
	/** 
	 * 名称
	 **/
	public $name;
	
	/** 
	 * 负责人，刚创建时负责人就是创建人
	 **/
	public $owner;	
}
?>