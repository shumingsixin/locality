<?php

class BookingManager {

    public function loadBookingByIdAndUserId($id, $userId, $attributes = null, $with = null) {
        return Booking::model()->getByIdAndUserId($id, $userId, $attributes, $with);
    }

    public function loadBookingMobileById($id, $attributes = null, $with = null) {
        return Booking::model()->getById($id, $with);
    }

    /**
     * 取消订单
     * @param type $id
     */
    public function cancelbook($id) {
        $output = array('status' => 'no');
        $model = Booking::model()->getById($id);
        if (isset($model)) {
            $model->bk_status = StatCode::BK_STATUS_CANCELLED;
            if ($model->update(array('bk_status'))) {
                $output['status'] = 'ok';
                $output['id'] = $model->getId();
            } else {
                $output['errors'] = $model->getErrors();
            }
        } else {
            $output['errors'] = 'no data...';
        }
        return $output;
    }

    /*     * ****** Api 5.0 ******* */

    public function apiCreateBookingV4(User $user, $values, $checkVerifyCode = true, $sendEmail = true) {
        $output['status'] = 'ok';
        $output['errorCode'] = 0;
        $output['errorMsg'] = 'success';
        $output['results'] = array();
        // create a new Booking and save into db.
        $model = new Booking();
        $model->user_id = $user->getId();
        $model->bk_status = StatCode::BK_STATUS_NEW;

        if (isset($values['expteam_id'])) {
            // 预约专家团队
            $model = $this->setExpertTeamData($model, $values['expteam_id']);
            $model->bk_type = StatCode::BK_TYPE_EXPERTTEAM;
        } elseif (isset($values['doctor_id'])) {
            // 预约医生
            $model = $this->setDoctorData($model, $values['doctor_id']);
            $model->bk_type = StatCode::BK_TYPE_DOCTOR;
        } else {
            // 快速预约
            $model->bk_type = StatCode::BK_TYPE_QUICKBOOK;
        }

        $model->setAttributes($values);

        $this->createAppBookingV4($model);
        if ($model->hasErrors()) {
            $output['status'] = 'no';
            $output['errorCode'] = 400;
            $output['errorMsg'] = $model->getFirstErrors();
            return $output;
        }
        // load this booking from db and convert it to IBooking model for viewing.
        try {
            if ($sendEmail && isset($model)) {
                // Send email to inform admin.
                $emailMgr = new EmailManager();
                $emailMgr->sendEmailAppBooking($model);
            }
        } catch (CException $ex) {
            Yii::log($ex->getMessage(), CLogger::LEVEL_ERROR, 'BookingManager.apiCreateBooking');
//            $output['error_msg'] = "发送电邮出错";
        }
        $output['results'] = array(
            'booking_id' => $model->getId(),
            'actionUrl' => Yii::app()->createAbsoluteUrl('/api/bookingfile'),
        );
        return $output;
    }

    public function createAppBookingV4(Booking $model) {

        // create new Booking model and save into db.
        if ($model->save() === false) {
            // error occured while saving into db.
            $model->addErrors($model->getErrors());
            return ($model->hasErrors() === false);
        } else {
            // Create BookingFile from $_FILES.
            // saves uploaded files into filesystem and db.
            $this->createBookingFiles($model->getId(), $model->getUserId());
        }
        return ($model->hasErrors() === false);
    }

    /**
     * single upload of BookingFile.
     * @param array $values array('booking_id'=>$bid, 'username'=>$mobile, 'token'=>$token);
     * @param type $file
     * @return string
     */
    public function apiCreateBookingFileV4(User $user, $values, $file) {


        if (is_null($file)) {
            $output['status'] = EApiViewService::RESPONSE_NO;
            $output['errorCode'] = ErrorList::BAD_REQUEST;
            $output['errorMsg'] = '请上传图片';
            return $output;
        }
        // validates input parameters.
        if (isset($values['booking_id']) === false) {
            $output['status'] = EApiViewService::RESPONSE_NO;
            $output['errorCode'] = ErrorList::BAD_REQUEST;
            $output['errorMsg'] = '参数错误';
            return $output;
        }
        $bookingId = $values['booking_id'];
        $userId = $user->getId();
        $mobile = $user->getMobile();

        // TODO: load $booking from db by $bookingId.
        // check if $booking.user_id == $user.id;
        //$booking = Booking::model()->getByIdAndUserId($bookingId, $userId);
        $booking = Booking::model()->getByIdAndUser($bookingId, $userId, $mobile);
        if (is_null($booking)) {
            $output['status'] = EApiViewService::RESPONSE_NO;
            $output['errorCode'] = ErrorList::UNAUTHORIZED;
            $output['errorMsg'] = '您没有权限执行此操作';
            return $output;
        }
        // create BookingFile and save into db.

        $bookingFile = $this->saveBookingFile($file, $bookingId, $userId);
        if ($bookingFile->hasErrors()) {
            $output['status'] = EApiViewService::RESPONSE_NO;
            $output['errorCode'] = ErrorList::BAD_REQUEST;
            $output['errorMsg'] = $bookingFile->getFirstErrors();
            return $output;
        }
        return array(
            'status' => EApiViewService::RESPONSE_OK,
            'errorCode' => 0,
            'errorMsg' => 'success',
            'results' => array(),
        );
    }

    /*     * ****** Api 2.0 ******* */

    public function apiCreateBooking(User $user, $values, $checkVerifyCode = true, $sendEmail = true) {
        $output = array('status' => false);


        // create a new Booking and save into db.
        $model = new Booking();
        $model->user_id = $user->getId();
        $model->bk_status = StatCode::BK_STATUS_NEW;

        if (isset($values['expteam_id'])) {
            // 预约专家团队
            $model = $this->setExpertTeamData($model, $values['expteam_id']);
            $model->bk_type = StatCode::BK_TYPE_EXPERTTEAM;
        } elseif (isset($values['doctor_id'])) {
            // 预约医生
            $model = $this->setDoctorData($model, $values['doctor_id']);
            $model->bk_type = StatCode::BK_TYPE_DOCTOR;
        } else {
            // 快速预约
            $model->bk_type = StatCode::BK_TYPE_QUICKBOOK;
        }
        $model->disease_detail = $values['patient_condition'];
        $model->remark = $values['appt_date_str'];
        $model->setAttributes($values);

        $this->createAppBookingV4($model);
        if ($model->hasErrors()) {
            $output['status'] = 'no';
            $output['errorCode'] = 400;
            $output['errorMsg'] = $model->getFirstErrors();
            return $output;
        }
        // load this booking from db and convert it to IBooking model for viewing.
        try {
            if ($sendEmail && isset($model)) {
                // Send email to inform admin.
                $emailMgr = new EmailManager();
                $emailMgr->sendEmailAppBooking($model);
            }
        } catch (CException $ex) {
            Yii::log($ex->getMessage(), CLogger::LEVEL_ERROR, 'BookingManager.apiCreateBooking');
//            $output['error_msg'] = "发送电邮出错";
        }

        $ibooking = $this->loadIBooking($model->getId());

        $output['status'] = true;
        $output['msg'] = '预约成功';
        $output['booking'] = $ibooking;

        return $output;
    }

    /**
     * single upload of BookingFile.
     * @param array $values array('booking_id'=>$bid, 'username'=>$mobile, 'token'=>$token);
     * @param type $file
     * @return string
     */
    public function apiCreateBookingFile(User $user, $values, EUploadedFile $file) {
        $output = array('status' => false);

        // validates input parameters.
        if (isset($values['booking_id']) === false) {
            $output['errors']['error_code'] = ErrorList::BAD_REQUEST;
            $output['errors']['error_msg'] = 'Wrong parameters.';
            return $output;
        }
        $bookingId = $values['booking_id'];
        $userId = $user->getId();

        // TODO: load $booking from db by $bookingId.
        // check if $booking.user_id == $user.id;
        $booking = Booking::model()->getByIdAndUserId($bookingId, $userId);
        if (is_null($booking)) {
            $output['errors']['error_code'] = ErrorList::UNAUTHORIZED;
            $output['errors']['error_msg'] = '您没有权限执行此操作';
            return $output;
        }
        // create BookingFile and save into db.
        $bookingFile = $this->saveBookingFile($file, $bookingId, $userId);
        if ($bookingFile->hasErrors()) {

            $output['errors'] = $bookingFile->getFirstErrors();
            return $output;
        } else {
            // creates IBookingFile model for json output.
            $bookingFile = BookingFile::model()->getById($bookingFile->getId());
            $ibookingFile = new IBookingFile();
            $ibookingFile->initModel($bookingFile);
            $output['status'] = true;
            $output['bookingFile'] = $ibookingFile;
        }
        return $output;
    }

    public function apiLoadIBookingJsonByUser(User $user, $bookingId) {
        $ibooking = $this->loadIBookingByUser($user, $bookingId);
        $output['booking'] = $ibooking;

        return $output;
    }

    public function apiLoadAllIBookingsJsonByUser(User $user, $options = null) {
        $ibookingList = $this->loadAllIBookingsByUser($user, null, $options);
        $output['countBookings'] = count($ibookingList);
        $output['bookings'] = $ibookingList;
        return $output;
    }

    public function loadAllIBookingsByUser(User $user, $with = null, $options = null) {
        $ibookingList = array();
        if (is_null($with)) {
            $with = array("bkOwner", "bkFiles", "bkDoctor" => array('with' => "doctorHospital"), "bkExpertTeam", "bkHpDept", "bkHospital");
        }
        $bookings = $this->loadAllBookingsByUser($user, $with, $options);
        $attributes = null;
        if (arrayNotEmpty($bookings)) {
            foreach ($bookings as $booking) {
                $ibookingList[] = $this->convertToIBooking($booking, $attributes, $with);
            }
        }
        return $ibookingList;
    }

    public function loadIBookingByUser(User $user, $bookingId) {
        $with = array("bkOwner", "bkFiles", "bkDoctor", "bkExpertTeam", "bkHpDept", "bkHospital");
        //$with = array('owner', 'bookingFiles');
        $attributes = null;
        //$booking = Booking::model()->getByIdAndUserId($bookingId, $user->getId(), $with);
        $booking = Booking::model()->getByIdAndUser($bookingId, $user->getId(), $user->getMobile(), $with);
        if (is_null($booking)) {
            return null;
        } else {
            $ibooking = $this->convertToIBooking($booking, $attributes, $with);
            return $ibooking;
        }
    }

    public function loadAllBookingsByUser($user, $with = null, $options = null) {
        /*
          if (is_null($with)) {
          $with = array("owner", "bookingFiles", "facultyBooked", "doctorBooked", "expertTeamBooked", "hospitalDeptBooked", "hospitalBooked");
          }
         * 
         */
        return Booking::model()->getAllByUserIdOrMobile($user->getId(), $user->getMobile(), $with, $options);
    }

    public function createAppBooking(AppBookingForm $form) {
        if ($form->validate()) {

            // create new Booking model and save into db.
            $model = new BookingOld();
            $model->setAttributes($form->getSafeAttributes());
            if ($model->save() === false) {
                // error occured while saving into db.
                $form->addErrors($model->getErrors());

                return ($form->hasErrors() === false);
            } else {

                // Create BookingFile from $_FILES.
                // saves uploaded files into filesystem and db.
                $form->id = $model->getId();
                $this->createBookingFiles($model->getId(), $model->getUserId());
            }
        }
        return ($form->hasErrors() === false);
    }

    public function createBooking(BookingForm $form, $checkVerifyCode = true) {
        if ($form->validate()) {
            if ($checkVerifyCode) {
                // Verifies AuthSmsVerify by using $mobile & $verifyCode.        
                $userHostIp = Yii::app()->request->getUserHostAddress();
                $authMgr = new AuthManager();
                $authSmsVerify = $authMgr->verifyCodeForBooking($form->getMobile(), $form->getVerifyCode(), $userHostIp);
                if ($authSmsVerify->isValid() === false) {
                    // sms verify code is not valid.
                    $form->addError('verify_code', $authSmsVerify->getError('code'));

                    return false;
                }
            }
            // create new Booking model and save into db.
            $model = new Booking();
            $model->setAttributes($form->getSafeAttributes());

            if ($model->save() === false) {
                // error occured while saving into db.
                $form->addErrors($model->getErrors());

                return ($form->hasErrors() === false);
            } else {
                // deactive current smsverify.                
                if (isset($authSmsVerify)) {
                    $authMgr->deActiveAuthSmsVerify($authSmsVerify);
                }
                // Create BookingFile from $_FILES.
                // saves uploaded files into filesystem and db.
                $form->id = $model->getId();
                $this->createBookingFiles($model->getId(), $model->getUserId());
            }
        }
        return ($form->hasErrors() === false);
    }

    public function createBookingFiles($bookingId, $userId) {
        $uploadField = BookingFile::model()->file_upload_field;
        $files = EUploadedFile::getInstancesByName($uploadField);

        $output = array();
        if (arrayNotEmpty($files)) {
            foreach ($files as $file) {
                $output[] = $this->saveBookingFile($file, $bookingId, $userId);
            }
        }
        return $output;
    }

    public function createBookingFile($booking) {
        $bookingId = $booking->getId();
        $userId = $booking->getUserId();

        //$uploadField = BookingFile::model()->file_upload_field;
        $uploadField = 'file';
        $file = EUploadedFile::getInstanceByName($uploadField);
        if (isset($file)) {
            //文件储存
            $output['filemodel'] = $this->saveBookingFile($file, $bookingId, $userId);
        } else {
            $output['error'] = 'missing uploaded file in - ' . $uploadField;
        }
        return $output;
    }

    /**
     * Get EUploadedFile from $_FILE. Create BookingFile model. Save file in filesystem. Save model in db.
     * @param EUploadedFile $file EUploadedFile::getInstances()
     * @param integer $bookingId Booking.id     
     * @return BookingFile 
     */
    private function saveBookingFile($file, $bookingId, $userid) {
        $bFile = new BookingFile();
        $bFile->initModel($bookingId, $userid, $file);
        $bFile->saveModel();

        return $bFile;
    }

    public function cerateBookingCorp($booking) {
        $bookingId = $booking->getId();
        $userId = $booking->getUserId();
        $uploadField = 'file';
        $file = EUploadedFile::getInstanceByName($uploadField);
        if (isset($file)) {
            //文件储存
            $output['filemodel'] = $this->saveBookingCorp($file, $bookingId, $userId);
        } else {
            $output['error'] = 'missing uploaded file in - ' . $uploadField;
        }
        return $output;
    }

    private function saveBookingCorp($file, $bookingId, $userid) {
        $bFile = new BookingCorpIc();
        $bFile->initModel($bookingId, $userid, $file);
        $bFile->saveModel();

        return $bFile;
    }

    public function loadIBooking($id, $with = null) {
        if (is_null($with)) {
            $with = array('bkOwner', 'bkDoctor', 'bkHospital', 'bkHpDept', 'bkFiles');
        }
        $attributes = null;
        $model = $this->loadBookingById($id, $with);
        if (isset($model)) {
            $ibooking = $this->convertToIBooking($model, $attributes, $with);
            return $ibooking;
        } else {
            return null;
        }
    }

    public function loadBookingById($id, $with = null) {
        return Booking::model()->getById($id, $with);
    }

    public function loadBookingByRefNo($refno) {
        return MedicalRecordBooking::model()->getByRefNo($refno);
        /*
          if (is_null($model)) {
          throw new CHttpException(404, 'Record is not found.');
          }
          return $model;
         * 
         */
    }

    /**
     * 
     * @param type $mobile
     * @param type $code
     * @param type $userIp
     * @return type AuthSmsVerify
     */
    private function checkVerifyCode($mobile, $code, $userIp = null) {
        $authMgr = new AuthManager();
        $actionType = AuthSmsVerify::ACTION_BOOKING;
        return $authMgr->verifyAuthSmsCode($mobile, $code, $actionType, $userIp);
    }

    /**
     * 
     * @param Booking $model
     * @param array $with array('owner','bookingFIles','doctor')
     * @return \IBooking|\IExpertTeam
     */
    public function convertToIBooking(Booking $model, $attributes = null, $with = null) {
        if (isset($model)) {
            $imodel = new IBooking();
            $imodel->initModel($model, $attributes);
            $imodel->addRelatedModel($model, $with);
            return $imodel;
        } else {
            return null;
        }
    }

    /**
     * 根据bookingid查询数据
     * @param type $BookingIds
     * @return type
     */
    public function loadAllBookingsByIds($BookingIds, $attr = null, $with = null, $options = null) {
        return Booking::model()->getAllByIds($BookingIds, $attr, $with, $options);
    }

    /**
     * 根据专家团队Id 设置预约的相关信息
     * @param Booking $model
     * @param $expteam_id
     * @return Booking
     */
    private function setExpertTeamData(Booking $model, $expteam_id) {
        $with = array('expteamHospital', 'expteamHpDept');
        $expertTeam = ExpertTeam::model()->getById($expteam_id, $with);
        if (isset($model)) {
            $model->expteam_name = $expertTeam->getName();
            $hospital = $expertTeam->getHospital();
            if (isset($hospital)) {
                $model->hospital_id = $hospital->getId();
                $model->hospital_name = $hospital->getName();
            }
            $hpdept = $expertTeam->getHpDept();
            if (isset($hpdept)) {
                $model->hp_dept_id = $hpdept->getId();
                $model->hp_dept_name = $hpdept->getName();
            }
        }
        return $model;
    }

    /**
     * 根据医生Id 设置预约的相关信息
     * @param Booking $model
     * @param $doctor_id
     * @return Booking
     */
    private function setDoctorData(Booking $model, $doctor_id) {
        $with = array('doctorHospital', 'doctorHpDept');
        $doctor = Doctor::model()->getById($doctor_id, $with);
        if (isset($doctor)) {
            $model->doctor_name = $doctor->getFullname();
            $hospital = $doctor->getHospital();
            if (isset($hospital)) {
                $model->hospital_id = $hospital->getId();
                $model->hospital_name = $hospital->getName();
            }
            $hpdept = $doctor->getHpDept();
            if (isset($hpdept)) {
                $model->hp_dept_id = $hpdept->getId();
                $model->hp_dept_name = $hpdept->getName();
            }
        }
        return $model;
    }

}
