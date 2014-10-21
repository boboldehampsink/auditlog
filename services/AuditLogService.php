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
                        $data = implode(', ', is_array($data) ? $data : []);

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

        return $data;

    }

}
