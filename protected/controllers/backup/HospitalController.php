<?php

class HospitalController extends WebsiteController {

    public function actionIndex($city = null) {
        $this->render("index");
    }

    public function actionView($id) {
        //$ifaculty = $this->loadIFaculty($id);
        $hospitalMgr = new HospitalManager();
        $with = array('hospitalCity', 'hospitalDepartments' => array('on' => 'hospitalDepartments.is_show=1'));
        $ihospital = $hospitalMgr->loadIHospitalById($id, $with);

        if (is_null($ihospital)) {
            $this->throwPageNotFoundException();
        }

        $this->render('view', array(
            'model' => $ihospital,
        ));
    }

}
