<?php
namespace Craft;

class AuditLog_EntryService extends BaseApplicationComponent 
{

    private $_before = array();

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
                $this->_before = $this->fields($entry);
                
            } else {
            
                // Get fields
                $this->_before = $this->fields($event->params['entry'], true);
            
            }
                    
        });
    
        // Get values after saving
        craft()->on('entries.onSaveEntry', function(Event $event) {
        
            // Get saved entry
            $entry = $event->params['entry'];
            
            // New row
            $log = new AuditLogRecord();
            
            // Set user id
            $log->userId = craft()->userSession->getUser()->id;
            
            // Set element type
            $log->type = ElementType::Entry;
            
            // Set origin
            $log->origin = craft()->request->isCpRequest() ? craft()->config->get('cpTrigger') . '/' . craft()->request->path : craft()->request->path;
            
            // Set before
            $log->before = $this->_before;
            
            // Set after
            $log->after = $this->fields($entry);
            
            // Set status
            $log->status = ($event->params['isNewEntry'] ? AuditLogModel::CREATED : AuditLogModel::MODIFIED);
            
            // Save row
            $log->save(false);
        
        });
        
        // Get values before deleting
        craft()->on('entries.onBeforeDeleteEntry', function(Event $event) {
        
            // Get deleted entry
            $entry = $event->params['entry'];
            
            // New row
            $log = new AuditLogRecord();
            
            // Set user id
            $log->userId = craft()->userSession->getUser()->id;
            
            // Set element type
            $log->type = ElementType::Entry;
            
            // Set origin
            $log->origin = craft()->request->isCpRequest() ? craft()->config->get('cpTrigger') . '/' . craft()->request->path : craft()->request->path;
            
            // Set before
            $log->before = $this->fields($entry);
            
            // Set after
            $log->after = $this->fields($entry, true);
            
            // Set status
            $log->status = AuditLogModel::DELETED;
            
            // Save row
            $log->save(false);
        
        });
        
    }
    
    public function fields(EntryModel $entry, $empty = false)
    {
    
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
                    'value' => StringHelper::arrayToString($value)
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