<?php
namespace Craft;

class AuditLogVariable
{

    public function log($criteria)
    {
        return craft()->auditLog->log((object) $criteria);
    }

    public function view($id)
    {
        return craft()->auditLog->view($id);
    }
}
