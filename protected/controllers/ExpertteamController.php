<?php

class ExpertteamController extends WebsiteController {

    /**
     * Declares class-based actions.
     */
    public function actions() {
        return array(
            // page action renders "static" pages stored under 'protected/views/site/pages'
            // They can be accessed via: index.php?r=site/page&view=FileName
            'page' => array(
                'class' => 'CViewAction',
            ),
        );
    }

    public function actionView($id) {
        $expteamMgr = new ExpertTeamManager();
        $imodel = $expteamMgr->loadIExpertTeamById($id);
        
        $this->render('view', array(
            'team'=>$imodel,          
        ));
    }
    /*
    public function actionView($code) {
        $exTeamMgr = new ExpertTeamManager();
        $team = $exTeamMgr->loadTeamByCode($code);
        $iteam = $exTeamMgr->convertToIExpertTeam($team);
        if (is_null($iteam)) {
            $this->throwPageNotFoundException();
        }
        $this->render('view', array(
            'team' => $iteam
        ));
    }
     * 
     */

    public function actionIndex() {
        $this->render("index");
    }

}
