<?php

namespace Craft;

/**
 * Audit Log Element Type.
 *
 * Makes the log behave as an Element Type
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, author
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 */
class AuditLogElementType extends BaseElementType
{
    /**
     * The name of the Element Type.
     *
     * @return string
     */
    public function getName()
    {
        return Craft::t('Audit Log');
    }

    /**
     * Return true so we have a status select menu.
     *
     * @return bool
     */
    public function hasStatuses()
    {
        return true;
    }

    /**
     * Define statuses.
     *
     * @return array
     */
    public function getStatuses()
    {
        return array(
            AuditLogModel::CREATED  => Craft::t('Created'),
            AuditLogModel::MODIFIED => Craft::t('Modified'),
            AuditLogModel::DELETED  => Craft::t('Deleted'),
        );
    }

    /**
     * Define table column names.
     *
     * @param string $source
     *
     * @return array
     */
    public function defineTableAttributes($source = null)
    {

        // Define default attributes
        $attributes = array(
            'type'        => Craft::t('Type'),
            'user'        => Craft::t('User'),
            'origin'      => Craft::t('Origin'),
            'dateUpdated' => Craft::t('Modified'),
        );

        // Allow plugins to modify the attributes
        craft()->plugins->call('modifyAuditLogTableAttributes', array(&$attributes, $source));

        // Set changes at last
        $attributes['changes'] = Craft::t('Changes');

        // Return the attributes
        return $attributes;
    }

    /**
     * Return table attribute html.
     *
     * @param BaseElementModel $element
     * @param string           $attribute
     *
     * @return string
     */
    public function getTableAttributeHtml(BaseElementModel $element, $attribute)
    {

        // First give plugins a chance to set this
        $pluginAttributeHtml = craft()->plugins->callFirst('getAuditLogTableAttributeHtml', array($element, $attribute), true);

        // Check if that had a valid result
        if ($pluginAttributeHtml) {

            // Return it
            return $pluginAttributeHtml;
        }

        // Modify custom attributes
        switch ($attribute) {

            // Format dates
            case 'dateCreated':
            case 'dateUpdated':

                return craft()->dateFormatter->formatDateTime($element->$attribute);
                break;

            // Return clickable user link
            case 'user':

                $user = $element->getUser();

                return $user ? '<a href="'.$user->getCpEditUrl().'">'.$user.'</a>' : Craft::t('Guest');
                break;

            // Return clickable event origin
            case 'origin':

                return '<a href="'.preg_replace('/'.craft()->config->get('cpTrigger').'\//', '', UrlHelper::getUrl($element->origin), 1).'">'.$element->origin.'</a>';
                break;

            // Return view changes button
            case 'changes':

                return '<a class="btn" href="'.UrlHelper::getCpUrl('auditlog/'.$element->id).'">'.Craft::t('View').'</a>';
                break;

            // Default behavior
            default:

                return $element->$attribute;
                break;

        }
    }

    /**
     * Define criteria.
     *
     * @return array
     */
    public function defineCriteriaAttributes()
    {
        return array(
            'type'        => AttributeType::String,
            'userId'      => AttributeType::Number,
            'origin'      => AttributeType::String,
            'modified'    => AttributeType::DateTime,
            'status'      => AttributeType::String,
            'before'      => AttributeType::DateTime,
            'after'       => AttributeType::DateTime,
            'order'       => array(AttributeType::String, 'default' => 'auditlog.id desc'),
        );
    }

    /**
     * Cancel the elements query.
     *
     * @param DbCommand            $query
     * @param ElementCriteriaModel $criteria
     *
     * @return bool
     */
    public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
    {
        // Default query
        $query
            ->select('auditlog.id, auditlog.type, auditlog.userId, auditlog.origin, auditlog.before, auditlog.after, auditlog.status, auditlog.dateCreated, auditlog.dateUpdated')
            ->from('auditlog auditlog');

        // Reset default element type query parts
        $query->setJoin('');
        $query->setWhere('1=1');
        $query->setGroup('');
        array_shift($query->params);

        // Check for date after
        if (!empty($criteria->after)) {
            $query->andWhere(DbHelper::parseDateParam('auditlog.dateUpdated', '>= '.DateTimeHelper::formatTimeForDb($criteria->after), $query->params));
        }

        // Check for date before
        if (!empty($criteria->before)) {
            $query->andWhere(DbHelper::parseDateParam('auditlog.dateUpdated', '<= '.DateTimeHelper::formatTimeForDb($criteria->before), $query->params));
        }

        // Check for type
        if (!empty($criteria->type)) {
            $query->andWhere(DbHelper::parseParam('auditlog.type', $criteria->type, $query->params));
        }

        // Check for status
        if (!empty($criteria->status)) {
            $query->andWhere(DbHelper::parseParam('auditlog.status', $criteria->status, $query->params));
        }

        // Search
        if (!empty($criteria->search)) {

            // Always perform a LIKE search
            $criteria->search = '*'.$criteria->search.'*';

            // Build conditions
            $conditions = array(
                'or',
                DbHelper::parseParam('auditlog.origin', $criteria->search, $query->params),
                DbHelper::parseParam('auditlog.before', $criteria->search, $query->params),
                DbHelper::parseParam('auditlog.after', $criteria->search, $query->params),
            );

            // Add to query
            $query->andWhere($conditions, $query->params);

            // Don't perform search logics after this
            $criteria->search = null;
        }
    }

    /**
     * Create element from row.
     *
     * @param array $row
     *
     * @return AuditLogModel
     */
    public function populateElementModel($row)
    {
        return AuditLogModel::populateModel($row);
    }

    /**
     * Define the sources.
     *
     * @param string $context
     */
    public function getSources($context = null)
    {

        // Get plugin settings
        $settings = craft()->plugins->getPlugin('AuditLog')->getSettings();

        // Set default sources
        $sources = array(
            '*' => array(
                'label'      => Craft::t('All logs'),
            ),
            array('heading' => Craft::t('Elements')),
        );

        // Show sources for entries when enabled
        if (in_array(ElementType::Entry, $settings->enabled)) {
            $sources['entries'] = array(
                'label'      => Craft::t('Entries'),
                'criteria'   => array(
                    'type'   => ElementType::Entry,
                ),
            );
        }

        // Show sources for categories when enabled
        if (in_array(ElementType::Category, $settings->enabled)) {
            $sources['categories'] = array(
                'label'      => Craft::t('Categories'),
                'criteria'   => array(
                    'type'   => ElementType::Category,
                ),
            );
        }

        // Show sources for users when enabled
        if (in_array(ElementType::User, $settings->enabled)) {
            $sources['users'] = array(
                'label'      => Craft::t('Users'),
                'criteria'   => array(
                    'type'   => ElementType::User,
                ),
            );
        }

        // Get sources by hook
        $plugins = craft()->plugins->call('registerAuditLogSources');
        if (count($plugins)) {
            $sources[] = array('heading' => Craft::t('Custom'));
            foreach ($plugins as $plugin) {

                // Add as own source
                $sources = array_merge($sources, $plugin);

                // Add to "All elemenents"
                foreach ($plugin as $key => $values) {
                    $sources['*']['criteria']['source'][] = $values['criteria']['source'];
                }
            }
        }

        // Return sources
        return $sources;
    }

    /**
     * Set sortable attributes.
     *
     * @return array
     */
    public function defineSortableAttributes()
    {

        // Set modified first
        $attributes['dateUpdated'] = Craft::t('Modified');

        // Get table attributes
        $attributes = array_merge($attributes, $this->defineTableAttributes());

        // Unset unsortable attributes
        unset($attributes['user'], $attributes['changes']);

        // Allow plugins to modify the attributes
        craft()->plugins->call('modifyAuditLogSortableAttributes', array(&$attributes));

        // Return attributes
        return $attributes;
    }
}
