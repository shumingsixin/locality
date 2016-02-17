<?php

class HomeController extends Controller {

    public $layout = '//layouts/column1';

    /**
     * This is the default 'index' action that is invoked
     * when an action is not explicitly requested by users.
     */
    public function actionIndex() {
        echo 'File!';        
    }

    /**
     * This is the action to handle external exceptions.
     */
    public function actionError() {     
        $error=Yii::app()->errorHandler->error;
        $this->renderJsonOutput($error);
       /*
        if ($error = Yii::app()->errorHandler->error) {
            if (Yii::app()->request->isAjaxRequest) {
                echo $error['message'];
            } else {
                $this->render('error', $error);
            }
        } else {
            $this->redirect(Yii::app()->getHomeUrl());
        }
        * 
        */
    }

}
