<?php

class DiseaseController extends WebsiteController {

    public function actionView($id) {
        $apiService = new ApiViewDisease($id);
        $data = $apiService->loadApiViewData();
        //    $data = new stdClass();        
        //    $data->disease = isset($output['disease']) ? $output['disease'] : null;
        //    $data->expertteams = isset($output['expertteams']) ? $output['expertteams'] : null;
        $this->render('view', array(
            'data' => $data
        ));
    }

    public function actionIndex() {
        $this->render('index');
    }

}
