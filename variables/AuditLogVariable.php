<?php

namespace Craft;

/**
 * Audit Log variable.
 *
 * Injects functions in templates
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, author
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 */
class AuditLogVariable
{
    /**
     * Show log with critiera.
     *
     * @param array $criteria
     *
     * @return array
     */
    public function log($criteria)
    {
        return craft()->auditLog->log((object) $criteria);
    }

    /**
     * Show a specific log item.
     *
     * @param int $id
     *
     * @return AuditLogModel
     */
    public function view($id)
    {
        return craft()->auditLog->view($id);
    }
}
