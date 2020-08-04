<?php

/**
 * 职位结果
 * @author auto create
 */
class JobSimpleVO
{
	
	/** 
	 * 职位地址详情
	 **/
	public $address;
	
	/** 
	 * 职位地址 市
	 **/
	public $city;
	
	/** 
	 * 企业id
	 **/
	public $corpid;
	
	/** 
	 * 职位描述
	 **/
	public $description;
	
	/** 
	 * 职位地址 区/县
	 **/
	public $district;
	
	/** 
	 * 招募人数
	 **/
	public $head_count;
	
	/** 
	 * 职位编码
	 **/
	public $job_code;
	
	/** 
	 * 职位唯一标识
	 **/
	public $job_id;
	
	/** 
	 * 最高薪水，单位分
	 **/
	public $max_salary;
	
	/** 
	 * 最低薪水，单位分
	 **/
	public $min_salary;
	
	/** 
	 * 职位名称
	 **/
	public $name;
	
	/** 
	 * 职位地址 升
	 **/
	public $province;
	
	/** 
	 * 1小学 2初中 3高中 4中专 5大专 6本科 7硕士 8 博士 9其他
	 **/
	public $required_edu;
	
	/** 
	 * 是否薪资面议
	 **/
	public $salary_negotiable;	
}
?>