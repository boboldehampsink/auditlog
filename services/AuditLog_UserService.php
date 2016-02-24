<?php

namespace Craft;

/**
 * Audit Log User service.
 *
 * Contains logics for logging users
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@nerds.company>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   MIT
 *
 * @link      http://github.com/boboldehampsink
 */
class AuditLog_UserService extends BaseApplicationComponent
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
     * Initialize the user saving/deleting events.
     *
     * @codeCoverageIgnore
     */
    public function log()
    {
        // Get values before saving
        craft()->on('users.onBeforeSaveUser', array($this, 'onBeforeSaveUser'));

        // Get values after saving
        craft()->on('users.onSaveUser', array($this, 'onSaveUser'));

        // Get values before deleting
        craft()->on('users.onBeforeDeleteUser', array($this, 'onBeforeDeleteUser'));
    }

    /**
     * Handle the onBeforeSaveUser event.
     *
     * @param Event $event
     */
    public function onBeforeSaveUser(Event $event)
    {
        // Get user id to save
        $id = $event->params['user']->id;

        if (!$event->params['isNewUser']) {

            // Get old user from db
            $user = UserModel::populateModel(UserRecord::model()->findById($id));

            // Get fields
            $this->before = $this->fields($user);
        } else {

            // Get fields
            $this->before = $this->fields($event->params['user'], true);
        }
    }

    /**
     * Handle the onSaveUser event.
     *
     * @param Event $event
     */
    public function onSaveUser(Event $event)
    {
        // Get saved user
        $user = $event->params['user'];

        // Get fields
        $this->after = $this->fields($user);

        // New row
        $log = new AuditLogRecord();

        // Set user id
        $log->userId = craft()->userSession->getUser() ? craft()->userSession->getUser()->id : $user->id;

        // Set element type
        $log->type = ElementType::User;

        // Set origin
        $log->origin = craft()->request->isCpRequest() ? craft()->config->get('cpTrigger').'/'.craft()->request->path : craft()->request->path;

        // Set before
        $log->before = $this->before;

        // Set after
        $log->after = $this->after;

        // Set status
        $log->status = ($event->params['isNewUser'] ? AuditLogModel::CREATED : AuditLogModel::MODIFIED);

        // Save row
        $log->save(false);

        // Callback
        craft()->auditLog->elementHasChanged(ElementType::User, $user->id, $this->before, $this->after);
    }

    /**
     * Handle the onBeforeDeleteUser event.
     *
     * @param Event $event
     */
    public function onBeforeDeleteUser(Event $event)
    {
        // Get deleted user
        $user = $event->params['user'];

        // Get fields
        $this->before = $this->fields($user);
        $this->after = $this->fields($user, true);

        // New row
        $log = new AuditLogRecord();

        // Set user id
        $log->userId = craft()->userSession->getUser()->id;

        // Set element type
        $log->type = ElementType::User;

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
        craft()->auditLog->elementHasChanged(ElementType::User, $user->id, $this->before, $this->after);
    }

    /**
     * Parse user fields.
     *
     * @param UserModel $user
     * @param bool      $empty
     *
     * @return array
     */
    public function fields(UserModel $user, $empty = false)
    {
        // Check if we are saving new groups
        $groupIds = craft()->request->getPost('groups', false);

        // If this is before saving, or no groups have changed
        if (!count($this->before) || !$groupIds) {

            // Get user's groups
            $groups = craft()->userGroups->getGroupsByUserId($user->id);
        } else {

            // This is after saving
            // Get posted groups
            $groups = array();
            foreach ($groupIds as $id) {
                $groups[] = craft()->userGroups->getGroupById($id);
            }
        }

        // Always save id
        $fields = array(
            'id' => array(
                'label' => Craft::t('ID'),
                'value' => $user->id,
            ),
            'groups' => array(
                'label' => Craft::t('Groups'),
                'value' => implode(', ', $groups),
            ),
        );

        // Get element type
        $elementType = craft()->elements->getElementType(ElementType::User);

        // Get nice attributes
        $availableAttributes = $elementType->defineAvailableTableAttributes();

        // Make 'em fit
        $attributes = array();
        foreach ($availableAttributes as $key => $result) {
            $attributes[$key] = $result['label'];
        }

        // Get static "fields"
        foreach ($user->getAttributes() as $handle => $value) {

            // Only show nice attributes
            if (array_key_exists($handle, $attributes)) {
                $fields[$handle] = array(
                    'label' => $attributes[$handle],
                    'value' => StringHelper::arrayToString(is_array($value) ? array_filter(ArrayHelper::flattenArray($value), 'strlen') : $value, ', '),
                );
            }
        }

        // Get fieldlayout
        foreach (craft()->fields->getLayoutByType(ElementType::User)->getFields() as $field) {

            // Get field values
            $field = $field->getField();
            $handle = $field->handle;
            $label = $field->name;
            $value = $empty ? '' : craft()->auditLog->parseFieldData($handle, $user->$handle);

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
