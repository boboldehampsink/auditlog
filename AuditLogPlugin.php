<?php

namespace Craft;

/**
 * Audit Log Plugin.
 *
 * Allows you to log adding/updating/deleting of categories/entries/users.
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink/auditlog
 */
class AuditLogPlugin extends BasePlugin
{
    /**
     * Return the plugin name.
     *
     * @return string
     */
    public function getName()
    {
        return Craft::t('Audit Log');
    }

    /**
     * Return the plugin version.
     *
     * @return string
     */
    public function getVersion()
    {
        return '0.7.1';
    }

    /**
     * Return the developer name.
     *
     * @return string
     */
    public function getDeveloper()
    {
        return 'Bob Olde Hampsink';
    }

    /**
     * Return the developer URL.
     *
     * @return string
     */
    public function getDeveloperUrl()
    {
        return 'https://github.com/boboldehampsink';
    }

    /**
     * Tell Craft we have a Control Panel section.
     *
     * @return bool
     */
    public function hasCpSection()
    {
        return true;
    }

    /**
     * Register routes for Control Panel.
     *
     * @return array
     */
    public function registerCpRoutes()
    {
        return array(
            'auditlog/(?P<logId>\d+)' => 'auditlog/_log',
        );
    }

    /**
     * Let the user decide what to log.
     *
     * @return array
     */
    protected function defineSettings()
    {
        return array(
            'enabled' => array(AttributeType::Mixed, 'default' => array(
                ElementType::Entry,
                ElementType::Category,
                ElementType::User,
            )),
        );
    }

    /**
     * Render the settings template.
     *
     * @return string
     */
    public function getSettingsHtml()
    {
        return craft()->templates->render('auditlog/_settings', array(
            'settings' => $this->getSettings(),
        ));
    }

    /**
     * Log all specific element types that have the right events.
     */
    public function init()
    {
        // Get settings
        $settings = $this->getSettings();

        // Log entries, when enabled
        if (in_array(ElementType::Entry, $settings->enabled)) {
            craft()->auditLog_entry->log();
        }

        // Log categories, when enabled
        if (in_array(ElementType::Category, $settings->enabled)) {
            craft()->auditLog_category->log();
        }

        // Log users, when enabled
        if (in_array(ElementType::User, $settings->enabled)) {
            craft()->auditLog_user->log();
        }
    }
}
