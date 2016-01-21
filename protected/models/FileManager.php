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
                $userId = $values['userId'];
                $models = BookingFile::model()->getAllByBookingIdAndUserId($bookingId, $userId);
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

}
