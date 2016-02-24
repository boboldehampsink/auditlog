<?php

namespace Craft;

/**
 * Audit Log Category Service Test.
 *
 * Unit Tests for the Audit Log Category Service
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@nerds.company>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   MIT
 *
 * @link      http://github.com/boboldehampsink
 *
 * @coversDefaultClass Craft\AuditLog_CategoryService
 * @covers ::<!public>
 */
class AuditLog_CategoryServiceTest extends BaseTest
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
        require_once __DIR__.'/../services/AuditLog_CategoryService.php';
        require_once __DIR__.'/../records/AuditLogRecord.php';
    }

    /**
     * Test onSaveCategory.
     *
     * @param CategoryModel $category
     * @param bool          $isNewCategory
     *
     * @covers ::onBeforeSaveCategory
     * @covers ::onSaveCategory
     * @covers ::fields
     * @dataProvider provideSaveCategoryEvents
     */
    final public function testOnSaveCategory(CategoryModel $category, $isNewCategory)
    {
        AuditLogRecord::$db = $this->setMockDbConnection();

        $this->setMockAuditLogService();
        $this->setMockUserSessionService();
        $this->setMockFieldsService();
        $this->setMockLocalizationService();

        $service = new AuditLog_CategoryService();
        $event = new Event($service, array(
            'category' => $category,
            'isNewCategory' => $isNewCategory,
        ));
        $service->onBeforeSaveCategory($event);
        $service->onSaveCategory($event);

        $this->assertArrayHasKey('id', $service->after);
    }

    /**
     * Test onBeforeDeleteCategory.
     *
     * @param CategoryModel $category
     *
     * @covers ::onBeforeDeleteCategory
     * @covers ::fields
     * @dataProvider provideSaveCategoryEvents
     */
    final public function testOnBeforeDeleteCategory(CategoryModel $category)
    {
        AuditLogRecord::$db = $this->setMockDbConnection();

        $this->setMockAuditLogService();
        $this->setMockUserSessionService();
        $this->setMockFieldsService();

        $service = new AuditLog_CategoryService();
        $event = new Event($service, array(
            'category' => $category,
        ));
        $service->onBeforeDeleteCategory($event);

        $this->assertArrayHasKey('id', $service->after);
    }

    /**
     * Provide saveCategory events.
     *
     * @return array
     */
    final public function provideSaveCategoryEvents()
    {
        return array(
            'With new category' => array($this->getMockCategoryModel(), true),
            'Without new category' => array($this->getMockCategoryModel(), false),
        );
    }

    /**
     * Mock CategoryModel.
     *
     * @return CategoryModel
     */
    private function getMockCategoryModel()
    {
        $mock = $this->getMockBuilder('Craft\CategoryModel')
            ->disableOriginalConstructor()
            ->setMethods(array('__get', 'getAttributes', 'getTitle', 'getGroup'))
            ->getMock();

        $mock->expects($this->any())->method('__get')->willReturn('test');
        $mock->expects($this->any())->method('getAttributes')->willReturn(array(
            array('id' => 'test'),
        ));
        $mock->expects($this->any())->method('getTitle')->willReturn('test');
        $mock->expects($this->any())->method('getGroup')->will($this->returnSelf());

        return $mock;
    }

    /**
     * Mock AuditLogService.
     */
    private function setMockAuditLogService()
    {
        $mock = $this->getMockBuilder('Craft\AuditLogService')
            ->disableOriginalConstructor()
            ->setMethods(array('elementHasChanged', 'parseFieldData'))
            ->getMock();

        $mock->expects($this->any())->method('elementHasChanged')->willReturn(true);
        $mock->expects($this->any())->method('parseFieldData')->willReturn('test');

        $this->setComponent(craft(), 'auditLog', $mock);
    }

    /**
     * Mock DbConnection.
     *
     * @return DbConnection
     */
    private function setMockDbConnection()
    {
        $mock = $this->getMockBuilder('Craft\DbConnection')
            ->disableOriginalConstructor()
            ->setMethods(array('createCommand', 'getSchema'))
            ->getMock();
        $mock->autoConnect = false; // Do not auto connect

        $command = $this->getMockDbCommand($mock);
        $schema = $this->getMockDbSchema($mock);

        $mock->expects($this->any())->method('createCommand')->willReturn($command);
        $mock->expects($this->any())->method('getSchema')->willReturn($schema);

        return $mock;
    }

    /**
     * Mock DbCommand.
     *
     * @param DbConnection $connection
     *
     * @return DbCommand
     */
    private function getMockDbCommand(DbConnection $connection)
    {
        $mock = $this->getMockBuilder('Craft\DbCommand')
            ->setConstructorArgs(array($connection))
            ->setMethods(array('execute', 'prepare', 'queryRow', 'queryAll'))
            ->getMock();

        $mock->expects($this->any())->method('execute')->willReturn(true);
        $mock->expects($this->any())->method('prepare')->willReturn(true);
        $mock->expects($this->any())->method('queryRow')->willReturn(array('username' => 'test'));
        $mock->expects($this->any())->method('queryAll')->willReturn(array(array('username' => 'test')));

        return $mock;
    }

    /**
     * Mock MysqlSchema.
     *
     * @param DbConncetion $connection
     *
     * @return MysqlSchema
     */
    private function getMockDbSchema(DbConnection $connection)
    {
        $mock = $this->getMockBuilder('Craft\MysqlSchema')
            ->disableOriginalConstructor()
            ->setMethods(array('getTable', 'getCommandBuilder'))
            ->getMock();

        $table = new \CMysqlTableSchema();
        $table->columns = array(
            'id' => new \CMysqlColumnSchema(),
            'userId' => new \CMysqlColumnSchema(),
            'type' => new \CMysqlColumnSchema(),
            'origin' => new \CMysqlColumnSchema(),
            'before' => new \CMysqlColumnSchema(),
            'after' => new \CMysqlColumnSchema(),
            'status' => new \CMysqlColumnSchema(),
            'type' => new \CMysqlColumnSchema(),
            'dateCreated' => new \CMysqlColumnSchema(),
            'dateUpdated' => new \CMysqlColumnSchema(),
            'uid' => new \CMysqlColumnSchema(),
        );
        $builder = $this->getMockCommandBuilder($connection, $mock);

        $mock->expects($this->any())->method('getTable')->willReturn($table);
        $mock->expects($this->any())->method('getCommandBuilder')->willReturn($builder);

        return $mock;
    }

    /**
     * Mock CdbCommandBuilder.
     *
     * @param DbConnection $connection
     * @param MysqlSchema  $schema
     *
     * @return \CdbCommandBuilder
     */
    private function getMockCommandBuilder(DbConnection $connection, MysqlSchema $schema)
    {
        $mock = $this->getMockBuilder('\CdbCommandBuilder')
            ->disableOriginalConstructor()
            ->setMethods(array('createInsertCommand', 'createPkCommand', 'createPkCriteria', 'createFindCommand', 'applyLimit', 'getSchema', 'getDbConnection', 'bindValues'))
            ->getMock();

        $command = $this->getMockDbCommand($connection);

        $mock->expects($this->any())->method('createInsertCommand')->willReturn($command);
        $mock->expects($this->any())->method('createPkCommand')->willReturn($command);
        $mock->expects($this->any())->method('createPkCriteria')->willReturn($command);
        $mock->expects($this->any())->method('createFindCommand')->willReturn($command);
        $mock->expects($this->any())->method('getSchema')->willReturn($schema);
        $mock->expects($this->any())->method('getDbConnection')->willReturn($connection);

        return $mock;
    }

    /**
     * Mock UserGroupModel.
     *
     * @return UserGroupModel
     */
    private function getMockUserGroupModel()
    {
        $mock = $this->getMockBuilder('Craft\UserGroupModel')
            ->disableOriginalConstructor()
            ->setMethods(array('__toString'))
            ->getMock();

        $mock->expects($this->any())->method('__toString')->willReturn('test');

        return $mock;
    }

    /**
     * Mock UserSessionService.
     */
    private function setMockUserSessionService()
    {
        $mock = $this->getMockBuilder('Craft\UserSessionService')
            ->disableOriginalConstructor()
            ->setMethods(array('getUser'))
            ->getMock();

        $user = $this->getMockUserModel();

        $mock->expects($this->any())->method('getUser')->willReturn($user);

        $this->setComponent(craft(), 'userSession', $mock);
    }

    /**
     * Mock UserModel.
     *
     * @return UserModel
     */
    private function getMockUserModel()
    {
        $mock = $this->getMockBuilder('Craft\UserModel')
            ->disableOriginalConstructor()
            ->setMethods(array('__get'))
            ->getMock();

        $mock->expects($this->any())->method('__get')->willReturn('test');

        return $mock;
    }

    /**
     * Mock FieldsService.
     */
    private function setMockFieldsService()
    {
        $mock = $this->getMockBuilder('Craft\FieldsService')
            ->disableOriginalConstructor()
            ->setMethods(array('getLayoutByType', 'getFieldByHandle', 'getAllFields'))
            ->getMock();

        $layout = $this->getMockFieldLayoutModel();
        $field = $this->getMockFieldModel();

        $mock->expects($this->any())->method('getLayoutByType')->willReturn($layout);
        $mock->expects($this->any())->method('getFieldByHandle')->willReturn($field);
        $mock->expects($this->any())->method('getAllFields')->willReturn(array($field));

        $this->setComponent(craft(), 'fields', $mock);
    }

    /**
     * Mock FieldLayoutModel.
     *
     * @return FieldLayoutModel
     */
    private function getMockFieldLayoutModel()
    {
        $mock = $this->getMockBuilder('Craft\FieldLayoutModel')
            ->disableOriginalConstructor()
            ->setMethods(array('getFields'))
            ->getMock();

        $fields = array($this->getMockFieldLayoutFieldModel());

        $mock->expects($this->any())->method('getFields')->willReturn($fields);

        return $mock;
    }

    /**
     * Mock FieldLayoutFieldModel.
     *
     * @return FieldLayoutFieldModel
     */
    private function getMockFieldLayoutFieldModel()
    {
        $mock = $this->getMockBuilder('Craft\FieldLayoutFieldModel')
            ->disableOriginalConstructor()
            ->setMethods(array('getField'))
            ->getMock();

        $field = $this->getMockFieldModel();

        $mock->expects($this->any())->method('getField')->willReturn($field);

        return $mock;
    }

    /**
     * Mock FieldModel.
     *
     * @return FieldModel
     */
    private function getMockFieldModel()
    {
        $mock = $this->getMockBuilder('Craft\FieldModel')
            ->disableOriginalConstructor()
            ->setMethods(array('__get'))
            ->getMock();

        $mock->expects($this->any())->method('__get')->willReturn('test');

        return $mock;
    }

    /**
     * Mock LocalizationService.
     */
    private function setMockLocalizationService()
    {
        $mock = $this->getMockBuilder('Craft\LocalizationService')
            ->disableOriginalConstructor()
            ->setMethods(array('getPrimarySiteLocaleId'))
            ->getMock();

        $mock->expects($this->any())->method('getPrimarySiteLocaleId')->willReturn('nl');

        $this->setComponent(craft(), 'i18n', $mock);
    }
}
