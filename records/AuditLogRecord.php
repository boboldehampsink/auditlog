<?php
namespace Craft;

/**
 * Audit Log Record
 *
 * Represents the Audit Log tables in the database
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, author
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://github.com/boboldehampsink
 * @package   craft.plugins.auditlog
 */
class AuditLogRecord extends BaseRecord
{

    /**
     * Return the table name
     * @return string
     */
    public function getTableName()
    {
        return 'auditlog';
    }

    /**
     * Return the table fields
     * @return array
     */
    protected function defineAttributes()
    {
        return array(
            'type'   => AttributeType::String,
            'origin' => AttributeType::String,
            'before' => AttributeType::Mixed,
            'after'  => AttributeType::Mixed,
            'status' => AttributeType::String,
        );
    }

    /**
     * Define the table relations
     * @return array
     */
    public function defineRelations()
    {
        return array(
            'user'    => array(static::BELONGS_TO, 'UserRecord'),
        );
    }
}
