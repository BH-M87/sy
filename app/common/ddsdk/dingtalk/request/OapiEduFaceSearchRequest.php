<?php
/**
 * dingtalk API: dingtalk.oapi.edu.face.search request
 * 
 * @author auto create
 * @since 1.0, 2019.11.14
 */
class OapiEduFaceSearchRequest
{
	/** 
	 * 班级id
	 **/
	private $classId;
	
	/** 
	 * https://img.alicdn.com/tfs/TB1._LRfUz1gK0jSZLeXXb9kVXa-36-32.png
	 **/
	private $url;
	
	private $apiParas = array();
	
	public function setClassId($classId)
	{
		$this->classId = $classId;
		$this->apiParas["class_id"] = $classId;
	}

	public function getClassId()
	{
		return $this->classId;
	}

	public function setUrl($url)
	{
		$this->url = $url;
		$this->apiParas["url"] = $url;
	}

	public function getUrl()
	{
		return $this->url;
	}

	public function getApiMethodName()
	{
		return "dingtalk.oapi.edu.face.search";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->classId,"classId");
		RequestCheckUtil::checkNotNull($this->url,"url");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
