<?php
/**
 * dingtalk API: dingtalk.oapi.workspace.project.create request
 * 
 * @author auto create
 * @since 1.0, 2019.11.07
 */
class OapiWorkspaceProjectCreateRequest
{
	/** 
	 * 创建人（主管理员）在归属组织内的userId
	 **/
	private $belongCorpUserid;
	
	/** 
	 * 是否建圈自动建群
	 **/
	private $createGroup;
	
	/** 
	 * 描述，长度256字符以内
	 **/
	private $desc;
	
	/** 
	 * 组织名，长度3-50个字符以内，不允许中划线、下划线、逗号、空格
	 **/
	private $name;
	
	/** 
	 * 开放的cid，如果有值会把该群作为组织的默认群，否则会新创建1个默认群
	 **/
	private $openConversationId;
	
	/** 
	 * 1项目组织  2圈子组织
	 **/
	private $type;
	
	/** 
	 * 可以指定创建人在项目/圈子组织内的userId，如果不填系统随机生成
	 **/
	private $userid;
	
	private $apiParas = array();
	
	public function setBelongCorpUserid($belongCorpUserid)
	{
		$this->belongCorpUserid = $belongCorpUserid;
		$this->apiParas["belong_corp_userid"] = $belongCorpUserid;
	}

	public function getBelongCorpUserid()
	{
		return $this->belongCorpUserid;
	}

	public function setCreateGroup($createGroup)
	{
		$this->createGroup = $createGroup;
		$this->apiParas["create_group"] = $createGroup;
	}

	public function getCreateGroup()
	{
		return $this->createGroup;
	}

	public function setDesc($desc)
	{
		$this->desc = $desc;
		$this->apiParas["desc"] = $desc;
	}

	public function getDesc()
	{
		return $this->desc;
	}

	public function setName($name)
	{
		$this->name = $name;
		$this->apiParas["name"] = $name;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setOpenConversationId($openConversationId)
	{
		$this->openConversationId = $openConversationId;
		$this->apiParas["open_conversation_id"] = $openConversationId;
	}

	public function getOpenConversationId()
	{
		return $this->openConversationId;
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
		return "dingtalk.oapi.workspace.project.create";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->belongCorpUserid,"belongCorpUserid");
		RequestCheckUtil::checkNotNull($this->name,"name");
		RequestCheckUtil::checkNotNull($this->type,"type");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
