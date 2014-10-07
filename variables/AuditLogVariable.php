<?php
namespace Craft;

class AuditLogVariable {

    public function log() 
    {
    
        return craft()->auditLog->log();
    
    }
    
    public function view($id)
    {
    
        return craft()->auditLog->view($id);
    
    }
    
}