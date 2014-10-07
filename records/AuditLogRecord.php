<?php
namespace Craft;

class AuditLogRecord extends BaseRecord
{

    public function getTableName()
    {
        return 'auditlog';
    }

    protected function defineAttributes()
    {
        return array(
            'type'   => AttributeType::String,
            'origin' => AttributeType::String,
            'before' => AttributeType::Mixed,
            'after'  => AttributeType::Mixed,
            'status' => AttributeType::String
        );
    }
    
    public function defineRelations()
    {
        return array(
            'user'    => array(static::BELONGS_TO, 'UserRecord')
        );
    }
    
}