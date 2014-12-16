<?php
namespace Craft;

class AuditLog_UserService extends BaseApplicationComponent 
{

    public $before = array();
    public $after  = array();

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
                craft()->auditLog_user->before = craft()->auditLog_user->fields($user);
                
            } else {
            
                // Get fields
                craft()->auditLog_user->before = craft()->auditLog_user->fields($event->params['user'], true);
            
            }
                    
        });
    
        // Get values after saving
        craft()->on('users.onSaveUser', function(Event $event) {
        
            // Get saved user
            $user = $event->params['user'];

            // Get fields
            craft()->auditLog_user->after = craft()->auditLog_user->fields($user);
            
            // New row
            $log = new AuditLogRecord();
            
            // Set user id
            $log->userId = craft()->userSession->getUser() ? craft()->userSession->getUser()->id : $user->id;
            
            // Set element type
            $log->type = ElementType::User;
            
            // Set origin
            $log->origin = craft()->request->isCpRequest() ? craft()->config->get('cpTrigger') . '/' . craft()->request->path : craft()->request->path;
            
            // Set before
            $log->before = craft()->auditLog_user->before;
            
            // Set after
            $log->after = craft()->auditLog_user->after;
            
            // Set status
            $log->status = ($event->params['isNewUser'] ? AuditLogModel::CREATED : AuditLogModel::MODIFIED);
            
            // Save row
            $log->save(false);
        
        });
        
        // Get values before deleting
        craft()->on('users.onBeforeDeleteUser', function(Event $event) {
        
            // Get deleted user
            $user = $event->params['user'];

            // Get fields
            craft()->auditLog_user->before = craft()->auditLog_user->fields($user);
            craft()->auditLog_user->after  = craft()->auditLog_user->fields($user, true);
            
            // New row
            $log = new AuditLogRecord();
            
            // Set user id
            $log->userId = craft()->userSession->getUser()->id;
            
            // Set element type
            $log->type = ElementType::User;
            
            // Set origin
            $log->origin = craft()->request->isCpRequest() ? craft()->config->get('cpTrigger') . '/' . craft()->request->path : craft()->request->path;
            
            // Set before
            $log->before = craft()->auditLog_user->before;
            
            // Set after
            $log->after = craft()->auditLog_user->after;
            
            // Set status
            $log->status = AuditLogModel::DELETED;
            
            // Save row
            $log->save(false);
        
        });

        // Fire off events in case things have changed
        if(count(array_diff($this->before, $this->after)) === 0) {

            // Fire an "onElementChanged" event
            Craft::import('plugins.auditLog.events.ElementChangedEvent');
            $event = new ElementChangedEvent($this, array('elementType' => ElementType::User));
            craft()->auditLog->onElementChanged($event);

        }
        
    }
    
    public function fields(UserModel $user, $empty = false)
    {
    
        // Check if we are saving new groups
        $groupIds = craft()->request->getPost('groups', false);
        
        // If this is before saving, or no groups have changed
        if(!count($this->before) || !$groupIds) {
        
            // Get user's groups
            $groups = craft()->userGroups->getGroupsByUserId($user->id);
            
        } else {
        
            // This is after saving
            // Get posted groups
            $groups = array();
            foreach($groupIds as $id) {
                $groups[] = craft()->userGroups->getGroupById($id);
            }
            
        }
    
        // Always save id
        $fields = array(
            'id' => array(
                'label' => Craft::t('ID'),
                'value' => $user->id
            ),
            'groups' => array(
                'label' => Craft::t('Groups'),
                'value' => implode(', ', $groups)
            )
        );
    
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
                    'value' => StringHelper::arrayToString(is_array($value) ? array_filter($value) : $value, ', ')
                );
                
            }
        
        }
        
        // Get fieldlayout
        foreach(craft()->fields->getLayoutByType(ElementType::User)->getFields() as $field) {
        
            // Get field values
            $field = $field->getField();
            $handle = $field->handle;
            $label = $field->name;
            $value = $empty ? '' : craft()->auditLog->parseFieldData($handle, $user->$handle);
            
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