<?php

namespace Craft;

/**
 * Audit Log Model.
 *
 * Contains the log data aswell as some constants
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@nerds.company>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   MIT
 *
 * @link      http://github.com/boboldehampsink
 */
class AuditLogModel extends BaseElementModel
{
    /**
     * Statuses.
     */
    const CREATED   = 'live';
    const MODIFIED  = 'pending';
    const DELETED   = 'expired';

    /**
     * Fieldtypes.
     */
    const FieldTypeEntries     = 'Entries';
    const FieldTypeCategories  = 'Categories';
    const FieldTypeAssets      = 'Assets';
    const FieldTypeUsers       = 'Users';
    const FieldTypeLightswitch = 'Lightswitch';

    /**
     * Element Type name.
     *
     * @var string
     */
    protected $elementType = 'AuditLog';

    /**
     * Return the title of this model.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->type;
    }

    /**
     * Return the model's attributes.
     *
     * @return array
     */
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

    /**
     * Return the model's status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Return the model's user.
     *
     * @return UserModel
     */
    public function getUser()
    {
        return craft()->users->getUserById($this->userId);
    }
}
