<?php
namespace Craft;

class AuditLog_CategoryService extends BaseApplicationComponent 
{

    public $before = array();
    public $after  = array();

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
                craft()->auditLog_category->before = craft()->auditLog_category->fields($category);
                
            } else {
            
                // Get fields
                craft()->auditLog_category->before = craft()->auditLog_category->fields($event->params['category'], true);
            
            }
                    
        });
    
        // Get values after saving
        craft()->on('categories.onSaveCategory', function(Event $event) {
        
            // Get saved category
            $category = $event->params['category'];

            // Get fields
            craft()->auditLog_category->after = craft()->auditLog_category->fields($category);
            
            // New row
            $log = new AuditLogRecord();
            
            // Set user id
            $log->userId = craft()->userSession->getUser()->id;
            
            // Set element type
            $log->type = ElementType::Category;
            
            // Set origin
            $log->origin = craft()->request->isCpRequest() ? craft()->config->get('cpTrigger') . '/' . craft()->request->path : craft()->request->path;
            
            // Set before
            $log->before = craft()->auditLog_category->before;
            
            // Set after
            $log->after = craft()->auditLog_category->after;
            
            // Set status
            $log->status = ($event->params['isNewCategory'] ? AuditLogModel::CREATED : AuditLogModel::MODIFIED);
            
            // Save row
            $log->save(false);
        
        });
        
        // Get values before deleting
        craft()->on('categories.onBeforeDeleteCategory', function(Event $event) {
        
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
            $log->origin = craft()->request->isCpRequest() ? craft()->config->get('cpTrigger') . '/' . craft()->request->path : craft()->request->path;
            
            // Set before
            $log->before = craft()->auditLog_category->before;
            
            // Set after
            $log->after = craft()->auditLog_category->after;
            
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
                'elementType' => ElementType::Category,
                'diff'        => $diff
            ));
            craft()->auditLog->onElementChanged($event);

        }
        
    }
    
    public function fields(CategoryModel $category, $empty = false)
    {
    
        // Always save id
        $fields = array(
            'id' => array(
                'label' => Craft::t('ID'),
                'value' => $category->id
            ),
            'title' => array(
                'label' => Craft::t('Title'),
                'value' => $category->title
            ),
            'group' => array(
                'label' => Craft::t('Group'),
                'value' => $category->group->name
            )
        );
    
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
                    'value' => StringHelper::arrayToString(is_array($value) ? array_filter($value) : $value, ', ')
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