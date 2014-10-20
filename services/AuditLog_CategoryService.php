<?php
namespace Craft;

class AuditLog_CategoryService extends BaseApplicationComponent 
{

    private $_before = array();

    public function log()
    {
    
        // Get values before saving
        craft()->on('categories.onBeforeSaveCategory', function(Event $event) {
        
            // Get category id to save
            $id = $event->params['category']->id;
            
            if(!$event->params['isNewCategory']) {
            
                // Get old category from db
                $category = CategoryModel::populateModel(CategoryRecord::model()->findById($id));
                
                // Get fields
                $this->_before = $this->fields($category);
                
            } else {
            
                // Get fields
                $this->_before = $this->fields($event->params['category'], true);
            
            }
                    
        });
    
        // Get values after saving
        craft()->on('categories.onSaveCategory', function(Event $event) {
        
            // Get saved category
            $category = $event->params['category'];
            
            // New row
            $log = new AuditLogRecord();
            
            // Set user id
            $log->userId = craft()->userSession->getUser()->id;
            
            // Set element type
            $log->type = ElementType::Category;
            
            // Set origin
            $log->origin = craft()->request->isCpRequest() ? craft()->config->get('cpTrigger') . '/' . craft()->request->path : craft()->request->path;
            
            // Set before
            $log->before = $this->_before;
            
            // Set after
            $log->after = $this->fields($category);
            
            // Set status
            $log->status = ($event->params['isNewCategory'] ? AuditLogModel::CREATED : AuditLogModel::MODIFIED);
            
            // Save row
            $log->save(false);
        
        });
        
        // Get values before deleting
        craft()->on('categories.onBeforeDeleteCategory', function(Event $event) {
        
            // Get deleted category
            $category = $event->params['category'];
            
            // New row
            $log = new AuditLogRecord();
            
            // Set user id
            $log->userId = craft()->userSession->getUser()->id;
            
            // Set element type
            $log->type = ElementType::Category;
            
            // Set origin
            $log->origin = craft()->request->isCpRequest() ? craft()->config->get('cpTrigger') . '/' . craft()->request->path : craft()->request->path;
            
            // Set before
            $log->before = $this->fields($category);
            
            // Set after
            $log->after = $this->fields($category, true);
            
            // Set status
            $log->status = AuditLogModel::DELETED;
            
            // Save row
            $log->save(false);
        
        });
        
    }
    
    public function fields(CategoryModel $category, $empty = false)
    {
    
        // Get element type
        $elementType = craft()->elements->getElementType(ElementType::Category);
        
        // Get nice attributes
        $attributes = $elementType->defineTableAttributes();
    
        // Get static "fields"
        foreach($category->getAttributes() as $handle => $value) {
            
            // Only show nice attributes
            if(array_key_exists($handle, $attributes)) {
        
                $fields[$handle] = array(
                    'label' => $attributes[$handle],
                    'value' => StringHelper::arrayToString($value)
                );
                
            }
        
        }
        
        // Get fieldlayout
        foreach(craft()->fields->getLayoutByType(ElementType::Category)->getFields() as $field) {
        
            // Get field values
            $field = $field->getField();
            $handle = $field->handle;
            $label = $field->name;
            $value = $empty ? '' : craft()->auditLog->parseFieldData($handle, $category->$handle);
            
            // Set on fields
            $fields[$handle] = array(
                'label' => $label,
                'value' => $value
            );
            
        }
        
        // Return
        return $fields;
    
    }
    
}