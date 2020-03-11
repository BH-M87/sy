<?php
/**
 * dingtalk API: dingtalk.oapi.impaas.conversaion.changegroupowner request
 * 
 * @author auto create
 * @since 1.0, 2019.07.03
 */
class OapiImpaasConversaionChangegroupownerRequest
{
	/** 
	 * 应用channel
	 **/
	private $channel;
	
	/** 
	 * 钉钉会话id
	 **/
	private $chatid;
	
	/** 
	 * 员工id
	 **/
	private $userid;
	
	private $apiParas = array();
	
	public function setChannel($channel)
	{
		$this->channel = $channel;
		$this->apiParas["channel"] = $channel;
	}

	public function getChannel()
	{
		return $this->channel;
	}

	public function setChatid($chatid)
	{
		$this->chatid = $chatid;
		$this->apiParas["chatid"] = $chatid;
	}

	public function getChatid()
	{
		return $this->chatid;
	}

	public function setUserid($userid)
	{
		$this->userid = $userid;
		$this->apiParas["userid"] = $userid;
	}

	public function getUserid()
	{
		return $this->userid;
	}

	public function getApiMethodName()
	{
		return "dingtalk.oapi.impaas.conversaion.changegroupowner";
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
