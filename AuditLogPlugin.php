<?php
namespace Craft;

class AuditLogPlugin extends BasePlugin
{

    function getName()
    {
        return Craft::t('Audit Log');
    }

    function getVersion()
    {
        return '0.4.2';
    }

    function getDeveloper()
    {
        return 'Bob Olde Hampsink';
    }

    function getDeveloperUrl()
    {
        return 'https://github.com/boboldehampsink';
    }
    
    function hasCpSection()
    {
        return true;
    }
    
    function registerCpRoutes() 
    {
        return array(
            'auditlog/(?P<logId>\d+)' => 'auditlog/_log'
        );
    
    }
    
    function init()
    {
    
        // Log all specific element types that have the right events
        craft()->auditLog_category->log();
        craft()->auditLog_entry->log();
        craft()->auditLog_user->log();
    
    }
    
}