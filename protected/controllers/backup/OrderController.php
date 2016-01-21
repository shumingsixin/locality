<?php

class OrderController extends WebsiteController{
    
    public function actionView($refno){
        $model = SalesOrder::model()->getByAttributes(array('ref_no'=>$refno));
        $this->show_header=true;
        $this->show_footer=false;
        $this->show_baidushangqiao=false;
        //$this->render('view', array('model' => $model));
        $this->render('viewOne', array('model' => $model));
    }
    
    public function actionViewtest($refno){
        $model = SalesOrder::model()->getByAttributes(array('ref_no'=>$refno));
        $this->show_header=true;
        $this->show_footer=false;
        $this->show_baidushangqiao=false;
        $this->render('viewtest', array('model' => $model));
    }
    
}
