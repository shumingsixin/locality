<?php

class ApiViewDoctorCert extends EApiViewService {

    private $values;
    private $userMgr;

    public function __construct($values) {
        parent::__construct();
        $this->values = $values;
        $this->userMgr = new UserManager();
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
        $this->saveDoctorCert();
    }

    private function saveDoctorCert() {
        $this->results->file = null;
        if (isset($this->values['id']) === false) {
            $this->output['status'] = 'no';
            $this->output['errorMsg'] = 'invalid parameters';
            $this->results->file = null;
        } else {
            $userId = $this->values['id'];
            $ret = $this->userMgr->createUserDoctorCert($userId);
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

    private function setFile($fileModel) {
        $data = new stdClass();
        $data->id = $fileModel->getId();
        $data->userId = $fileModel->getUserId();
        $data->fileUrl = $fileModel->getAbsFileUrl();
        $data->tnUrl = $fileModel->getAbsThumbnailUrl();
        $this->results->file = $data;
    }

}
