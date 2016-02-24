<?php

namespace Craft;

/**
 * Audit Log Element Type.
 *
 * Makes the log behave as an Element Type
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@nerds.company>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   MIT
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
            AuditLogModel::CREATED => Craft::t('Created'),
            AuditLogModel::MODIFIED => Craft::t('Modified'),
            AuditLogModel::DELETED => Craft::t('Deleted'),
        );
    }

    /**
     * Define available table column names.
     *
     * @return array
     */
    public function defineAvailableTableAttributes()
    {
        // Define default attributes
        $attributes = array(
            'type' => array('label' => Craft::t('Type')),
            'user' => array('label' => Craft::t('User')),
            'origin' => array('label' => Craft::t('Origin')),
            'dateUpdated' => array('label' => Craft::t('Modified')),
        );

        // Allow plugins to modify the attributes
        $pluginAttributes = craft()->plugins->call('defineAdditionalAuditLogTableAttributes', array(), true);
        foreach ($pluginAttributes as $thisPluginAttributes) {
            $attributes = array_merge($attributes, $thisPluginAttributes);
        }

        // Set changes at last
        $attributes['changes'] = array('label' => Craft::t('Changes'));

        // Return the attributes
        return $attributes;
    }

    /**
     * Returns the default table attributes.
     *
     * @param string $source
     *
     * @return string[]
     */
    public function getDefaultTableAttributes($source = null)
    {
        return array('type', 'user', 'origin', 'dateUpdated', 'changes');
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
            return $pluginAttributeHtml;
        }

        // Modify custom attributes
        switch ($attribute) {

            // Format dates
            case 'dateCreated':
            case 'dateUpdated':
                return craft()->dateFormatter->formatDateTime($element->$attribute);

            // Return clickable user link
            case 'user':
                $user = $element->getUser();

                return $user ? '<a href="'.$user->getCpEditUrl().'">'.$user.'</a>' : Craft::t('Guest');

            // Return clickable event origin
            case 'origin':
                return '<a href="'.preg_replace('/'.craft()->config->get('cpTrigger').'\//', '', UrlHelper::getUrl($element->origin), 1).'">'.$element->origin.'</a>';

            // Return view changes button
            case 'changes':
                return '<a class="btn" href="'.UrlHelper::getCpUrl('auditlog/'.$element->id).'">'.Craft::t('View').'</a>';

            // Default behavior
            default:
                return $element->$attribute;
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
            'type' => AttributeType::String,
            'userId' => AttributeType::Number,
            'origin' => AttributeType::String,
            'modified' => AttributeType::DateTime,
            'before' => AttributeType::String,
            'after' => AttributeType::String,
            'status' => AttributeType::String,
            'from' => AttributeType::DateTime,
            'to' => AttributeType::DateTime,
            'order' => array(AttributeType::String, 'default' => 'auditlog.id desc'),
        );
    }

    /**
     * Modify the elements query.
     *
     * @param DbCommand            $query
     * @param ElementCriteriaModel $criteria
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
        unset($query->params[':locale']);
        unset($query->params[':elementsid1']);

        // Check for specific id
        if (!empty($criteria->id)) {
            $query->andWhere(DbHelper::parseParam('auditlog.id', $criteria->id, $query->params));
        }

        // Check type
        if (!empty($criteria->type)) {
            $query->andWhere(DbHelper::parseParam('auditlog.type', $criteria->type, $query->params));
        }

        // Check user id
        if (!empty($criteria->userId)) {
            $query->andWhere(DbHelper::parseParam('auditlog.userId', $criteria->userId, $query->params));
        }

        // Check origin
        if (!empty($criteria->origin)) {
            $query->andWhere(DbHelper::parseParam('auditlog.origin', $criteria->origin, $query->params));
        }

        // Check before
        if (!empty($criteria->before)) {
            $query->andWhere(DbHelper::parseParam('auditlog.before', $criteria->before, $query->params));
        }

        // Check after
        if (!empty($criteria->after)) {
            $query->andWhere(DbHelper::parseParam('auditlog.after', $criteria->after, $query->params));
        }

        // Check for status
        if (!empty($criteria->status)) {
            $query->andWhere(DbHelper::parseParam('auditlog.status', $criteria->status, $query->params));
        }

        // Dates
        $this->applyDateCriteria($criteria, $query);

        // Search
        $this->applySearchCriteria($criteria, $query);
    }

    /**
     * Apply date criteria.
     *
     * @param ElementCriteriaModel $criteria
     * @param DbCommand            $query
     */
    private function applyDateCriteria(ElementCriteriaModel $criteria, DbCommand $query)
    {
        // Check for date modified
        if (!empty($criteria->modified)) {
            $query->andWhere(DbHelper::parseDateParam('auditlog.dateUpdated', $criteria->modified, $query->params));
        }

        // Check for date from
        if (!empty($criteria->from)) {
            $query->andWhere(DbHelper::parseDateParam('auditlog.dateUpdated', '>= '.DateTimeHelper::formatTimeForDb($criteria->from), $query->params));
        }

        // Check for date to
        if (!empty($criteria->to)) {
            $criteria->to->add(new DateInterval('PT23H59M59S'));
            $query->andWhere(DbHelper::parseDateParam('auditlog.dateUpdated', '<= '.DateTimeHelper::formatTimeForDb($criteria->to), $query->params));
        }
    }

    /**
     * Apply search criteria.
     *
     * @param ElementCriteriaModel $criteria
     * @param DbCommand            $query
     */
    private function applySearchCriteria(ElementCriteriaModel $criteria, DbCommand $query)
    {
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
                'label' => Craft::t('All logs'),
            ),
            array('heading' => Craft::t('Elements')),
        );

        // Show sources for entries when enabled
        if (in_array(ElementType::Entry, $settings->enabled)) {
            $sources['entries'] = array(
                'label' => Craft::t('Entries'),
                'criteria' => array(
                    'type' => ElementType::Entry,
                ),
            );
        }

        // Show sources for categories when enabled
        if (in_array(ElementType::Category, $settings->enabled)) {
            $sources['categories'] = array(
                'label' => Craft::t('Categories'),
                'criteria' => array(
                    'type' => ElementType::Category,
                ),
            );
        }

        // Show sources for users when enabled
        if (in_array(ElementType::User, $settings->enabled)) {
            $sources['users'] = array(
                'label' => Craft::t('Users'),
                'criteria' => array(
                    'type' => ElementType::User,
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
        $attributes = array('dateUpdated' => Craft::t('Modified'));

        // Get table attributes
        $attributes = array_merge($attributes, parent::defineSortableAttributes());

        // Unset unsortable attributes
        unset($attributes['user'], $attributes['changes']);

        // Allow plugins to modify the attributes
        craft()->plugins->call('modifyAuditLogSortableAttributes', array(&$attributes));

        // Return attributes
        return $attributes;
    }
}
