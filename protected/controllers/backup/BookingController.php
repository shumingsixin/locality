<?php

class BookingController extends WebsiteController {

    private $model = null;  // Booking

    /**
     * @return array action filters
     */

    public function filters() {
        return array(
            'accessControl', // perform access control for CRUD operations
            'postOnly + delete', // we only allow deletion via POST request
            'userContext + index',
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
                'actions' => array('index', 'view', 'test', 'quickbook', 'create', 'ajaxCreate', 'ajaxUploadFile'),
                'users' => array('*'),
            ),
            array('allow', // allow authenticated user to perform 'create' and 'update' actions
                'actions' => array('ajaxCreate', 'update'),
                'users' => array('@'),
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

    public function actionCreate() {
        $values = $_GET;
        if (isset($values['ajax'])) {
            $this->show_header = false;
            $this->show_footer = false;
            $this->show_traffic_script = false;
            $this->show_baidushangqiao = false;
        }

        //$request = Yii::app()->request;
        if (isset($values['tid'])) {
            // 预约专家团队
            $form = new BookExpertTeamForm();
            $form->initModel();
            $form->setExpertTeamId($values['tid']);
            $form->setExpertTeamData();
            $userId = $this->getCurrentUserId();
            if (isset($userId)) {
                $form->setUserId($userId);
            }
            //@TEST:
            //     $data = $this->testDataDoctorBook();
            //   $form->setAttributes($data, true);
            $this->render('bookExpertteam', array('model' => $form));
        } elseif (isset($values['did'])) {
            // 预约医生
            $form = new BookDoctorForm();
            $form->initModel();
            $form->setDoctorId($values['did']);
            $form->setDoctorData();
            $userId = $this->getCurrentUserId();
            if (isset($userId)) {
                $form->setUserId($userId);
            }
            //@TEST:
            //    $data = $this->testDataDoctorBook();
            //    $form->setAttributes($data, true);

            $this->render('doctor', array('model' => $form));
        } else {
            // 快速预约
            $form = new BookQuickForm();
            $form->initModel();
            $userId = $this->getCurrentUserId();
            if (isset($userId)) {
                $form->setUserId($userId);
            }
            //@TEST:
            //    $data = $this->testDataQuickBook();
            //    $form->setAttributes($data, true);

            $this->render('quickbook', array('model' => $form));
        }
    }

    public function actionAjaxUploadFile() {
        $output = array('status' => 'no');
        if (isset($_POST['booking'])) {
            $values = $_POST['booking'];
            $bookingMgr = new BookingManager();
            if (isset($values['id']) === false) {
                // ['patient']['mrid'] is missing.
                $output['status'] = 'no';
                $output['error'] = 'invalid parameters';
                $this->renderJsonOutput($output);
            }
            $bookingId = $values['id'];
            //    $userId = $this->getCurrentUserId();
            $booking = $bookingMgr->loadBookingMobileById($bookingId);
            //$patientMR = $patientMgr->loadPatientMRById($mrid);
            if (isset($booking) === false) {
                // PatientInfo record is not found in db.
                $output['status'] = 'no';
                $output['errors'] = 'invalid id';
                $this->renderJsonOutput($output);
            } else {
                $output['bookingId'] = $booking->getId();
                $ret = $bookingMgr->createBookingFile($booking);
                if (isset($ret['error'])) {
                    $output['status'] = 'no';
                    $output['error'] = $ret['error'];
                    $output['file'] = '';
                } else {
                    // create file output.
                    $fileModel = $ret['filemodel'];
                    $data = new stdClass();
                    $data->id = $fileModel->getId();
                    $data->bookingId = $fileModel->getBookingId();
                    $data->fileUrl = $fileModel->getAbsFileUrl();
                    $data->tnUrl = $fileModel->getAbsThumbnailUrl();
                    //    $data->deleteUrl = $this->createUrl('patient/deleteMRFile', array('id' => $fileModel->getId()));
                    $output['status'] = 'ok';
                    $output['file'] = $data;
                    //$output['redirectUrl'] = $this->createUrl("home/index");
                }
            }
        } else {
            $output['error'] = 'missing parameters';
        }
        $this->renderJsonOutput($output);
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionQuickbook() {

        $this->show_header = false;
        $this->show_footer = false;
        $userId = Yii::app()->user->id;

        $form = new BookingForm();
        $form->initModel($userId);

        if (Yii::app()->request->isPostRequest === false) {
            $form->setValuesFromRequest($_GET);
            $form->loadData();
        } else {
            $this->createBooking($form);
        }
        $this->render('quickbook', array(
            'model' => $form,
        ));
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionAjaxCreate() {
        $output = array('status' => 'no');
        if (isset($_POST['booking'])) {
            $values = $_POST['booking'];
            if (isset($values['expteam_id'])) {
                // 预约专家团队
                $form = new BookExpertTeamForm();
                $form->setAttributes($values, true);
                $form->setExpertTeamData();
            } elseif (isset($values['doctor_id'])) {
                // 预约医生
                $form = new BookDoctorForm();
                $form->setAttributes($values, true);
                $form->setDoctorData();
            } else {
                // 快速预约
                $form = new BookQuickForm();
                $form->setAttributes($values, true);
            }
            $form->initModel();
            //$form->validate();
            //var_dump($form->attributes);exit;

            if ($form->validate()) {
                /*
                  $output['status'] = 'no';
                  $output['form'] = 'is success';
                  $this->renderJsonOutput($output);
                 */
                $booking = new Booking();
                $booking->setAttributes($form->attributes, true);
                $booking->user_agent = StatCode::USER_AGENT_WEBSITE;
                if ($booking->save()) {
                    $output['status'] = 'ok';
                    $output['booking']['id'] = $booking->getId();
                    $booking = Booking::model()->getById($booking->id);
                    $email = 0;
                //    $email = $this->sendBookingEmailNew($booking);
                    $output['email'] = $email;
                } else {
                    $output['errors'] = $booking->getErrors();
                }
            } else {
                $output['errors'] = $form->getErrors();
            }
        } else {
            $output['error'] = 'missing parameters';
        }

        $this->renderJsonOutput($output);
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

        if (isset($_POST['Booking'])) {
            $model->attributes = $_POST['Booking'];
            if ($model->save())
                $this->redirect(array('view', 'id' => $model->id));
        }

        $this->render('update', array(
            'model' => $model,
        ));
    }

    /**
     * Lists all models.
     */
    public function actionIndex() {
        $dataProvider = new CActiveDataProvider('Booking');
        $this->render('index', array(
            'dataProvider' => $dataProvider,
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return Booking the loaded model
     * @throws CHttpException
     */
    public function loadModel($id) {
        if (is_null($this->model)) {
            $this->model = Booking::model()->findByPk($id);
        }
        if (is_null($this->model)) {
            throw new CHttpException(404, 'The requested page does not exist.');
        }
        return $this->model;
    }

    /**
     * Performs the AJAX validation.
     * @param Booking $model the model to be validated
     */
    protected function performAjaxValidation($model) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'booking-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

    protected function sendBookingEmailNew($booking) {
        $data = new stdClass();
        $data->id = $booking->getId();
        $data->refNo = $booking->getRefNo();
        if ($booking->bk_type == StatCode::BK_TYPE_EXPERTTEAM) {
            $data->expertBooked = $booking->getExpertteamName();
        } elseif ($booking->bk_type == StatCode::BK_TYPE_DOCTOR) {
            $data->expertBooked = $booking->getDoctorName();
        } else {
            $data->expertBooked = $booking->getDoctorName();
        }
        $data->hospitalName = $booking->getHospitalName();
        $data->hpDeptName = $booking->getHpDeptName();
        $data->patientName = $booking->getContactName();
        $data->mobile = $booking->getMobile();
        $data->diseaseName = $booking->getDiseaseName();
        $data->diseaseDetail = $booking->getDiseaseDetail();
        $data->dateCreated = $booking->getDateCreated();
        $data->submitFrom = '';
        $emailMgr = new EmailManager();
        return $emailMgr->sendEmailBookingNew($data);
    }

    /**
     * /booking/quickbook?type=4&hid=$hid&dept=$dept
     * @param BookingForm $form
     */
    protected function createBooking(BookingForm $form) {

        if (isset($_POST['BookingForm'])) {
            $values = $_POST['BookingForm'];
            $form->attributes = $values;
            $form->setValuesFromRequest($values, "post");

            // Uncomment the following line if AJAX validation is needed
            $this->performAjaxValidation($form);
            // loads data accroding to values from request.
            $form->loadData();

            $bookingMgr = new BookingManager();
            $checkVerifyCode = true;    // checks sms verify code before creating a new booking in db.
            $sendEmail = true;  // sends email to inform admin.
            if ($bookingMgr->createBooking($form, $checkVerifyCode)) {
                $ibooking = $bookingMgr->loadIBooking($form->id);
                if ($sendEmail && isset($ibooking)) {
                    $emailMgr = new EmailManager();
                    $emailMgr->sendEmailQuickBooking($ibooking);
                }
                // store successful message id in session.
                $this->setFlashMessage("booking.success", "预约成功!我们的客服人员会在第一时间与您确认您的预约细节。");
                $this->refresh(true);     // terminate and refresh the current page.
            } else {
                
            }
        }
    }

    private function testDataQuickBook() {
        return array(
            'hospital_name' => '肿瘤医院',
            'hp_dept_name' => '肿瘤科',
            'doctor_name' => '李医生',
            'contact_name' => '王小明',
            'mobile' => '18217531537',
            'verify_code' => '123456',
            'disease_name' => '小腿骨折',
            'disease_detail' => '小腿都碎了啊！咋办啊'
        );
    }

    private function testDataDoctorBook() {
        return array(
            //    'hospital_name' => '肿瘤医院',
            //    'hp_dept_name' => '肿瘤科',
            //    'doctor_name' => '李医生',
            'contact_name' => '王小明',
            'mobile' => '18217531537',
            'verify_code' => '123456',
            'disease_name' => '小腿骨折',
            'disease_detail' => '小腿都碎了啊！咋办啊'
        );
    }

    private function testDataExpertTeamBook() {
        return array(
            //    'hospital_name' => '肿瘤医院',
            //    'hp_dept_name' => '肿瘤科',
            //    'doctor_name' => '李医生',
            'contact_name' => '王小明',
            'mobile' => '18217531537',
            'verify_code' => '123456',
            'disease_name' => '小腿骨折',
            'disease_detail' => '小腿都碎了啊！咋办啊'
        );
    }

}
