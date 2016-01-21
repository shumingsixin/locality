<?php

class MrbookingController extends WebsiteController {

    public $model;
    private $medical_record;

    public function filterMedicalRecordContext($filterChain) {
        $user = $this->loadUser();

        $mrid = null;
        if (isset($_GET['mrid'])) {
            $mrid = $_GET['mrid'];
        } else if (isset($_POST['mrid'])) {
            $mrid = $_POST['mrid'];
        }

        $this->loadMedicalRecordByIdAndUserId($mrid, $user->getId());

        //complete the running of other filters and execute the requested action.
        $filterChain->run();
    }

    /**
     * @return array action filters
     */
    public function filters() {
        return array(
            'accessControl', // perform access control for CRUD operations
            'postOnly + delete', // we only allow deletion via POST request
            'userContext + index view create',
                //'medicalRecordContext + create',
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
                'actions' => array(''),
                'users' => array('*'),
            ),
            array('allow', // allow authenticated user to perform 'create' and 'update' actions
                'actions' => array('index', 'view', 'create'),
                'users' => array('@'),
            ),
            array('allow', // allow admin user to perform 'admin' and 'delete' actions
                'actions' => array(''),
                'users' => array('admin'),
            ),
            array('deny', // deny all users
                'users' => array('*'),
            ),
        );
    }

    /**
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     */
    public function actionView($id) {
        $this->render('view', array(
            'model' => $this->loadModel($id),
        ));
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate() {
        if ($this->hasFlashMessage('mrbooking.success')) {
            // has success in session, so render success page.
            $id = $this->getFlashMessage("mrbooking.success");
            $model = $this->loadModel($id, array('mrbMedicalRecord'));
            $this->render('success', array('model' => $model));
            Yii::app()->end();
        }

        $user = $this->loadUser();
        $mrid = null;
        if (isset($_GET['mrid'])) {
            $mrid = $_GET['mrid'];
        } else if (isset($_POST['mrid'])) {
            $mrid = $_POST['mrid'];
        } else {
            $this->redirect(array('medicalrecord/create', 'returnUrl' => $this->createUrl('mrbooking/create')));
        }

        $mRecord = $this->loadMedicalRecordByIdAndUserId($mrid, $user->id);

        $form = new MRBookingForm();
        $form->setUserId($user->getId());
        $form->setMrId($mRecord->getId());
        $form->initModel();
        // Uncomment the following line if AJAX validation is needed
        $this->performAjaxValidation($form);

        if (isset($_POST['MRBookingForm'])) {
            $values = $_POST['MRBookingForm'];
            $form->attributes = $values;

            $mrMgr = new MedicalRecordManager();
            $mrMgr->createNewBooking($form);

            if ($form->hasErrors() === false) { // store success id in session.
                $this->setFlashMessage('mrbooking.success', $form->id);
                // Send email to inform admin.
                $ibooking = $mrMgr->loadIMedicalRecordBooking($form->id);
                if (isset($ibooking)) {
                    $emailMgr = new EmailManager();
                    $emailMgr->sendEmailMrBooking($ibooking);
                }

                $this->refresh(true);   // terminate and refresh the current page.
            }
        }

        $this->render('create', array(
            'form' => $form,
            'mRecord' => $mRecord
        ));
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionUpdate($id) {
        $model = $this->loadModel($id);

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);

        if (isset($_POST['MedicalRecordBooking'])) {
            $model->attributes = $_POST['MedicalRecordBooking'];
            if ($model->save())
                $this->redirect(array('view', 'id' => $model->id));
        }

        $this->render('update', array(
            'model' => $model,
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

    /**
     * Lists all models.
     */
    public function actionIndex() {
        $this->redirect(array('create'));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return MedicalRecordBooking the loaded model
     * @throws CHttpException
     */
    public function loadModel($id, $with = null) {
        if (is_null($this->model)) {
            $this->model = MedicalRecordBooking::model()->findByPk($id);
            if (is_null($this->model)) {
                throw new CHttpException(404, 'The requested page does not exist.');
            }
        }
        return $this->model;
    }

    /**
     * Performs the AJAX validation.
     * @param MedicalRecordBooking $model the model to be validated
     */
    protected function performAjaxValidation($model) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'mr-booking-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

    public function loadMedicalRecordByIdAndUserId($id, $userId, $with = null) {
        if (is_null($this->medical_record)) {
            $this->medical_record = MedicalRecord::model()->getByAttributes(array('id' => $id, 'user_id' => $userId), $with);
            if (is_null($this->medical_record)) {
                throw new CHttpException(404, 'The requested page does not exist.');
            }
        }
        return $this->medical_record;
    }

}
