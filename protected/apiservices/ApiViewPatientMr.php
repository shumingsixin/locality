<?php

class ApiViewPatientMr extends EApiViewService {

    private $values;
    private $patientMgr;

    public function __construct($values) {
        parent::__construct();
        $this->values = $values;
        $this->patientMgr = new PatientManager();
    }

    protected function createOutput() {
        if (is_null($this->output)) {
            $this->output = array(
                'status' => self::RESPONSE_OK,
                'errorCode' => 0,
                'errorMsg' => 'success',
                'results' => $this->results,
            );
        }
    }

    protected function loadData() {
        $this->savePatientMr();
    }

    private function savePatientMr() {
        if (isset($this->values['id']) === false) {
            $this->output['status'] = 'no';
            $this->output['errorMsg'] = 'invalid parameters';
            $this->results->file = null;
        } else {
            $id = $this->values['id'];
            $patientInfo = $this->patientMgr->loadPatientInfoById($id);
            if (isset($patientInfo) === false) {
                // PatientInfo record is not found in db.
                $this->output['status'] = 'no';
                $this->output['errorMsg'] = 'invalid parameters';
                $this->results->file = null;
            } else {
                $reportType = isset($this->values['report_type']) ? $this->values['report_type'] : StatCode::MR_REPORTTYPE_MR;
                $ret = $this->patientMgr->createPatientMRFile($patientInfo, $reportType);
                if (isset($ret['error'])) {
                    $this->output['status'] = 'no';
                    $this->output['errorMsg'] = 'file have errors';
                    $this->results->file = null;
                } else {
                    $fileModel = $ret['filemodel'];
                    $this->setFile($fileModel);
                }
            }
        }
    }

    private function setFile($fileModel) {
        $data = new stdClass();
        $data->id = $fileModel->getId();
        $data->patientId = $fileModel->getPatientId();
        $data->fileUrl = $fileModel->getAbsFileUrl();
        $data->tnUrl = $fileModel->getAbsThumbnailUrl();
        $this->results->file = $data;
    }

}
