<?php

namespace Craft;

/**
 * Audit Log Record.
 *
 * Represents the Audit Log tables in the database
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@nerds.company>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   MIT
 *
 * @link      http://github.com/boboldehampsink
 */
class AuditLogRecord extends BaseRecord
{
    /**
     * Return the table name.
     *
     * @return string
     */
    public function getTableName()
    {
        return 'auditlog';
    }

    /**
     * Return the table fields.
     *
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
     * Define the table relations.
     *
     * @return array
     */
    public function defineRelations()
    {
        return array(
            'user'    => array(static::BELONGS_TO, 'UserRecord'),
        );
    }
}
