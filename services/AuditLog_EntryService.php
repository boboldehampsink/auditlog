<?php

namespace Craft;

/**
 * Audit Log Entry service.
 *
 * Contains logics for logging entries
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@nerds.company>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   MIT
 *
 * @link      http://github.com/boboldehampsink
 */
class AuditLog_EntryService extends BaseApplicationComponent
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
        craft()->on('entries.onBeforeSaveEntry', array($this, 'onBeforeSaveEntry'));

        // Get values after saving
        craft()->on('entries.onSaveEntry', array($this, 'onSaveEntry'));

        // Get values before deleting
        craft()->on('entries.onBeforeDeleteEntry', array($this, 'onBeforeDeleteEntry'));
    }

    /**
     * Handle the onBeforeSaveEntry event.
     *
     * @param Event $event
     */
    public function onBeforeSaveEntry(Event $event)
    {
        // Get entry id to save
        $id = $event->params['entry']->id;

        if (!$event->params['isNewEntry']) {

            // Get old entry from db
            $entry = EntryModel::populateModel(EntryRecord::model()->findById($id));

            // Get fields
            $this->before = $this->fields($entry);
        } else {

            // Get fields
            $this->before = $this->fields($event->params['entry'], true);
        }
    }

    /**
     * Handle the onSaveEntry event.
     *
     * @param Event $event
     */
    public function onSaveEntry(Event $event)
    {
        // Get saved entry
        $entry = $event->params['entry'];

        // Get fields
        $this->after = $this->fields($entry);

        // New row
        $log = new AuditLogRecord();

        // Get user
        $user = craft()->userSession->getUser();

        // Set user id
        $log->userId = $user ? $user->id : null;

        // Set element type
        $log->type = ElementType::Entry;

        // Set origin
        $log->origin = craft()->request->isCpRequest() ? craft()->config->get('cpTrigger').'/'.craft()->request->path : craft()->request->path;

        // Set before
        $log->before = $this->before;

        // Set after
        $log->after = $this->after;

        // Set status
        $log->status = ($event->params['isNewEntry'] ? AuditLogModel::CREATED : AuditLogModel::MODIFIED);

        // Save row
        $log->save(false);

        // Callback
        craft()->auditLog->elementHasChanged(ElementType::Entry, $entry->id, $this->before, $this->after);
    }

    /**
     * Handle the onBeforeDeleteEntry event.
     *
     * @param Event $event
     */
    public function onBeforeDeleteEntry(Event $event)
    {
        // Get deleted entry
        $entry = $event->params['entry'];

        // Get fields
        $this->before = $this->fields($entry);
        $this->after = $this->fields($entry, true);

        // New row
        $log = new AuditLogRecord();

        // Set user id
        $log->userId = craft()->userSession->getUser()->id;

        // Set element type
        $log->type = ElementType::Entry;

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
        craft()->auditLog->elementHasChanged(ElementType::Entry, $entry->id, $this->before, $this->after);
    }

    /**
     * Parse entry fields.
     *
     * @param EntryModel $entry
     * @param bool       $empty
     *
     * @return array
     */
    public function fields(EntryModel $entry, $empty = false)
    {

        // Always save id and title
        $fields = array(
            'id' => array(
                'label' => Craft::t('ID'),
                'value' => $entry->id,
            ),
            'title' => array(
                'label' => Craft::t('Title'),
                'value' => (string) $entry->getTitle(),
            ),
            'section' => array(
                'label' => Craft::t('Section'),
                'value' => (string) $entry->getSection(),
            ),
        );

        // Get element type
        $elementType = craft()->elements->getElementType(ElementType::Entry);

        // Get nice attributes
        $availableAttributes = $elementType->defineAvailableTableAttributes();

        // Make 'em fit
        $attributes = array();
        foreach ($availableAttributes as $key => $result) {
            $attributes[$key] = $result['label'];
        }

        // Get static "fields"
        foreach ($entry->getAttributes() as $handle => $value) {

            // Only show nice attributes
            if (array_key_exists($handle, $attributes)) {
                $fields[$handle] = array(
                    'label' => $attributes[$handle],
                    'value' => StringHelper::arrayToString(is_array($value) ? array_filter(ArrayHelper::flattenArray($value), 'strlen') : $value, ', '),
                );
            }
        }

        // Get fieldlayout
        $entrytype = $entry->getType();
        if ($entrytype) {
            $tabs = craft()->fields->getLayoutById($entrytype->fieldLayoutId)->getTabs();
            foreach ($tabs as $tab) {
                foreach ($tab->getFields() as $field) {

                    // Get field values
                    $field = $field->getField();
                    $handle = $field->handle;
                    $label = $field->name;
                    $value = $empty ? '' : craft()->auditLog->parseFieldData($handle, $entry->$handle);

                    // Set on fields
                    $fields[$handle] = array(
                        'label' => $label,
                        'value' => $value,
                    );
                }
            }
        }

        // Return
        return $fields;
    }
}
