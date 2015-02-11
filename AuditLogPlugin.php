<?php
namespace Craft;

class AuditLogPlugin extends BasePlugin
{

    public function getName()
    {
        return Craft::t('Audit Log');
    }

    public function getVersion()
    {
        return '0.5.0';
    }

    public function getDeveloper()
    {
        return 'Bob Olde Hampsink';
    }

    public function getDeveloperUrl()
    {
        return 'https://github.com/boboldehampsink';
    }

    public function hasCpSection()
    {
        return true;
    }

    public function registerCpRoutes()
    {
        return array(
            'auditlog/(?P<logId>\d+)' => 'auditlog/_log',
        );
    }

    public function init()
    {

        // Log all specific element types that have the right events
        craft()->auditLog_category->log();
        craft()->auditLog_entry->log();
        craft()->auditLog_user->log();
    }
}
