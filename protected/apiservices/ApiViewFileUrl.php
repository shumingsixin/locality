<?php

class ApiViewFileUrl extends EApiViewService {

    private $values;
    private $fileMgr;
    private $uploadMgr;
    private $files;

    public function __construct($values) {
        parent::__construct();
        $this->values = $values;
        $this->fileMgr = new FileManager();
        $this->uploadMgr = new FileUploadManager();
        $this->files = array();
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
        $this->loadModelFile();
    }

    private function loadModelFile() {
        $models = $this->fileMgr->getFileUrlList($this->values);
        if (arrayNotEmpty($models)) {
            $this->setFiles($models);
        }
        $this->results->files = $this->files;
    }

    private function setFiles($models) {
        $actionUrl = 'api/appimage';
        foreach ($models as $model) {
            $this->files[] = $this->uploadMgr->getAbsFileUrl($model, $actionUrl);
        }
    }

}
