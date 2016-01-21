<?php

class EventController extends WebsiteController {

    public $menu;
    public $urlUpload;
    public $page_list;
    public $current_page;
    private $defaultPage = 'dandao';

    public function init() {
        $this->urlUpload = Yii::app()->baseUrl . '/upload/event/';
        return parent::init();
    }

    public function actionIndex() {
        $this->redirect(array('view', 'page' => $this->defaultPage));
    }

    public function actionView($page) {
        $this->current_page = $page;

        $this->render('viewXiumi');
    }

    public function actionAjaxDandao() {
        if (isset($_POST['EventDandao'])) {

            $output = array();
            $model = new EventDandao();
            $model->attributes = $_POST['EventDandao'];
            $model->user_ip = $this->getUserIp();
            $model->user_agent = Yii::app()->request->getUserAgent();

            $this->performAjaxValidation($model);

            $success = $model->save();
            if ($this->isAjaxRequest()) {
                if ($success) {
                    // success.
                    echo CJSON::encode(array(
                        'status' => 'true'
                    ));
                    Yii::app()->end();
                } else {
                    // error message.
                    $error = CActiveForm::validate($model);
                    if ($error != '[]') {
                        echo $error;
                    }
                    Yii::app()->end();
                }
            } else {
                if ($success) {
                    $this->setFlashMessage('event.dandao.success', '恭喜！您已成功报名！');
                }
            }

            //TODO: if($success){send email to admin.}
        } else {
            $this->throwPageNotFoundException();
        }
    }

    public function getPageList() {
        if ($this->page_list === null) {
            $this->page_list = array('dandao' => '上海胆道疾病会诊中心', 'liubaochi' => '肝病新疗法');
        }
        return $this->page_list;
    }

    public function getCurrentPageLabel() {
        $list = $this->getPageList();
        if (isset($list[$this->current_page])) {
            return $list[$this->current_page];
        }
    }

    public function loadPageMenu() {
        if ($this->menu === null) {
            $list = $this->getPageList();
            $this->menu = array();
            foreach ($list as $key => $value) {
                $menuItem = array(
                    'label' => $value,
                    'key' => $key,
                    'link' => $this->createUrl('event/view', array('page' => $key)),
                    'active' => '',
                );
                if ($key == $this->current_page) {
                    $menuItem['active'] = 'active';
                }
                $this->menu[] = $menuItem;
            }
        }
        return $this->menu;
    }

    /**
     * Performs the AJAX validation.
     * @param User $model the model to be validated
     */
    protected function performAjaxValidation($model) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'event-dandao-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

}
