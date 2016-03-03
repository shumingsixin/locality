<?php

class FileManager {

    //保存app上传的文件记录
    public function saveAppFile($appFile) {
        $tableName = $appFile['tableName'];
        $model = null;
        switch ($tableName) {
            case "user_doctor_cert"://医生证明
                $model = new UserDoctorCert();
                break;
            case "patient_mr_file"://病人病历--医生端
                $model = new PatientMRFile();
                break;

            case "booking_file"://病人病历 --病人端
                $model = new BookingFile();
                break;
        }
        $model->initAppModel($appFile);
        $model->save();
        return $model;
    }

    //获取各类文件的链接
    public function getFileUrlList($values) {
        $tableName = $values['tableName'];
        $models = array();
        switch ($tableName) {
            case "user_doctor_cert"://医生证明
                $userId = $values['userId'];
                $models = UserDoctorCert::model()->getDoctorFilesByUserId($userId);
                break;
            case "patient_mr_file"://病人病历--医生端
                $patientId = $values['patientId'];
                $creatorId = $values['userId'];
                $reportType = $values['reportType'];
                $models = PatientMRFile::model()->getFilesOfPatientByPatientIdAndCreaterIdAndType($patientId, $creatorId, $reportType);
                break;
            case "booking_file"://病人病历 --病人端
                $bookingId = $values['bookingId'];
                if (isset($values['userId'])) {
                    $userId = $values['userId'];
                    $models = BookingFile::model()->getAllByBookingIdAndUserId($bookingId, $userId);
                } else {
                    $models = BookingFile::model()->getAllByBookingId($bookingId);
                }
                break;
            case "patient_booking"://收到的预约
                $pbId = $values['pbId'];
                $dotorId = $values['userId'];
                $reportType = $values['reportType'];
                $pb = PatientBooking::model()->getByIdAndDoctorId($pbId, $dotorId);
                if (isset($pb)) {
                    $patientId = $pb->patient_id;
                    $models = PatientMRFile::model()->getAllByAttributes(array('patient_id' => $patientId, 'report_type' => $reportType));
                }
                break;
        }
        return $models;
    }

    //本地文件链接获取
    public function getFileUrl($tableName, $uid, $type) {
        $model = null;
        switch ($tableName) {
            case "user_doctor_cert"://医生证明
                $model = UserDoctorCert::model()->getByUID($uid);
                break;
            case "patient_mr_file"://病人病历--医生端
                $model = PatientMRFile::model()->getByUID($uid);
                break;
            case "booking_file"://病人病历 --病人端
                $model = BookingFile::model()->getByUID($uid);
                break;
        }
        $url = '';
        if (isset($model)) {
            $url = $model->getAbsThumbnailUrl();
            if ($type == 'absFile') {
                $url = $model->getAbsFileUrl();
            }
        }
        return $url;
    }

    public function RemoteTask() {
        $this->filesUploadQiniu('user_doctor_cert');
        $this->filesUploadQiniu('patient_mr_file');
        $this->filesUploadQiniu('booking_file');
    }

    //定时任务 文件上传至七牛
    public function filesUploadQiniu($tableName) {
        $models = array();
        $options = array('limit' => 100, 'order' => 't.date_updated DESC');
        switch ($tableName) {
            case "user_doctor_cert"://医生证明
                $models = UserDoctorCert::model()->loadAllHasNotRemote($options);
                break;
            case "patient_mr_file"://病人病历--医生端
                $models = PatientMRFile::model()->loadAllHasNotRemote($options);
                break;
            case "booking_file"://病人病历 --病人端
                $models = BookingFile::model()->loadAllHasNotRemote($options);
                break;
        }
        $uploadMgr = new FileUploadManager();
        $uploadMgr->fileUpdateCloud($models, $tableName);
    }

    //文件删除
    public function deleteFile($values) {
        $tableName = $values['tableName'];
        $id = $values['id'];
        $userId = $values['userId'];
        switch ($tableName) {
            case "user_doctor_cert"://医生证明
                $userMgr = new UserManager();
                $output = $userMgr->delectDoctorCertByIdAndUserId($id, $userId);
                break;
            case "patient_mr_file"://病人病历--医生端
                $userMgr = new UserManager();
                $output = $userMgr->delectPatientMRFileByIdAndCreatorId($id, $userId);
                break;
        }
        return $output;
    }

    public function loadNotAvatar() {
        $data = array();
        $models = Doctor::model()->getAll();
        foreach ($models as $model) {
            if (strIsEmpty($model->avatar_url)) {
                //$data[] = $model->id;
                continue;
            }
            $fileUrl = $model->getAbsUrlAvatar();
            $fileData = @file_get_contents($fileUrl);
            if (strlen($fileData) <= 0) {
                $data[] = $model->id;
            }
        }
        return $data;
    }

    public function updateAvatar() {
        $ids = array("10", "24", "28", "30", "31", "35", "38", "54", "63", "100", "102", "103", "113", "114");
        $qiniuUrl = 'http://7xrgsh.com2.z0.glb.qiniucdn.com/';
        $data = array();
        $models = Doctor::model()->getAll();
        foreach ($models as $model) {
            if (strIsEmpty($model->avatar_url)) {
                continue;
            }
            if (in_array($model->id, $ids)) {
                 continue;
            }
            $filePath = $model->getAbsUrlAvatar();
            $url = $qiniuUrl . substr($filePath, strrpos($filePath, '/') + 1);
            $model->avatar_url = $url;
            $model->update(array('avatar_url'));
            $data[] = $model->id;
        }
        return $data;
    }

}
