<?php
/**
 * dingtalk API: dingtalk.oapi.chat.updatebanwords request
 * 
 * @author auto create
 * @since 1.0, 2019.07.30
 */
class OapiChatUpdatebanwordsRequest
{
	/** 
	 * 禁言时间，单位ms
	 **/
	private $banWordsTime;
	
	/** 
	 * chatid
	 **/
	private $chatid;
	
	/** 
	 * 0表示剔除禁言名单，1表示加入禁言名单
	 **/
	private $type;
	
	/** 
	 * 被禁言人id列表
	 **/
	private $useridList;
	
	private $apiParas = array();
	
	public function setBanWordsTime($banWordsTime)
	{
		$this->banWordsTime = $banWordsTime;
		$this->apiParas["ban_words_time"] = $banWordsTime;
	}

	public function getBanWordsTime()
	{
		return $this->banWordsTime;
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

	public function setType($type)
	{
		$this->type = $type;
		$this->apiParas["type"] = $type;
	}

	public function getType()
	{
		return $this->type;
	}

	public function setUseridList($useridList)
	{
		$this->useridList = $useridList;
		$this->apiParas["userid_list"] = $useridList;
	}

	public function getUseridList()
	{
		return $this->useridList;
	}

	public function getApiMethodName()
	{
		return "dingtalk.oapi.chat.updatebanwords";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->banWordsTime,"banWordsTime");
		RequestCheckUtil::checkNotNull($this->chatid,"chatid");
		RequestCheckUtil::checkNotNull($this->type,"type");
		RequestCheckUtil::checkNotNull($this->useridList,"useridList");
		RequestCheckUtil::checkMaxListSize($this->useridList,20,"useridList");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
