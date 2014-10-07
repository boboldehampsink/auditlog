<?php
namespace Craft;

class AuditLogService extends BaseApplicationComponent 
{

    public function log()
    {
    
        // Get logs from record
        return AuditLogRecord::model()->findAll(array(
            'order' => 'id desc'
        ));
    
    }
    
    public function view($id)
    {
    
        // Get log from record
        $log = AuditLogRecord::model()->findByPk($id)->getAttributes();
        
        // Gather diff report
        $log['diff'] = array();
                
        // Loop through content
        foreach($log['after'] as $handle => $item) 
        {
                                
            // Set parsed values
            $log['diff'][$handle] = array(
                'label'   => $item['label'],
                'changed' => ($item['value'] != $log['before'][$handle]['value']),
                'after'   => $item['value'],
                'before'  => $log['before'][$handle]['value']
            );
        
        }
        
        // Get user model
        $log['user'] = craft()->users->getUserById($log['userId']);
        
        // Return the log
        return $log;
    
    }
    
}