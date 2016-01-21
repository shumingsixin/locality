<?php

class MarketingController extends WebsiteController {

    public $page_list;
    public $urlAssets;

    public function init() {
        $this->urlAssets = Yii::app()->baseUrl . '/resource/marketing/';
        return parent::init();
    }

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

    public function actionView($id=null) {
        $list = $this->getPageList();
        if (isset($list[$id]) === false) {
            throw new CHttpException(404, 'The requested page does not exist.');
        } else {
            $urlAssets = $list[$id];
            if ($id == 'intro2') {
                $mobileDetect = Yii::app()->mobileDetect;
                if ($mobileDetect->isMobile()) {
                    $this->redirect('http://r.xiumi.us/stage/v3/2bkr1/840062');
                } else {
                    $this->renderPartial('pages/intro', array(
                        'urlAssets' => $urlAssets
                    ));
                }
            } else {
                $this->render('pages/' . $id, array(
                    'urlAssets' => $urlAssets
                ));
            }
        }
    }

    public function getPageList() {
        if ($this->page_list === null) {
            $this->page_list = array('waigongzouhao' => $this->urlAssets . 'waigongzouhao', 'lianghui' => $this->urlAssets . 'lianghui', 'intro2' => $this->urlAssets);
        }
        return $this->page_list;
    }

}
