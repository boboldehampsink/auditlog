<?php

namespace Craft;

/**
 * Audit Log test.
 *
 * Tests if the audit log works
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, author
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 */
class AuditLogTest extends BaseTest
{
    /**
     * Load the plugin component.
     */
    public function setUp()
    {

        // Load plugins
        $pluginsService = craft()->getComponent('plugins');
        $pluginsService->loadPlugins();
    }

    /**
     * Test if viewing and parsing works.
     */
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
