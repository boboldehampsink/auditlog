<?php
namespace Craft;

class AuditLogController extends BaseController
{

    public function actionClear()
    {
    
        // Delete all
        AuditLogRecord::model()->deleteAll();
                
        // Redirect
        $this->redirect('auditlog');
    
    }

}