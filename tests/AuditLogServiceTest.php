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
 *
 * @coversDefaultClass Craft\AuditLogService
 * @covers ::<!public>
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

        $this->assertInstanceOf('Craft\AuditLogModel', $result);
    }

    /**
     * Test parseFieldData.
     *
     * @param string $handle
     * @param mixed  $data
     * @param string $expected
     *
     * @covers ::parseFieldData
     * @dataProvider provideFieldData
     */
    final public function testParseFieldData($handle, $data, $expected)
    {
        $this->setMockFieldsService($handle);

        $service = new AuditLogService();
        $result = $service->parseFieldData($handle, $data);

        $this->assertSame($result, $expected);
    }

    /**
     * Test elementHasChanged.
     *
     * @covers ::elementHasChanged
     * @covers ::onElementChanged
     */
    final public function testElementHasChanged()
    {
        $before = array(
            'test' => array(
                'label' => 'test1',
                'value' => 'test1',
            ),
        );

        $after = array(
            'test' => array(
                'label' => 'test2',
                'value' => 'test2',
            ),
        );

        $service = new AuditLogService();
        $result = $service->elementHasChanged(ElementType::Entry, 1, $before, $after);

        $this->assertInternalType('array', $result);
    }

    /**
     * Provide field data.
     *
     * @return array
     */
    final public function provideFieldData()
    {
        require_once __DIR__.'/../models/AuditLogModel.php';

        return array(
            'Parse ElementCriteriaModel' => array(
                'element',
                $this->getMockElementCriteriaModel(),
                'test, test',
            ),
            'Parse Lightswitch with "no" option' => array(
                'lightswitch',
                '0',
                Craft::t('No'),
            ),
            'Parse Lightswitch with "yes" option' => array(
                'lightswitch',
                '1',
                Craft::t('Yes'),
            ),
            'Parse empty data' => array(
                'empty',
                null,
                '',
            ),
            'Parse data array' => array(
                'array',
                array('test', 'test'),
                'test, test',
            ),
            'Parse data object' => array(
                'object',
                call_user_func(function () {
                    $class = new \stdClass();
                    $class->test = 'test';

                    return $class;
                }),
                'test',
            ),
        );
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
            ->setMethods(array('__set', '__get', 'first', 'find'))
            ->getMock();

        $log = $this->getMockAuditLogModel();

        $mock->expects($this->any())->method('__set')->willReturn(true);
        $mock->expects($this->any())->method('__get')->willReturn(true);
        $mock->expects($this->any())->method('first')->willReturn($log);
        $mock->expects($this->any())->method('find')->willReturn(array($log, $log));

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
            ->setMethods(array('__toString', '__get', 'setAttribute'))
            ->getMock();

        $mock->expects($this->any())->method('__toString')->willReturn('test');
        $mock->expects($this->any())->method('__get')->will($this->returnCallback(function ($attribute) {
            switch ($attribute) {
                case 'before':
                case 'after':
                    return array(
                        'test' => array(
                            'label' => 'test',
                            'value' => 'test',
                        ),
                    );

                default:
                    return 'test';
            }
        }));
        $mock->expects($this->any())->method('setAttribute')->willReturn(true);

        return $mock;
    }

    /**
     * Mock FieldsService.
     *
     * @param string $handle
     */
    private function setMockFieldsService($handle)
    {
        $mock = $this->getMockBuilder('Craft\FieldsService')
            ->disableOriginalConstructor()
            ->setMethods(array('getFieldByHandle'))
            ->getMock();

        $field = $this->getMockFieldModel($handle);

        $mock->expects($this->any())->method('getFieldByHandle')->willReturn($field);

        $this->setComponent(craft(), 'fields', $mock);
    }

    /**
     * Mock FieldModel.
     *
     * @param string $handle
     *
     * @return FieldModel
     */
    private function getMockFieldModel($handle)
    {
        $mock = $this->getMockBuilder('Craft\FieldsService')
            ->disableOriginalConstructor()
            ->setMethods(array('__get'))
            ->getMock();

        $mock->expects($this->any())->method('__get')->will($this->returnCallback(function ($attribute) use ($handle) {
            return $handle == 'element' ? AuditLogModel::FieldTypeEntries : AuditLogModel::FieldTypeLightswitch;
        }));

        return $mock;
    }
}
