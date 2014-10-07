<?php
namespace Craft;

class AuditLog_UserService extends BaseApplicationComponent 
{

    private $_before = array();

    public function log()
    {
    
        // Get values before saving
        craft()->on('users.onBeforeSaveUser', function(Event $event) {
        
            // Get user id to save
            $id = $event->params['user']->id;
            
            if(!$event->params['isNewUser']) {
            
                // Get old user from db
                $user = UserModel::populateModel(UserRecord::model()->findById($id));
                
                // Get fields
                $this->_before = $this->fields($user);
                
            } else {
            
                // Get fields
                $this->_before = $this->fields($event->params['user'], true);
            
            }
                    
        });
    
        // Get values after saving
        craft()->on('users.onSaveUser', function(Event $event) {
        
            // Get saved user
            $user = $event->params['user'];
            
            // New row
            $log = new AuditLogRecord();
            
            // Set user id
            $log->userId = craft()->userSession->getUser()->id;
            
            // Set element type
            $log->type = ElementType::User;
            
            // Set origin
            $log->origin = craft()->request->isCpRequest() ? craft()->config->get('cpTrigger') . '/' . craft()->request->path : craft()->request->path;
            
            // Set before
            $log->before = $this->_before;
            
            // Set after
            $log->after = $this->fields($user);
            
            // Set status
            $log->status = ($event->params['isNewUser'] ? 'live' : 'pending');
            
            // Save row
            $log->save(false);
        
        });
        
        // Get values before deleting
        craft()->on('users.onBeforeDeleteUser', function(Event $event) {
        
            // Get deleted user
            $user = $event->params['user'];
            
            // New row
            $log = new AuditLogRecord();
            
            // Set user id
            $log->userId = craft()->userSession->getUser()->id;
            
            // Set element type
            $log->type = ElementType::User;
            
            // Set origin
            $log->origin = craft()->request->isCpRequest() ? craft()->config->get('cpTrigger') . '/' . craft()->request->path : craft()->request->path;
            
            // Set before
            $log->before = $this->fields($user);
            
            // Set after
            $log->after = $this->fields($user, true);
            
            // Set status
            $log->status = 'expired';
            
            // Save row
            $log->save(false);
        
        });
        
    }
    
    public function fields(UserModel $user, $empty = false)
    {
    
        // Get element type
        $elementType = craft()->elements->getElementType(ElementType::User);
        
        // Get nice attributes
        $attributes = $elementType->defineTableAttributes();
    
        // Get static "fields"
        foreach($user->getAttributes() as $handle => $value) {
            
            // Only show nice attributes
            if(array_key_exists($handle, $attributes)) {
        
                $fields[$handle] = array(
                    'label' => $attributes[$handle],
                    'value' => $value
                );
                
            }
        
        }
        
        // Get fieldlayout
        foreach(craft()->fields->getLayoutByType(ElementType::User)->getFields() as $field) {
        
            // Get field values
            $field = $field->getField();
            $handle = $field->handle;
            $label = $field->name;
            $value = $empty ? '' : ($user->$handle instanceof ElementCriteriaModel ? implode(', ', $user->$handle->find()) : $user->$handle);
            
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