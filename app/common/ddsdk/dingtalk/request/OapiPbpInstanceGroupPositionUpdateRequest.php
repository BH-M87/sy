<?php
/**
 * dingtalk API: dingtalk.oapi.pbp.instance.group.position.update request
 * 
 * @author auto create
 * @since 1.0, 2019.12.23
 */
class OapiPbpInstanceGroupPositionUpdateRequest
{
	/** 
	 * 同步参数
	 **/
	private $syncParam;
	
	private $apiParas = array();
	
	public function setSyncParam($syncParam)
	{
		$this->syncParam = $syncParam;
		$this->apiParas["sync_param"] = $syncParam;
	}

	public function getSyncParam()
	{
		return $this->syncParam;
	}

	public function getApiMethodName()
	{
		return "dingtalk.oapi.pbp.instance.group.position.update";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
