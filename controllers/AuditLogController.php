<?php

namespace Craft;

/**
 * Audit Log Controller.
 *
 * Handles requests for the Audit Log
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@nerds.company>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   MIT
 *
 * @link      http://github.com/boboldehampsink/auditlog
 */
class AuditLogController extends BaseController
{
    /**
     * Download a CSV of the changes list.
     */
    public function actionDownload()
    {
        // Get criteria
        $criteria = craft()->elements->getCriteria('AuditLog', craft()->request->getParam('criteria'));

        // Get element type
        $elementType = craft()->elements->getElementType('AuditLog');

        // Get order and sort
        $viewState = craft()->request->getParam('viewState', array(
            'order' => 'id',
            'sort' => 'desc',
        ));

        // Set sort on criteria
        $criteria->order = $viewState['order'] == 'score' ? 'id' : $viewState['order'].' '.$viewState['sort'];

        // Did we search?
        $criteria->search = craft()->request->getParam('search');

        // Get source
        $sources = $elementType->getSources();
        $source = craft()->request->getParam('source', '*');

        // Set type
        $criteria->type = $source != '*' ? $sources[$source]['criteria']['type'] : null;

        // Get data
        $log = craft()->auditLog->log($criteria);

        // Set status attribute
        $attributes = array('status' => Craft::t('Status'));

        // Get nice attributes
        $availableAttributes = $elementType->defineAvailableTableAttributes();

        // Make 'em fit
        foreach ($availableAttributes as $key => $result) {
            $attributes[$key] = $result['label'];
        }

        // Ditch the changes button
        unset($attributes['changes']);

        // Re-order data
        $data = StringHelper::convertToUTF8('"'.implode('","', $attributes)."\"\r\n");
        foreach ($log as $element) {

            // Gather parsed fields
            $fields = array();

            // Parse fields
            foreach ($attributes as $handle => $field) {
                $fields[] = $handle == 'status' ? $elementType->getStatuses()[$element->$handle] : strip_tags($elementType->getTableAttributeHtml($element, $handle));
            }

            // Set data
            $data .= StringHelper::convertToUTF8('"'.implode('","', $fields)."\"\r\n");
        }

        // Download the file
        craft()->request->sendFile('auditlog.csv', $data, array('forceDownload' => true, 'mimeType' => 'text/csv'));
    }
}
