<?php

class FacultyController extends WebsiteController{

    public $current_page=null;
    
    public function actionView($name) {
        $this->headerUTF8();
        //$ifaculty = $this->loadIFaculty($id);
        $faculty = Faculty::model()->getActiveRecordByName($name);
        
        if (is_null($faculty)) {
            $this->throwPageNotFoundException();
        }
       
        $facultyMgr = new FacultyManager();
        $ifaculty = $facultyMgr->loadIFaculty2($faculty->getId());
        
         $this->current_page=array("label"=>$ifaculty->getName(),"code"=>$ifaculty->getCode());
        
        $this->render('view', array(
            'model' => $ifaculty,
        ));
    }

}
