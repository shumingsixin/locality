<?php

class PatientManager {

    //保存app上传的文件记录
    public function saveAppFile($appFile) {
        $output = array(
            'status' => 'no',
            'errorCode' => 1,
            'errorMsg' => null,
            'results' => null,
        );
        $mrFile = new PatientMRFile();
        $mrFile->initAppModel($appFile);
        if ($mrFile->save()) {
            $output['status'] = 'ok';
            $output['errorCode'] = 0;
            $output['errorMsg'] = 'success';
            $output['results'] = $mrFile;
        } else {
            $output['errorMsg'] = $mrFile->getErrors();
        }
        return $output;
    }

    public function loadPatientInfoById($id) {
        return PatientInfo::model()->getById($id);
    }

    public function loadPatientMRById($id, $attributes = '*', $with = null) {
        if (is_null($attributes)) {
            $attributes = '*';
        }
        $criteria = new CDbCriteria();
        $criteria->select = $attributes;
        $criteria->addCondition('t.date_deleted is NULL');
        $criteria->compare('t.id', $id);
        if (is_array($with)) {
            $criteria->with = $with;
        }
        return PatientMR::model()->find($criteria);
    }

    //查询所有患者信息总数
    public function loadPatientCount($creator_id) {
        $criteria = new CDbCriteria();
        $criteria->compare('t.creator_id', $creator_id);
        $criteria->addCondition('t.date_deleted is NULL');
        return PatientInfo::model()->count($criteria);
        ;
    }

    //查询该创建者所有预约患者的总数
    public function loadPatientBookingNumberByCreatorId($creator_id) {
        $criteria = new CDbCriteria();
        $criteria->compare('t.creator_id', $creator_id);
        $criteria->addCondition('t.date_deleted is NULL');
        return PatientBooking::model()->count($criteria);
    }

    //查询该医生所有的预约患者总数
    public function loadPatientBookingNumberByDoctorId($doctor_id) {
        $criteria = new CDbCriteria();
        $criteria->compare('t.doctor_id', $doctor_id);
        $criteria->addCondition('t.date_deleted is NULL');
        return PatientBooking::model()->count($criteria);
    }

    //加载mr信息
    public function loadPatientMRByPatientId($patientId, $attributes = null, $with = null) {
        return PatientMR::model()->getByPatientId($patientId, $attributes, $with);
    }

    //加载病人文件信息 
    public function loadPatientMRFilesByPatientId($patientId, $attributes = null, $with = null) {
        return PatientMRFile::model()->getAllByPatientId($patientId, $attributes, $with);
    }

    public function loadPatientBookingById($bookingId, $attributes = null, $with = null) {
        return PatientBooking::model()->getById($bookingId, $with);
    }

    //根据patientid加载booking
    public function loadPatientBookingByPatientId($patientId, $attributes = null, $with = null) {
        return PatientBooking::model()->getByPatientId($patientId, $attributes, $with);
    }

    //查询预约该医生的患者列表
    public function loadPatientBookingListByDoctorId($doctorId, $attributes = '*', $with = null, $options = null) {
        return PatientBooking::model()->getAllByDoctorId($doctorId, $with = null, $options = null);
    }

    //查询预约该医生的患者详细信息
    public function loadPatientBookingByIdAndDoctorId($id, $doctorId, $attributes = '*', $with = null) {
        return PatientBooking::model()->getByIdAndDoctorId($id, $doctorId, $with);
    }

    public function createPatientMRFile(PatientInfo $patientInfo, $reportType) {
        $patientId = $patientInfo->getId();
        $creatorId = $patientInfo->getCreatorId();
        $uploadField = PatientMRFile::model()->file_upload_field;
        $file = EUploadedFile::getInstanceByName($uploadField);
        if (isset($file)) {
            //文件储存
            $output['filemodel'] = $this->savePatientMRFile($patientId, $creatorId, $reportType, $file);
        } else {
            $output['error'] = 'missing uploaded file in - ' . $uploadField;
        }
        return $output;
    }

    public function createPatientMRFiles(PatientInfo $patientInfo, $reportType) {
        $patientId = $patientInfo->getId();
        $creatorId = $patientInfo->getCreatorId();
        $uploadField = PatientMRFile::model()->file_upload_field;
        $files = EUploadedFile::getInstancesByName($uploadField);
        if (isset($files)) {
            //文件储存
            $data = array();
            foreach ($files as $file) {
                $data[] = $this->savePatientMRFile($patientId, $creatorId, $reportType, $file);
            }
            $output['filemodel'] = $data;
            // var_dump($data);                exit();
        } else {
            $output['error'] = 'missing uploaded file in - ' . $uploadField;
        }

        return $output;
    }

    //查询创建者预约列表
    public function loadAllPatientBookingByCreatorId($creatorId, $attributes = null, $with = null, $options = null) {
        if (is_null($attributes)) {
            $attributes = '*';
        }
        return PatientBooking::model()->getAllByCreatorId($creatorId, $attributes, $with, $options);
    }

    //查询创建者的预约详情
    public function loadPatientBookingByIdAndCreatorId($id, $creatorId, $attributes = null, $with = null, $options = null) {
        if (is_null($attributes)) {
            $attributes = '*';
        }
        return PatientBooking::model()->getByIdAndCreatorId($id, $creatorId, $with);
    }

    //查询患者的病历/出院小结图片/
    public function loadFilesOfPatientByPatientIdAndCreaterIdAndType($patientId, $creatorId, $type, $attributes = null, $with = null, $options = null) {
        if (is_null($attributes)) {
            $attributes = '*';
        }

        return PatientMRFile::model()->getFilesOfPatientByPatientIdAndCreaterIdAndType($patientId, $creatorId, $type, $attributes, $with);
    }

    //查询患者列表
    public function loadPatientInfoListByCreateorId($creatorId, $attributes, $with = null, $options = null) {
        if (is_null($attributes)) {
            $attributes = '*';
        }
        return PatientInfo::model()->getAllByCreateorId($creatorId, $attributes, $with, $options);
    }

    //患者详情查询
    public function loadPatientInfoByIdAndCreateorId($id, $creatorId, $attributes, $with = null, $options = null) {
        if (is_null($attributes)) {
            $attributes = '*';
        }
        return PatientInfo::model()->getByIdAndCreatorId($id, $creatorId, $attributes, $with, $options);
    }

    /**
     * Get EUploadedFile from $_FILE. 
     * Create DoctorCert model. 
     * Save file in filesystem. 
     * Save model in db.
     * @param EUploadedFile $file EUploadedFile::getInstances()
     * @param integer $doctorId Doctor.id     
     * @return DoctorCert 
     */
    private function savePatientMRFile($patientId, $creatorId, $reportType, $file) {
        $pFile = new PatientMRFile();
        $pFile->initModel($patientId, $creatorId, $reportType, $file);
        //文件保存于本地返回model存于数据库
        $pFile->saveModel();

        return $pFile;
    }

    /**
     * api 创建或修改(id设值)患者基本信息
     * @param User $user
     * @param $values
     * @param null $id
     * @return mixed
     */
    public function apiCreatePatientInfo(User $user, $values, $id = null) {
        // create a new model and save into db.
        $userId = $user->getId();
        if (isset($id)) {
            $model = PatientInfo::model()->getByIdAndCreatorId($id, $userId);
            if (is_null($model)) {
                $output['status'] = EApiViewService::RESPONSE_NO;
                $output['errorCode'] = ErrorList::UNAUTHORIZED;
                $output['errorMsg'] = '您没有权限执行此操作';
                return $output;
            }
        } else {
            $model = new PatientInfo();
        }

        $model->setAttributes($values);
        $model->creator_id = $userId;
        $model->country_id = 1;
        list($birth_year, $birth_month) = explode('-', $values['birth']);
        $model->birth_year = $birth_year;
        $model->birth_month = (int) $birth_month;
        $model->setAge();

        //给省会名 城市名赋值
        $regionState = RegionState::model()->getById($model->state_id);
        $model->state_name = $regionState->getName();
        $regionCity = RegionCity::model()->getById($model->city_id);
        $model->city_name = $regionCity->getName();

        if ($model->save()) {
            $output['status'] = EApiViewService::RESPONSE_OK;
            $output['errorCode'] = ErrorList::ERROR_NONE;
            $output['errorMsg'] = 'success';
            $output['results'] = array(
                'id' => $model->getId(),
                'actionUrl' => Yii::app()->createAbsoluteUrl('/apimd/patientfile'),
            );
        } else {
            $output['status'] = EApiViewService::RESPONSE_NO;
            $output['errorCode'] = ErrorList::UNAUTHORIZED;
            $output['errorMsg'] = $model->getErrors();
        }

        return $output;
    }

    /**
     * api 上传患者病历图片
     * @param User $user
     * @param $values
     * @param $file
     * @return array
     */
    public function apiCreatePatientFile(User $user, $values, $file) {
        if (is_null($file)) {
            $output['status'] = EApiViewService::RESPONSE_NO;
            $output['errorCode'] = ErrorList::BAD_REQUEST;
            $output['errorMsg'] = '请上传图片';
            return $output;
        }
        // validates input parameters.
        if (isset($values['patient_id']) === false) {
            $output['status'] = EApiViewService::RESPONSE_NO;
            $output['errorCode'] = ErrorList::BAD_REQUEST;
            $output['errorMsg'] = '参数错误';
            return $output;
        }
        $patientId = $values['patient_id'];
        $userId = $user->getId();

        // TODO: load $patient from db by $patientId.
        $patient = PatientInfo::model()->getByIdAndCreatorId($patientId, $userId);
        if (is_null($patient)) {
            $output['status'] = EApiViewService::RESPONSE_NO;
            $output['errorCode'] = ErrorList::UNAUTHORIZED;
            $output['errorMsg'] = '您没有权限执行此操作';
            return $output;
        }
        // create PatientFile and save into db.
        $reportType = isset($values['report_type']) ? $values['report_type'] : StatCode::MR_REPORTTYPE_MR;
        $patientFile = $this->savePatientMRFile($patientId, $userId, $reportType, $file);
        if ($patientFile->hasErrors()) {
            $output['status'] = EApiViewService::RESPONSE_NO;
            $output['errorCode'] = ErrorList::BAD_REQUEST;
            $output['errorMsg'] = $patientFile->getFirstErrors();
            return $output;
        }
        return array(
            'status' => EApiViewService::RESPONSE_OK,
            'errorCode' => ErrorList::ERROR_NONE,
            'errorMsg' => 'success',
            'results' => '',
        );
    }

    /**
     * api 创建患者预约
     * @param User $user
     * @param $values
     * @return mixed
     */
    public function apiCreatePatientBooking(User $user, $values) {
        // validates input parameters.
        if (isset($values['patient_id']) === false) {
            $output['status'] = EApiViewService::RESPONSE_NO;
            $output['errorCode'] = ErrorList::BAD_REQUEST;
            $output['errorMsg'] = '参数错误';
            return $output;
        }
        $patientId = $values['patient_id'];
        $userId = $user->getId();

        // TODO: load $patient from db by $patientId.
        $patient = PatientInfo::model()->getByIdAndCreatorId($patientId, $userId);
        if (is_null($patient)) {
            $output['status'] = EApiViewService::RESPONSE_NO;
            $output['errorCode'] = ErrorList::UNAUTHORIZED;
            $output['errorMsg'] = '您还未创建此患者';
            return $output;
        }
        $model = new PatientBooking();
        $model->setAttributes($values);
        $model->status = StatCode::BK_STATUS_NEW;
        $model->creator_id = $userId;
        $model->patient_id = $patientId;

        if ($model->save()) {

            //预约单保存成功  生成一张支付单
            $orderMgr = new OrderManager();
            $salesOrder = $orderMgr->createSalesOrder($model);
            if ($salesOrder->save()) {
                //发送提示短信
                $this->sendSmsToCreator($user, $model);
            }

            $output['status'] = EApiViewService::RESPONSE_OK;
            $output['errorCode'] = ErrorList::ERROR_NONE;
            $output['errorMsg'] = 'success';
            $output['results'] = array('bookingId' => $model->getId(), 'refNo' => $salesOrder->getRefNo());
        } else {
            $output['status'] = EApiViewService::RESPONSE_NO;
            $output['errorCode'] = ErrorList::UNAUTHORIZED;
            $output['errorMsg'] = $model->getErrors();
        }

        return $output;
    }

    public function sendSmsToCreator(User $user, $patientBooking) {
        $mobile = $user->getUsername();
        $smsMgr = new SmsManager();
        $data = new stdClass();
        $data->refno = $patientBooking->getRefNo();
        $doctor = $patientBooking->getDoctor();
        $data->expertBooked = isset($doctor) ? $doctor->name : '';
        //发送提示的信息
        $smsMgr->sendSmsBookingSubmit($mobile, $data);
    }

}
