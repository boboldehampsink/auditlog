<?php
namespace Craft;

class AuditLogTest extends BaseTest 
{
    
    public function setUp()
    {
    
        // PHPUnit complains about not settings this
        date_default_timezone_set('UTC');
    
        // Get dependencies
        $dir = __DIR__;
        $map = array(
            '\\Craft\\AuditLogModel'   => '/../models/AuditLogModel.php',
            '\\Craft\\AuditLogRecord'  => '/../records/AuditLogRecord.php',
            '\\Craft\\AuditLogService' => '/../services/AuditLogService.php'
        );

        // Inject them
        foreach($map as $classPath => $filePath) {
            if(!class_exists($classPath, false)) {
                require_once($dir . $filePath);
            }
        }
    
        // Set components we're going to use
        $this->setComponent(craft(), 'auditLog', new AuditLogService);
    
    } 
    
    public function testActionDownload() 
    {
    
        // Get first log item
        $log = craft()->auditLog->view(1);
        
        // Only test if already set
        if($log) {
        
            // $log is a model, want to break that down
            $result = craft()->auditLog->parseFieldData('title', $log);
            
            // Result is always a string
            $this->assertInternalType('string', $result);
        
        }
        
    }
    
}