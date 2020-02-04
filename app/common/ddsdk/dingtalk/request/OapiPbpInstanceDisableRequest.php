<?php
/**
 * dingtalk API: dingtalk.oapi.pbp.instance.disable request
 * 
 * @author auto create
 * @since 1.0, 2019.12.20
 */
class OapiPbpInstanceDisableRequest
{
	/** 
	 * 业务实例唯一标识
	 **/
	private $bizInstId;
	
	private $apiParas = array();
	
	public function setBizInstId($bizInstId)
	{
		$this->bizInstId = $bizInstId;
		$this->apiParas["biz_inst_id"] = $bizInstId;
	}

	public function getBizInstId()
	{
		return $this->bizInstId;
	}

	public function getApiMethodName()
	{
		return "dingtalk.oapi.pbp.instance.disable";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->bizInstId,"bizInstId");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
