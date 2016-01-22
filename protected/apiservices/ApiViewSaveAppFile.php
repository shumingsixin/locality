<?php

class ApiViewSaveAppFile extends EApiViewService {

    private $appFile;
    private $fileMgr;

    public function __construct($appFile) {
        parent::__construct();
        $this->appFile = $appFile;
        $this->fileMgr = new FileManager();
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
        $this->saveData();
    }

    private function saveData() {
        $data = $this->fileMgr->saveAppFile($this->appFile);
        $this->results->modelId = $data->getId();
    }

}
