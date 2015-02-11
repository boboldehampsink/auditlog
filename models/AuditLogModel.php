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
            'userId'    => AttributeType::Number,
            'origin'    => AttributeType::String,
            'before'    => AttributeType::Mixed,
            'after'     => AttributeType::Mixed,
            'diff'      => AttributeType::Mixed,
            'status'    => AttributeType::String,
        ));
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getUser()
    {
        return craft()->users->getUserById($this->userId);
    }
}
