<?php

namespace Craft;

/**
 * Audit Log User Service Test.
 *
 * Unit Tests for the Audit Log User Service
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@nerds.company>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   MIT
 *
 * @link      http://github.com/boboldehampsink
 *
 * @coversDefaultClass Craft\AuditLog_UserService
 * @covers ::<!public>
 */
class AuditLog_UserServiceTest extends BaseTest
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
        require_once __DIR__.'/../services/AuditLog_UserService.php';
        require_once __DIR__.'/../records/AuditLogRecord.php';
    }

    /**
     * {@inheritdoc}
     */
    public function teardown()
    {
        parent::teardown();
        AuditLogRecord::$db = craft()->db;
    }

    /**
     * Test onBeforeSaveUser.
     *
     * @param UserModel $user
     * @param bool      $isNewUser
     *
     * @covers ::onBeforeSaveUser
     * @covers ::fields
     * @dataProvider provideSaveUserEvents
     */
    final public function testOnBeforeSaveUser(UserModel $user, $isNewUser)
    {
        $this->setMockAuditLogService();

        $service = new AuditLog_UserService();
        $event = new Event($service, array(
            'user' => $user,
            'isNewUser' => $isNewUser,
        ));
        $service->onBeforeSaveUser($event);

        $this->assertArrayHasKey('username', $service->before);
    }

    /**
     * Test onSaveUser.
     *
     * @param UserModel $user
     * @param bool      $isNewUser
     *
     * @covers ::onSaveUser
     * @covers ::fields
     * @dataProvider provideSaveUserEvents
     */
    final public function testOnSaveUser(UserModel $user, $isNewUser)
    {
        AuditLogRecord::$db = $this->setMockDbConnection();

        $this->setMockAuditLogService();
        $this->setMockUserGroupsService();
        $this->setMockUserSessionService();

        $service = new AuditLog_UserService();
        $event = new Event($service, array(
            'user' => $user,
            'isNewUser' => $isNewUser,
        ));
        $service->onSaveUser($event);

        $this->assertArrayHasKey('username', $service->after);
    }

    /**
     * Test onBeforeDeleteUser.
     *
     * @param UserModel $user
     *
     * @covers ::onBeforeDeleteUser
     * @covers ::fields
     * @dataProvider provideSaveUserEvents
     */
    final public function testOnBeforeDeleteUser(UserModel $user)
    {
        AuditLogRecord::$db = $this->setMockDbConnection();

        $this->setMockAuditLogService();
        $this->setMockUserGroupsService();
        $this->setMockUserSessionService();

        $service = new AuditLog_UserService();
        $event = new Event($service, array(
            'user' => $user,
        ));
        $service->onBeforeDeleteUser($event);

        $this->assertArrayHasKey('username', $service->after);
    }

    /**
     * Provide saveUser events.
     *
     * @return array
     */
    final public function provideSaveUserEvents()
    {
        $user = $this->getMockUserModel();

        return array(
            'With new user' => array($user, true),
            'Without new user' => array($user, false),
            'With posted groups' => call_user_func(function () use ($user) {
                $this->setMockRequestService();

                return array($user, false);
            }),
        );
    }

    /**
     * Mock RequestService.
     */
    private function setMockRequestService()
    {
        $mock = $this->getMockBuilder('Craft\HttpRequestService')
            ->disableOriginalConstructor()
            ->setMethods(array('getPost'))
            ->getMock();

        $mock->expects($this->any())->method('getPost')->willReturn(array(1, 2));

        $this->setComponent(craft(), 'request', $mock);
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

        $this->setComponent(craft(), 'db', $mock);

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
            ->setMethods(array('execute', 'prepare'))
            ->getMock();

        $mock->expects($this->any())->method('execute')->willReturn(true);
        $mock->expects($this->any())->method('prepare')->willReturn(true);

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
        $builder = $this->getMockCommandBuilder($connection);

        $mock->expects($this->any())->method('getTable')->willReturn($table);
        $mock->expects($this->any())->method('getCommandBuilder')->willReturn($builder);

        return $mock;
    }

    /**
     * Mock CdbCommandBuilder.
     *
     * @param DbConnection $connection
     *
     * @return \CdbCommandBuilder
     */
    private function getMockCommandBuilder(DbConnection $connection)
    {
        $mock = $this->getMockBuilder('\CdbCommandBuilder')
            ->disableOriginalConstructor()
            ->setMethods(array('createInsertCommand'))
            ->getMock();

        $command = $this->getMockDbCommand($connection);

        $mock->expects($this->any())->method('createInsertCommand')->willReturn($command);

        return $mock;
    }

    /**
     * Mock UserGroupsService.
     */
    private function setMockUserGroupsService()
    {
        $mock = $this->getMockBuilder('Craft\UserGroupsService')
            ->disableOriginalConstructor()
            ->setMethods(array('getGroupsByUserId', 'getGroupById'))
            ->getMock();

        $group = $this->getMockUserGroupModel();

        $mock->expects($this->any())->method('getGroupsByUserId')->willReturn(array($group));
        $mock->expects($this->any())->method('getGroupById')->willReturn($group);

        $this->setComponent(craft(), 'userGroups', $mock);
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
}
