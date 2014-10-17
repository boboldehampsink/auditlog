<?php
namespace Craft;

class AuditLogModel extends BaseElementModel
{

    // Statuses
    const CREATED   = 'live';
    const MODIFIED  = 'pending';
    const DELETED   = 'expired';
    
    // Fieldtypes
    const FieldTypeEntries     = 'Entries';
    const FieldTypeCategories  = 'Categories';
    const FieldTypeAssets      = 'Assets';
    const FieldTypeUsers       = 'Users';
    const FieldTypeLightswitch = 'Lightswitch';
    const FieldTypeTable       = 'Table';
    
    protected $elementType = 'AuditLog';
    
    public function getTitle()
    {
        return $this->type;
    }
    
    protected function defineAttributes()
    {
        return array_merge(parent::defineAttributes(), array(
            'id'        => AttributeType::Number,
            'type'      => AttributeType::String,
            'user'      => AttributeType::Number,
            'origin'    => AttributeType::String,
            'before'    => AttributeType::Mixed,
            'after'     => AttributeType::Mixed,
            'diff'      => AttributeType::Mixed,
            'status'    => AttributeType::String
        ));
    }
    
    public function getStatus()
    {
        return $this->status;
    }
    
}