<?php

class ApiViewUploadToken extends EApiViewService {

    private $tableName;
    private $fileMgr;

    public function __construct($tableName) {
        parent::__construct();
        $this->tableName = $tableName;
        $this->fileMgr = new FileUploadManager();
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
        $this->loadUploadToken();
    }

    public function loadUploadToken() {
        $data = $this->fileMgr->getUploadToken($this->tableName);
        $this->results = $data;
    }

}
