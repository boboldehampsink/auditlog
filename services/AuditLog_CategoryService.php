<?php

namespace Craft;

/**
 * Audit Log Category service.
 *
 * Contains logics for logging categories
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@nerds.company>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   MIT
 *
 * @link      http://github.com/boboldehampsink
 */
class AuditLog_CategoryService extends BaseApplicationComponent
{
    /**
     * Catch value before saving.
     *
     * @var array
     */
    public $before = array();

    /**
     * Catch value after saving.
     *
     * @var array
     */
    public $after = array();

    /**
     * Initialize the category saving/deleting events.
     *
     * @codeCoverageIgnore
     */
    public function log()
    {
        // Get values before saving
        craft()->on('categories.onBeforeSaveCategory', array($this, 'onBeforeSaveCategory'));

        // Get values after saving
        craft()->on('categories.onSaveCategory', array($this, 'onSaveCategory'));

        // Get values before deleting
        craft()->on('categories.onBeforeDeleteCategory', array($this, 'onBeforeDeleteCategory'));
    }

    /**
     * Handle the onBeforeSaveCategory event.
     *
     * @param Event $event
     */
    public function onBeforeSaveCategory(Event $event)
    {
        // Get category id to save
        $id = $event->params['category']->id;

        if (!$event->params['isNewCategory']) {

            // Get old category from db
            $category = CategoryModel::populateModel(CategoryRecord::model()->findById($id));

            // Get fields
            $this->before = $this->fields($category);
        } else {

            // Get fields
            $this->before = $this->fields($event->params['category'], true);
        }
    }

    /**
     * Handle the onSaveCategory event.
     *
     * @param Event $event
     */
    public function onSaveCategory(Event $event)
    {
        // Get saved category
        $category = $event->params['category'];

        // Get fields
        $this->after = $this->fields($category);

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
        $log->before = $this->before;

        // Set after
        $log->after = $this->after;

        // Set status
        $log->status = ($event->params['isNewCategory'] ? AuditLogModel::CREATED : AuditLogModel::MODIFIED);

        // Save row
        $log->save(false);

        // Callback
        craft()->auditLog->elementHasChanged(ElementType::Category, $category->id, $this->before, $this->after);
    }

    /**
     * Handle the onBeforeDeleteCategory event.
     *
     * @param Event $event
     */
    public function onBeforeDeleteCategory(Event $event)
    {
        // Get deleted category
        $category = $event->params['category'];

        // Get fields
        $this->before = $this->fields($category);
        $this->after = $this->fields($category, true);

        // New row
        $log = new AuditLogRecord();

        // Set user id
        $log->userId = craft()->userSession->getUser()->id;

        // Set element type
        $log->type = ElementType::Category;

        // Set origin
        $log->origin = craft()->request->isCpRequest() ? craft()->config->get('cpTrigger').'/'.craft()->request->path : craft()->request->path;

        // Set before
        $log->before = $this->before;

        // Set after
        $log->after = $this->after;

        // Set status
        $log->status = AuditLogModel::DELETED;

        // Save row
        $log->save(false);

        // Callback
        craft()->auditLog->elementHasChanged(ElementType::Category, $category->id, $this->before, $this->after);
    }

    /**
     * Parse category fields.
     *
     * @param CategoryModel $category
     * @param bool          $empty
     *
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
                'value' => (string) $category->getTitle(),
            ),
            'group' => array(
                'label' => Craft::t('Group'),
                'value' => (string) $category->getGroup(),
            ),
        );

        // Get element type
        $elementType = craft()->elements->getElementType(ElementType::Category);

        // Get nice attributes
        $availableAttributes = $elementType->defineAvailableTableAttributes();

        // Make 'em fit
        $attributes = array();
        foreach ($availableAttributes as $key => $result) {
            $attributes[$key] = $result['label'];
        }

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
