<?php
namespace Craft;

/**
 * Audit Log Category service
 *
 * Contains logics for logging categories
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, author
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://github.com/boboldehampsink
 * @package   craft.plugins.auditlog
 */
class AuditLog_CategoryService extends BaseApplicationComponent
{

    /**
     * Catch value before saving
     * @var array
     */
    public $before = array();

    /**
     * Catch value after saving
     * @var array
     */
    public $after  = array();

    /**
     * Initialize the category saving/deleting events
     */
    public function log()
    {

        // Get values before saving
        craft()->on('categories.onBeforeSaveCategory', function (Event $event) {

            // Get category id to save
            $id = $event->params['category']->id;

            if (!$event->params['isNewCategory']) {

                // Get old category from db
                $category = CategoryModel::populateModel(CategoryRecord::model()->findById($id));

                // Get fields
                craft()->auditLog_category->before = craft()->auditLog_category->fields($category);
            } else {

                // Get fields
                craft()->auditLog_category->before = craft()->auditLog_category->fields($event->params['category'], true);
            }

        });

        // Get values after saving
        craft()->on('categories.onSaveCategory', function (Event $event) {

            // Get saved category
            $category = $event->params['category'];

            // Get fields
            craft()->auditLog_category->after = craft()->auditLog_category->fields($category);

            // New row
            $log = new AuditLogRecord();

            // Get user
            $user = craft()->userSession->getUser();

            // Set user id
            $log->userId = $user ? $user->id : null;

            // Set element type
            $log->type = ElementType::Category;

            // Set origin
            $log->origin = craft()->request->isCpRequest() ? craft()->config->get('cpTrigger').'/'.craft()->request->path : craft()->request->path;

            // Set before
            $log->before = craft()->auditLog_category->before;

            // Set after
            $log->after = craft()->auditLog_category->after;

            // Set status
            $log->status = ($event->params['isNewCategory'] ? AuditLogModel::CREATED : AuditLogModel::MODIFIED);

            // Save row
            $log->save(false);

            // Callback
            craft()->auditLog->elementHasChanged(ElementType::Category, $category->id, craft()->auditLog_category->before, craft()->auditLog_category->after);

        });

        // Get values before deleting
        craft()->on('categories.onBeforeDeleteCategory', function (Event $event) {

            // Get deleted category
            $category = $event->params['category'];

            // Get fields
            craft()->auditLog_category->before = craft()->auditLog_category->fields($category);
            craft()->auditLog_category->after  = craft()->auditLog_category->fields($category, true);

            // New row
            $log = new AuditLogRecord();

            // Set user id
            $log->userId = craft()->userSession->getUser()->id;

            // Set element type
            $log->type = ElementType::Category;

            // Set origin
            $log->origin = craft()->request->isCpRequest() ? craft()->config->get('cpTrigger').'/'.craft()->request->path : craft()->request->path;

            // Set before
            $log->before = craft()->auditLog_category->before;

            // Set after
            $log->after = craft()->auditLog_category->after;

            // Set status
            $log->status = AuditLogModel::DELETED;

            // Save row
            $log->save(false);

            // Callback
            craft()->auditLog->elementHasChanged(ElementType::Category, $category->id, craft()->auditLog_category->before, craft()->auditLog_category->after);

        });
    }

    /**
     * Parse category fields
     * @param  CategoryModel $category
     * @param  boolean       $empty
     * @return array
     */
    public function fields(CategoryModel $category, $empty = false)
    {

        // Always save id
        $fields = array(
            'id' => array(
                'label' => Craft::t('ID'),
                'value' => $category->id,
            ),
            'title' => array(
                'label' => Craft::t('Title'),
                'value' => $category->title,
            ),
            'group' => array(
                'label' => Craft::t('Group'),
                'value' => $category->group->name,
            ),
        );

        // Get element type
        $elementType = craft()->elements->getElementType(ElementType::Category);

        // Get nice attributes
        $attributes = $elementType->defineTableAttributes();

        // Get static "fields"
        foreach ($category->getAttributes() as $handle => $value) {

            // Only show nice attributes
            if (array_key_exists($handle, $attributes)) {
                $fields[$handle] = array(
                    'label' => $attributes[$handle],
                    'value' => StringHelper::arrayToString(is_array($value) ? array_filter(ArrayHelper::flattenArray($value), 'strlen') : $value, ', '),
                );
            }
        }

        // Get fieldlayout
        foreach (craft()->fields->getLayoutByType(ElementType::Category)->getFields() as $field) {

            // Get field values
            $field = $field->getField();
            $handle = $field->handle;
            $label = $field->name;
            $value = $empty ? '' : craft()->auditLog->parseFieldData($handle, $category->$handle);

            // Set on fields
            $fields[$handle] = array(
                'label' => $label,
                'value' => $value,
            );
        }

        // Return
        return $fields;
    }
}
