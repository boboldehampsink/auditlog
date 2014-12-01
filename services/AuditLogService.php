<?php
namespace Craft;

class AuditLogService extends BaseApplicationComponent 
{

    public function log($criteria, $viewState = false)
    {
        // Build specific criteria
        $condition = '';
        $params = array();
        
        // Default -7 days
        if(empty($criteria->after)) {
            $criteria->after = array('date' => DateTimeHelper::fromString('-7 days'));
        }
        
        // Default today
        if(empty($criteria->before)) {
            $criteria->before = array('date' => DateTimeHelper::currentUTCDateTime());
        }
        
        // Sorting
        if($viewState) {
            $criteria->order = $viewState['order'] . ' ' . $viewState['sort'];
        }
        
        // Check for date after
        if(!empty($criteria->after)) {
            $condition .= 'dateUpdated > :after and ';
            $params[':after'] = DateTimeHelper::formatTimeForDb($criteria->after);
        }
        
        // Check for date before
        if(!empty($criteria->before)) {
            $condition .= 'dateUpdated < :before and ';
            $params[':before'] = DateTimeHelper::formatTimeForDb($criteria->before);
        }
        
        // Check for type
        if(!empty($criteria->type)) {
            $condition .= 'type = :type and ';
            $params[':type'] = $criteria->type;
        }
        
        // Check for status
        if(!empty($criteria->status)) {
            $condition .= 'status = :status and ';
            $params[':status'] = $criteria->status;
        }
        
        // Search
        if(!empty($criteria->search)) {
            $condition .= 'origin like :search and ';
            $params[':search'] = '%' . addcslashes($criteria->search, '%_') . '%';
        }
                    
        // Get logs from record
        return AuditLogModel::populateModels(AuditLogRecord::model()->findAll(array(
            'order'     => $criteria->order,
            'condition' => substr($condition, 0, -5),
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
    
    // Parse field values
    public function parseFieldData($handle, $data) 
    {
    
        // Do we have any data at all
        if(!is_null($data)) {
        
            // Get field info
            $field = craft()->fields->getFieldByHandle($handle);
            
            // If it's a field ofcourse
            if(!is_null($field)) {
            
                // For some fieldtypes the're special rules
                switch($field->type) {
                
                    case AuditLogModel::FieldTypeEntries:
                    case AuditLogModel::FieldTypeCategories:
                    case AuditLogModel::FieldTypeAssets:
                    case AuditLogModel::FieldTypeUsers:
                        
                        // Show names
                        $data = implode(', ', $data->find());
                        
                        break;
                    
                    case AuditLogModel::FieldTypeLightswitch:
                    
                        // Make data human readable
                        switch($data) {
                        
                            case "0":
                                $data = Craft::t("No");
                                break;
                            
                            case "1":
                                $data = Craft::t("Yes");
                                break;
                        
                        }
                        
                        break;
                
                }
            
            }
        
        } else {
        
            // Don't return null, return empty
            $data = "";
        
        }
        
        // If it's an array, make it a string
        if(is_array($data)) {
            $data = StringHelper::arrayToString($data);
        }
        
        // If it's an object, make it a string
        if(is_object($data)) {
            $data = StringHelper::arrayToString(get_object_vars($data));
        }
        
        return $data;
    
    }
    
}