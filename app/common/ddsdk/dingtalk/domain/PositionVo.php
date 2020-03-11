<?php

/**
 * 位置列表
 * @author auto create
 */
class PositionVo
{
	
	/** 
	 * 位置id，根据type不同类型不同，如硬件类型代表硬件uid
	 **/
	public $position_id;
	
	/** 
	 * 位置名称
	 **/
	public $position_name;
	
	/** 
	 * 设备类型，具体见文档枚举
	 **/
	public $type;	
}
?>