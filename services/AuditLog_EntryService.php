<?php
namespace Craft;

class AuditLog_EntryService extends BaseApplicationComponent 
{

    public $before = array();
    public $after  = array();

    public function log()
    {
    
        // Get values before saving
        craft()->on('entries.onBeforeSaveEntry', function(Event $event) {
        
            // Get entry id to save
            $id = $event->params['entry']->id;
            
            if(!$event->params['isNewEntry']) {
            
                // Get old entry from db
                $entry = EntryModel::populateModel(EntryRecord::model()->findById($id));
                
                // Get fields
                craft()->auditLog_entry->before = craft()->auditLog_entry->fields($entry);
                
            } else {
            
                // Get fields
                craft()->auditLog_entry->before = craft()->auditLog_entry->fields($event->params['entry'], true);
            
            }
                    
        });
    
        // Get values after saving
        craft()->on('entries.onSaveEntry', function(Event $event) {
        
            // Get saved entry
            $entry = $event->params['entry'];

            // Get fields
            craft()->auditLog_entry->after = craft()->auditLog_entry->fields($entry);
            
            // New row
            $log = new AuditLogRecord();
            
            // Set user id
            $log->userId = craft()->userSession->getUser()->id;
            
            // Set element type
            $log->type = ElementType::Entry;
            
            // Set origin
            $log->origin = craft()->request->isCpRequest() ? craft()->config->get('cpTrigger') . '/' . craft()->request->path : craft()->request->path;
            
            // Set before
            $log->before = craft()->auditLog_entry->before;
            
            // Set after
            $log->after = craft()->auditLog_entry->after;
            
            // Set status
            $log->status = ($event->params['isNewEntry'] ? AuditLogModel::CREATED : AuditLogModel::MODIFIED);
            
            // Save row
            $log->save(false);
        
        });
        
        // Get values before deleting
        craft()->on('entries.onBeforeDeleteEntry', function(Event $event) {
        
            // Get deleted entry
            $entry = $event->params['entry'];

            // Get fields
            craft()->auditLog_entry->before = craft()->auditLog_entry->fields($entry);
            craft()->auditLog_entry->after  = craft()->auditLog_entry->fields($entry, true);
            
            // New row
            $log = new AuditLogRecord();
            
            // Set user id
            $log->userId = craft()->userSession->getUser()->id;
            
            // Set element type
            $log->type = ElementType::Entry;
            
            // Set origin
            $log->origin = craft()->request->isCpRequest() ? craft()->config->get('cpTrigger') . '/' . craft()->request->path : craft()->request->path;
            
            // Set before
            $log->before = craft()->auditLog_entry->before;
            
            // Set after
            $log->after = craft()->auditLog_entry->after;
            
            // Set status
            $log->status = AuditLogModel::DELETED;
            
            // Save row
            $log->save(false);
        
        });

        // Calculate the diffence
        $diff = array_diff_assoc($this->before, $this->after);

        // If there IS a difference
        if(count($diff) === 0) {

            // Fire an "onElementChanged" event
            Craft::import('plugins.auditLog.events.ElementChangedEvent');
            $event = new ElementChangedEvent($this, array(
                'elementType' => ElementType::Entry,
                'diff'        => $diff
            ));
            craft()->auditLog->onElementChanged($event);

        }

    }
    
    public function fields(EntryModel $entry, $empty = false)
    {
    
        // Always save id and title
        $fields = array(
            'id' => array(
                'label' => Craft::t('ID'),
                'value' => $entry->id
            ),
            'title' => array(
                'label' => Craft::t('Title'),
                'value' => $entry->title
            ),
            'section' => array(
                'label' => Craft::t('Section'),
                'value' => $entry->section->name
            )
        );
    
        // Get element type
        $elementType = craft()->elements->getElementType(ElementType::Entry);
        
        // Get nice attributes
        $attributes = $elementType->defineTableAttributes();
    
        // Get static "fields"
        foreach($entry->getAttributes() as $handle => $value) {
            
            // Only show nice attributes
            if(array_key_exists($handle, $attributes)) {
        
                $fields[$handle] = array(
                    'label' => $attributes[$handle],
                    'value' => StringHelper::arrayToString(is_array($value) ? array_filter($value) : $value, ', ')
                );
                
            }
        
        }
                                
        // Get fieldlayout
        $entrytype = $entry->getType();
        $tabs = craft()->fields->getLayoutById($entrytype->fieldLayoutId)->getTabs();
        foreach($tabs as $tab) {
            foreach($tab->getFields() as $field) {
        
                // Get field values
                $field = $field->getField();
                $handle = $field->handle;
                $label = $field->name;
                $value = $empty ? '' : craft()->auditLog->parseFieldData($handle, $entry->$handle);
                
                // Set on fields
                $fields[$handle] = array(
                    'label' => $label,
                    'value' => $value
                );
                
            }
        }
        
        // Return
        return $fields;
    
    }
    
}