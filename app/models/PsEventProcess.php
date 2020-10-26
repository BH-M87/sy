<?php

namespace app\models;

class PsEventProcess extends BaseModel 
{
    public static function tableName()
    {
        return 'ps_event_process';
    }

    public function rules()
    {
        return [];
    }

    public function attributeLabels()
    {
        return [];
    }
}