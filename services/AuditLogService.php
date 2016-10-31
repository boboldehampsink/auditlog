<?php

namespace Craft;

/**
 * Audit Log service.
 *
 * Contains logics for logging
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@nerds.company>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   MIT
 *
 * @link      http://github.com/boboldehampsink
 */
class AuditLogService extends BaseApplicationComponent
{
    /**
     * Show log with criteria.
     *
     * @param object $criteria
     *
     * @return array
     *
     * @codeCoverageIgnore
     */
    public function log($criteria)
    {
        // Build specific criteria
        $criteria = craft()->elements->getCriteria('AuditLog', $criteria);

        // Return occurences
        return $criteria->find();
    }

    /**
     * View a specific log item.
     *
     * @param int $id
     *
     * @return AuditLogModel
     */
    public function view($id)
    {
        // Get log from record
        $log = craft()->elements->getCriteria('AuditLog', array('id' => $id))->first();

        // Create diff
        $diff = array();

        // Loop through content
        foreach ($log->after as $handle => $item) {

            // Set parsed values
            $diff[$handle] = array(
                'label' => $item['label'],
                'changed' => !isset($log['before'][$handle]) || ($item['value'] != $log['before'][$handle]['value']),
                'after' => $item['value'],
                'before' => isset($log['before'][$handle]) ? $log['before'][$handle]['value'] : '',
            );
        }

        // Set diff
        $log->setAttribute('diff', $diff);

        // Return the log
        return $log;
    }

    /**
     * Parse field values.
     *
     * @param string $handle
     * @param mixed  $data
     *
     * @return string
     */
    public function parseFieldData($handle, $data)
    {
        // Do we have any data at all
        if (!is_null($data)) {

            // Get field info
            $field = craft()->fields->getFieldByHandle($handle);

            // If it's a field ofcourse
            if (!is_null($field)) {

                // For some fieldtypes the're special rules
                switch ($field->type) {

                    case AuditLogModel::FieldTypeEntries:
                    case AuditLogModel::FieldTypeCategories:
                    case AuditLogModel::FieldTypeAssets:
                    case AuditLogModel::FieldTypeUsers:

                        // Show names
                        $data = implode(', ', $data->find());

                        break;

                    case AuditLogModel::FieldTypeLightswitch:

                        // Make data human readable
                        switch ($data) {

                            case '0':
                                $data = Craft::t('No');
                                break;

                            case '1':
                                $data = Craft::t('Yes');
                                break;

                        }

                        break;

                }
            }
        } else {

            // Don't return null, return empty
            $data = '';
        }

        // If it's an array, make it a string
        if (is_array($data)) {
            $data = StringHelper::arrayToString(array_filter(ArrayHelper::flattenArray($data), 'strlen'), ', ');
        }

        // If it's an object, make it a string
        if (is_object($data)) {
            $data = StringHelper::arrayToString(array_filter(ArrayHelper::flattenArray(get_object_vars($data)), 'strlen'), ', ');
        }

        return $data;
    }

    /**
     * Check if an element has changed while saving.
     *
     * @param string $elementType
     * @param int    $id
     * @param array  $before
     * @param array  $after
     *
     * @return array
     */
    public function elementHasChanged($elementType, $id, $before, $after)
    {
        // Flatten arrays
        $flatBefore = ArrayHelper::flattenArray($before);
        $flatAfter = ArrayHelper::flattenArray($after);

        // Calculate the diffence
        $flatDiff = array_diff_assoc($flatAfter, $flatBefore);

        // Expand diff again
        $expanded = ArrayHelper::expandArray($flatDiff);

        // Add labels once again
        $diff = array();
        foreach ($expanded as $key => $value) {
            $diff[$key]['label'] = isset($before[$key]) ? $before[$key]['label'] : '';
            $diff[$key]['value'] = $value['value'];
        }

        // If there IS a difference
        if (count($diff)) {

            // Fire an "onElementChanged" event
            $event = new Event($this, array(
                'elementType' => $elementType,
                'id' => $id,
                'diff' => $diff,
            ));
            $this->onElementChanged($event);
        }

        return $diff;
    }

    /**
     * Fires an "onElementChanged" event.
     *
     * @param Event $event
     */
    public function onElementChanged(Event $event)
    {
        $this->raiseEvent('onElementChanged', $event);
    }

    /**
     * Fires an "onFieldChanged" event.
     *
     * @param Event $event
     *
     * @codeCoverageIgnore
     */
    public function onFieldChanged(Event $event)
    {
        $this->raiseEvent('onFieldChanged', $event);
    }
}
