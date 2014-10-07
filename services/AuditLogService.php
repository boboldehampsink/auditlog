<?php
namespace Craft;

class AuditLogService extends BaseApplicationComponent 
{

    public function log($criteria)
    {
    
        // Build specific criteria
        $condition = '';
        $params = array();
        if($criteria->type) {
            $condition .= 'type = :type and ';
            $params[':type'] = $criteria->type;
        }
        if($criteria->status) {
            $condition .= 'status = :status and ';
            $params[':status'] = $criteria->status;
        }
        if($criteria->search) {
            $condition .= 'origin like :search and ';
            $params[':search'] = '%' . addcslashes($criteria->search, '%_') . '%';
        }
        $condition = substr($condition, 0, -5);
    
        // Get logs from record
        return AuditLogModel::populateModels(AuditLogRecord::model()->findAll(array(
            'order'     => 'id desc',
            'limit'     => $criteria->limit,
            'offset'    => $criteria->offset,
            'condition' => $condition,
            'params'    => $params
        )));
    
    }
    
    public function view($id)
    {
    
        // Get log from record
        $log = AuditLogModel::populateModel(AuditLogRecord::model()->findByPk($id));
        
        // Create diff
        $diff = array();
                
        // Loop through content
        foreach($log->after as $handle => $item) 
        {
                                
            // Set parsed values
            $diff[$handle] = array(
                'label'   => $item['label'],
                'changed' => ($item['value'] != $log['before'][$handle]['value']),
                'after'   => $item['value'],
                'before'  => $log['before'][$handle]['value']
            );
        
        }
        
        // Set diff
        $log->setAttribute('diff', $diff);
        
        // Return the log
        return $log;
    
    }
    
}