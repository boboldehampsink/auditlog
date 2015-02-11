<?php
namespace Craft;

class AuditLogTest extends BaseTest
{

    public function setUp()
    {

        // Load plugins
        $pluginsService = craft()->getComponent('plugins');
        $pluginsService->loadPlugins();
    }

    public function testActionDownload()
    {

        // Get first log item
        $log = craft()->auditLog->view(1);

        // Only test if already set
        if ($log) {

            // $log is a model, want to break that down
            $result = craft()->auditLog->parseFieldData('title', $log);

            // Result is always a string
            $this->assertInternalType('string', $result);
        }
    }
}
