<?php
/**
 * dingtalk API: dingtalk.oapi.pbp.instance.position.list request
 * 
 * @author auto create
 * @since 1.0, 2019.12.21
 */
class OapiPbpInstancePositionListRequest
{
	/** 
	 * 业务id，由系统分配
	 **/
	private $bizId;
	
	/** 
	 * 业务实例id，由创建示例接口返回
	 **/
	private $bizInstId;
	
	/** 
	 * 游标，用于分页查询
	 **/
	private $cursor;
	
	/** 
	 * 查询数据量
	 **/
	private $size;
	
	/** 
	 * 位置类型，如B1，wifi等
	 **/
	private $type;
	
	private $apiParas = array();
	
	public function setBizId($bizId)
	{
		$this->bizId = $bizId;
		$this->apiParas["biz_id"] = $bizId;
	}

	public function getBizId()
	{
		return $this->bizId;
	}

	public function setBizInstId($bizInstId)
	{
		$this->bizInstId = $bizInstId;
		$this->apiParas["biz_inst_id"] = $bizInstId;
	}

	public function getBizInstId()
	{
		return $this->bizInstId;
	}

	public function setCursor($cursor)
	{
		$this->cursor = $cursor;
		$this->apiParas["cursor"] = $cursor;
	}

	public function getCursor()
	{
		return $this->cursor;
	}

	public function setSize($size)
	{
		$this->size = $size;
		$this->apiParas["size"] = $size;
	}

	public function getSize()
	{
		return $this->size;
	}

	public function setType($type)
	{
		$this->type = $type;
		$this->apiParas["type"] = $type;
	}

	public function getType()
	{
		return $this->type;
	}

	public function getApiMethodName()
	{
		return "dingtalk.oapi.pbp.instance.position.list";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->bizId,"bizId");
		RequestCheckUtil::checkNotNull($this->bizInstId,"bizInstId");
		RequestCheckUtil::checkNotNull($this->size,"size");
		RequestCheckUtil::checkNotNull($this->type,"type");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}