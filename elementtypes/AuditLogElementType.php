<?php
namespace Craft;

class AuditLogElementType extends BaseElementType
{

    public function getName()
    {
        return Craft::t('Audit Log');
    }
    
    // Return true so we have a status select menu
    public function hasStatuses()
    {
        return true;
    }

    // Define statuses
    public function getStatuses()
    {
        return array(
            AuditLogModel::CREATED  => Craft::t('Created'),
            AuditLogModel::MODIFIED => Craft::t('Modified'),
            AuditLogModel::DELETED  => Craft::t('Deleted')
        );
    }

    // Define table column names
    public function defineTableAttributes($source = null)
    {
        return array(
            'type'        => Craft::t('Type'),
            'user'        => Craft::t('User'),
            'origin'      => Craft::t('Origin'),
            'dateUpdated' => Craft::t('Modified'),
            'changes'     => Craft::t('Changes')
        );
    }
    
    public function getTableAttributeHtml(BaseElementModel $element, $attribute)
    {
        switch ($attribute)
        {
            case 'dateCreated':
            case 'dateUpdated':
            {
                return craft()->dateFormatter->formatDateTime($element->$attribute);
            }
            case 'user':
            {
                $user = craft()->users->getUserById($element->$attribute->id);
                return '<a href="' . $user->getCpEditUrl() . '">' . $user . '</a>';
            }
            case 'changes':
            {
                return '<a class="btn" href="' . UrlHelper::getCpUrl('auditlog/' . $element->id) . '">' . Craft::t('View') . '</a>';
            }
            default:
            {
                return $element->$attribute;
            }
        }
    }
    
    // Define criteria
    public function defineCriteriaAttributes()
    {
        return array(
            'type'        => AttributeType::String,
            'user'        => AttributeType::Number,
            'origin'      => AttributeType::String,
            'modified'    => AttributeType::DateTime,
            'status'      => AttributeType::String
        );
    }
    
    // Cancel the elements query
    public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
    {
        return false;
    }
    
    // Create element from row
    public function populateElementModel($row)
    {
        return AuditLogModel::populateModel($row);
    }
    
    // Define the sources
    public function getSources($context = null)
    {
        return array(
            '*' => array(
                'label'      => Craft::t('All logs')
            ),
            array('heading' => Craft::t('Elements')),
            'categories' => array(
                'label'      => Craft::t('Categories'),
                'criteria'   => array(
                    'type'   => ElementType::Category
                )
            ),
            'entries' => array(
                'label'      => Craft::t('Entries'),
                'criteria'   => array(
                    'type'   => ElementType::Entry
                )
            ),
            'users' => array(
                'label'      => Craft::t('Users'),
                'criteria'   => array(
                    'type'   => ElementType::User
                )
            )
        );
    }
    
    // Return the html
    public function getIndexHtml($criteria, $disabledElementIds, $viewState, $sourceKey, $context)
    {
        $variables = array(
            'viewMode'            => $viewState['mode'],
            'context'             => $context,
            'elementType'         => new ElementTypeVariable($this),
            'disabledElementIds'  => $disabledElementIds,
            'attributes'          => $this->defineTableAttributes($sourceKey),
            'elements'            => craft()->auditLog->log($criteria)
        );
       
        $template = '_elements/'.$viewState['mode'].'view/'.(!$criteria->offset ? 'container' : 'elements');
        return craft()->templates->render($template, $variables);
    }

}