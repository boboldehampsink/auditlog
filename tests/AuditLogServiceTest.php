<?php

namespace Craft;

/**
 * Audit Log Service Test.
 *
 * Unit Tests for the Audit Log Service
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@nerds.company>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   MIT
 *
 * @link      http://github.com/boboldehampsink
 */
class AuditLogServiceTest extends BaseTest
{
    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        // Set up parent
        parent::setUpBeforeClass();

        // Require dependencies
        require_once __DIR__.'/../services/AuditLogService.php';
        require_once __DIR__.'/../models/AuditLogModel.php';
    }

    /**
     * Test view.
     *
     * @covers ::view
     */
    final public function testView()
    {
        $this->setMockElementsService();

        $service = new AuditLogService();
        $result = $service->view(1);

        $this->assertCount(1, $result);
    }

    /**
     * Mock ElementsService.
     */
    private function setMockElementsService()
    {
        $mock = $this->getMockBuilder('Craft\ElementsService')
            ->disableOriginalConstructor()
            ->setMethods(array('getCriteria'))
            ->getMock();

        $criteria = $this->getMockElementCriteriaModel();

        $mock->expects($this->any())->method('getCriteria')->willReturn($criteria);

        $this->setComponent(craft(), 'elements', $mock);
    }

    /**
     * Mock ElementCriteriaModel.
     *
     * @return ElementCriteriaModel
     */
    private function getMockElementCriteriaModel()
    {
        $mock = $this->getMockBuilder('Craft\ElementCriteriaModel')
            ->disableOriginalConstructor()
            ->setMethods(array('__set', 'first', 'find'))
            ->getMock();

        $log = $this->getMockAuditLogModel();

        $mock->expects($this->any())->method('__set')->willReturn(true);
        $mock->expects($this->any())->method('first')->willReturn($log);
        $mock->expects($this->any())->method('find')->willReturn(array($log));

        return $mock;
    }

    /**
     * Mock EntryModel.
     *
     * @return EntryModel
     */
    private function getMockAuditLogModel()
    {
        $mock = $this->getMockBuilder('Craft\AuditLogModel')
            ->disableOriginalConstructor()
            ->setMethods(array('__get'))
            ->getMock();

        $mock->expects($this->any())->method('__get')->willReturn($this->returnCallback(function ($attribute) {
            switch ($attribute) {
                case 'before':
                case 'after':
                    return array();

                default:
                    return 'test';
            }
        }));

        return $mock;
    }
}
