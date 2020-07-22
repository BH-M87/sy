<?php
/**
 * dingtalk API: dingtalk.oapi.ats.job.deliver.add request
 * 
 * @author auto create
 * @since 1.0, 2020.06.24
 */
class OapiAtsJobDeliverAddRequest
{
	/** 
	 * 业务唯一标识，接入前请提前沟通
	 **/
	private $bizCode;
	
	/** 
	 * 投递渠道, 接入前请提前沟通
	 **/
	private $deliverChannel;
	
	/** 
	 * 投递状态 fail,success
	 **/
	private $deliverStatus;
	
	/** 
	 * 职位id
	 **/
	private $jobId;
	
	private $apiParas = array();
	
	public function setBizCode($bizCode)
	{
		$this->bizCode = $bizCode;
		$this->apiParas["biz_code"] = $bizCode;
	}

	public function getBizCode()
	{
		return $this->bizCode;
	}

	public function setDeliverChannel($deliverChannel)
	{
		$this->deliverChannel = $deliverChannel;
		$this->apiParas["deliver_channel"] = $deliverChannel;
	}

	public function getDeliverChannel()
	{
		return $this->deliverChannel;
	}

	public function setDeliverStatus($deliverStatus)
	{
		$this->deliverStatus = $deliverStatus;
		$this->apiParas["deliver_status"] = $deliverStatus;
	}

	public function getDeliverStatus()
	{
		return $this->deliverStatus;
	}

	public function setJobId($jobId)
	{
		$this->jobId = $jobId;
		$this->apiParas["job_id"] = $jobId;
	}

	public function getJobId()
	{
		return $this->jobId;
	}

	public function getApiMethodName()
	{
		return "dingtalk.oapi.ats.job.deliver.add";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->bizCode,"bizCode");
		RequestCheckUtil::checkNotNull($this->deliverChannel,"deliverChannel");
		RequestCheckUtil::checkNotNull($this->deliverStatus,"deliverStatus");
		RequestCheckUtil::checkNotNull($this->jobId,"jobId");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
