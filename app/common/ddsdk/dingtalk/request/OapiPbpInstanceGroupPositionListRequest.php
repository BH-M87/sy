<?php
/**
 * dingtalk API: dingtalk.oapi.pbp.instance.group.position.list request
 * 
 * @author auto create
 * @since 1.0, 2019.12.18
 */
class OapiPbpInstanceGroupPositionListRequest
{
	/** 
	 * 游标，用于分页查询
	 **/
	private $cursor;
	
	/** 
	 * 打卡组id，由创建打卡组接口返回
	 **/
	private $punchGroupId;
	
	/** 
	 * 分页请求数量
	 **/
	private $size;
	
	private $apiParas = array();
	
	public function setCursor($cursor)
	{
		$this->cursor = $cursor;
		$this->apiParas["cursor"] = $cursor;
	}

	public function getCursor()
	{
		return $this->cursor;
	}

	public function setPunchGroupId($punchGroupId)
	{
		$this->punchGroupId = $punchGroupId;
		$this->apiParas["punch_group_id"] = $punchGroupId;
	}

	public function getPunchGroupId()
	{
		return $this->punchGroupId;
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

	public function getApiMethodName()
	{
		return "dingtalk.oapi.pbp.instance.group.position.list";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->punchGroupId,"punchGroupId");
		RequestCheckUtil::checkNotNull($this->size,"size");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
