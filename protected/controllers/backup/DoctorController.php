<?php

class DoctorController extends WebsiteController {

    public $listId;
    public $diseaselist = null;
    public $disease = null;

    /**
     * @return array action filters
     */
    public function filters() {
        return array(
            'accessControl', // perform access control for CRUD operations
            'postOnly + delete', // we only allow deletion via POST request
        );
    }

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules() {
        return array(
            array('allow', // allow all users to perform 'index' and 'view' actions
                'actions' => array('register', 'view', 'search'),
                'users' => array('*'),
            ),
            array('allow', // allow authenticated user to perform 'create' and 'update' actions
                'actions' => array('loadAvatar'),
                'users' => array('@'),
            ),
            array('deny', // deny all users
                'users' => array('*'),
            ),
        );
    }

    public function actionSearch() {

        $this->render('search');
    }

    /**
     * Lists all models.
     */
    /*
    public function actionIndex() {
        $dataProvider = new CActiveDataProvider('Doctor');
        $this->render('index', array(
            'dataProvider' => $dataProvider,
        ));
    }
     * 
     */

    /**
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     */
    public function actionView($id) {
        $model = $this->loadModel($id);
        $list = $this->getListId();
        Yii::app()->clientScript->registerCssFile(Yii::app()->theme->baseUrl . "/css/doctor.css");
        if (in_array($id, $list)) {
            $filename = $id;
            $this->render('pages/' . $filename, array('id' => $id, 'model' => $model));
        } else {

            $this->render('view', array(
                'model' => $model
            ));
            //throw new CHttpException(404, 'The requested page does not exist.');
        }
    }

    public function actionRegister() {
        $form = new DoctorForm("register");
        $form->initModel();

        $this->performAjaxValidation($form);

        $this->registerDoctor($form);

        $this->render('register', array(
            'model' => $form
        ));
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate() {
        $model = new Doctor;

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);

        if (isset($_POST['Doctor'])) {
            $model->attributes = $_POST['Doctor'];
            if ($model->save())
                $this->redirect(array('view', 'id' => $model->id));
        }

        $this->render('create', array(
            'model' => $model,
        ));
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionUpdate($id) {
        $model = $this->loadModel($id);
        $form = new DoctorForm();
        $form->initModel($model);

        // Uncomment the following line if AJAX validation is needed
        $this->performAjaxValidation($form);

        if (isset($_POST['DoctorForm'])) {
            $form->attributes = $_POST['DoctorForm'];
            if (isset($_POST['DoctorForm']['disease_list']) === false) {
                $form->disease_list = null;
            }
            $doctorMgr = new DoctorManager();
            $doctorMgr->updateDoctor($form);
            if ($form->hasErrors() === false) {
                $this->redirect(array('view', 'id' => $model->getId()));
            }
        }

        $this->render('update', array(
            'model' => $form,
        ));
    }

    /**
     * Deletes a particular model.
     * If deletion is successful, the browser will be redirected to the 'admin' page.
     * @param integer $id the ID of the model to be deleted
     */
    public function actionDelete($id) {
        $this->loadModel($id)->delete();

        // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
        if (!isset($_GET['ajax']))
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
    }

    public function actionLoadAvatar($uid = null) {

        $fileUrl = '';
        if ($uid === null || $uid == '') {
            $fileUrl = DoctorAvatar::getAbsDefaultAvatarUrl();
        } else {
            $avatar = DoctorAvatar::model()->getByUID($uid);

            if (isset($avatar)) {
                $fileUrl = $avatar->getAbsThumbnailUrl();
            } else {
                $fileUrl = DoctorAvatar::getAbsDefaultAvatarUrl();
            }
        }
        header('Location: ' . $fileUrl);
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return Doctor the loaded model
     * @throws CHttpException
     */
    public function loadModel($id) {
        $model = Doctor::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param Doctor $model the model to be validated
     */
    protected function performAjaxValidation($model) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'doctor-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

    public function getListId() {
        if (is_array($this->listId) === false) {
            //$this->listId = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 12, 13, 14, 15, 16, 17, 20, 21, 30, 31, 32);
            $this->listId = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25);
        }
        return $this->listId;
    }

    public function getDiseaseList() {
        if ($this->diseaselist == null) {
            $this->diseaselist = array('shen' => '肾脏', 'fei' => '肺部', 'weichang' => '胃肠', 'gandan' => '肝胆', 'xinxueguan' => '心血管', 'buyun' => '不孕不育', 'guke' => '骨科');
        }
        return $this->diseaselist;
    }

    public function getCurrentDiseaseLabel() {
        $list = $this->getDiseaseList();
        if (isset($list[$this->disease])) {
            return $list[$this->disease];
        }
    }

    public function loadDiseaseMenu() {
        $list = $this->getDiseaseList();
        $menu = array();
        foreach ($list as $key => $value) {
            $menuItem = array(
                'label' => $value,
                'key' => $key,
                'link' => $this->createUrl('doctor/search', array('d' => $key)),
                'active' => false
            );
            if ($key == $this->disease) {
                $menuItem['active'] = true;
            }
            $menu[] = $menuItem;
        }
        return $menu;
    }

    public function getDoctorListByDisease($disease = null) {
        if ($disease === null) {
            $disease = $this->disease;
        }
        $list;
        //$list = array('shen' => '肾脏', 'fei' => '肺部', 'weichang' => '胃肠', 'gandan' => '肝胆', 'xinxueguan' => '心血管', 'buyun' => '不孕不育');
        switch ($disease) {
            case 'shen': $list = array(20, 8, 9, 2);
                break;
            case 'fei': $list = array(23, 20, 7, 5, 3, 16, 15);
                break;
            case 'weichang':$list = array(23, 25, 24, 30, 33, 5, 15);
                break;
            case 'gandan':$list = array(24, 21, 19, 20, 15);
                break;
            case 'xinxueguan':$list = array(4, 11);
                break;
            case 'buyun':$list = array(12, 6);
                break;
            case 'guke':$list = array(17);
                break;
            default: $list = array();
                break;
        }
        return $list;
    }

    protected function registerDoctor(DoctorForm $form) {

        if (isset($_POST['DoctorForm'])) {
            $values = $_POST['DoctorForm'];
            $form->setAttributes($values);
            $form->hp_dept_name = $form->faculty;

            //$form->hospital_id = null;
            $doctorMgr = new DoctorManager();
            //if ($doctorMgr->createDoctor($form, false)) {   // do not check verify_code.
            if ($doctorMgr->createDoctor($form)) {
                // Send email to inform admin.
                $doctorId = $form->getId();
                $with = array('doctorCerts', 'doctorHospital', 'doctorHpDept', 'doctorCity');
                $idoctor = $doctorMgr->loadIDoctor($doctorId, $with);

                if (isset($idoctor)) {
                    $emailMgr = new EmailManager();
                    $emailMgr->sendEmailDoctorRegister($idoctor);
                }
                // store successful message id in session.
                $this->setFlashMessage("doctor.success", "恭喜您注册成功！");
                $this->refresh(true);     // terminate and refresh the current page.
            } else {
                
            }
        }
    }

}
