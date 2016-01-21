<?php

class MedicalrecordController extends WebsiteController {

    private $model = null;  //MedicalRecord
    public $page_list;
    public $current_page;

    public function filterMedicalRecordContext($filterChain) {
        $user = $this->loadUser();

        $mrId = null;
        if (isset($_GET['id'])) {
            $mrId = $_GET['id'];
        } else if (isset($_POST['id'])) {
            $mrId = $_POST['id'];
        }

        $this->loadModelByIdAndUserId($mrId, $user->getId());

        //complete the running of other filters and execute the requested action.
        $filterChain->run();
    }

    /**
     * @return array action filters
     */
    public function filters() {
        return array(
            'accessControl', // perform access control for CRUD operations
            'postOnly + delete deleteFile ajaxUploadFile', // we only allow deletion via POST request
            'userContext + create index',
            //'medicalRecordContext + view create2 ajaxUploadFile ajaxLoadFiles deleteFile ajaxUpdate ajaxUpdateFileMeta',
            'medicalRecordContext + view create2 ajaxLoadFiles deleteFile ajaxUpdate ajaxUpdateFileMeta',
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
                'actions' => array('ajaxUploadFile'),   //TODO: remove ajaxUploadFile.
                'users' => array('*'),
            ),
            array('allow', // allow authenticated user to perform 'create' and 'update' actions
                'actions' => array('index', 'view', 'create', 'create2', 'ajaxLoadFiles', 'ajaxUpdate', 'delete', 'deleteFile', 'ajaxUpdateFileMeta'),
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
        $model = $this->loadModel($id);
        $form = new MedicalRecordForm();
        $form->initModel($model);
        //$model = $this->model;
        //  $recordFiles = $model->getMedicalRecordFiles();
        //  $outputlist = $this->createMRFileOutputList($recordFiles);
        $outputlist = array();

        $this->render('view', array(
            'model' => $model,
            'form' => $form,
            'outputlist' => $outputlist
        ));
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate() {
        $userId = $this->getCurrentUserId();
        $model = null;

        $form = new MedicalRecordForm();
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
        } else if (isset($_POST['MedicalRecordForm']['id'])) {
            $id = $_POST['MedicalRecordForm']['id'];
        }
        //$id = $this->getFlashMessage('mr.step.id'); // get MedicalRecord.id from session, if there is any.
        if (isset($id)) {
            $model = $this->loadModelByIdAndUserId($id, $userId);   // load MedicalRecord from session.
        }

        if (isset($model)) {
            $form->initModel($model);   // assign model attributes to $form.
        } else {
            $form->setUserId($userId);  // create new record.
            $form->initModel(); // init new $form attributes.
        }

        // Uncomment the following line if AJAX validation is needed
        $this->performAjaxValidation($form);

        if (isset($_POST['MedicalRecordForm'])) {
            $values = $_POST['MedicalRecordForm'];
            $form->attributes = $values;
            $mrMgr = new MedicalRecordManager();

            //  $this->headerUTF8();
            if ($form->isNewRecord) {
                $mrMgr->createMedicalRecord($form);
            } else {
                $mrMgr->updateMedicalRecord($form, $model);
            }

            if ($form->hasErrors() === false) {
                $this->redirect(array('medicalrecord/create2', 'id' => $form->id));
            }
            $form->loadOptions();
        }

        $this->render('create', array(
            'form' => $form,
        ));
    }

    /**
     *
     * @param type $id MedicalRecord.id
     */
    public function actionCreate2($id) {
        $model = $this->model;

        if (isset($_POST['MedicalRecord'])) {
            $this->redirect(array('view', 'id' => $model->getId()));
        }

        $this->render('create2', array(
            'model' => $model,
                )
        );
    }

    /**
     * $_POST input sample:
     * array (size=1)
      'filemetas' =>
      array (size=2)
      13 =>
      array (size=3)
      'fid' => int 13
      'dateTaken' => string '2015-02-03'
      'desc' => string 'some description'
      5 =>
      array (size=2)
      'fid' => int 5
      'dateTaken' => string '2015-05-03'
     */
    public function actionAjaxUpdateFileMeta() {
        $output = array();
        $urlNextStep = null;
        if (isset($_POST['next_step']) && $_POST['next_step'] == 3 && isset($_POST['id'])) {
            $urlNextStep = $this->createUrl('mrbooking/create', array('mrid' => $_POST['id']));
        }
        if (isset($_POST['MRFile'])) {

            $values = $_POST['MRFile'];
            $model = $this->model;
            $mrMgr = new MedicalRecordManager();
            $output = $mrMgr->updateMRFileMeta2($model->getId(), $values);
            if (count($output) === 0) {
                $output['status'] = true;
                if ($urlNextStep !== null) {
                    $output['urlNext'] = $urlNextStep;
                }
            } else {
                $output['status'] = false;
            }
            $this->renderJsonOutput($output);
        } else if ($urlNextStep !== null) {
            $output['status'] = true;
            $output['urlNext'] = $urlNextStep;
            $this->renderJsonOutput($output);
        } else {
            $this->throwPageNotFoundException();
        }
    }

    /**
     * @request type post
     * @param MedicalRecord[id] MedicalRecord.id
     * @param MedicalRecordFile[doc_type] MedicalRecordFile.doc_type
     */
    public function actionAjaxUploadFile() {

        $output = array();
        if (isset($_POST['MRFile']['mrid']) && isset($_POST['MRFile']['report_type'])) {
            $mrid = $_POST['MRFile']['mrid'];
            $rt = $_POST['MRFile']['report_type'];
            
            //$userid = $this->getCurrentUserId();    //TODO: <= uncomment this line.            
            if (isset($_POST['MRFile']['user_id'])) {
                $userid = $_POST['MRFile']['user_id'];
            }else{
                //TODO: remove this line.
                $userid = $this->getCurrentUserId(); 
            }
            $mrMgr = new MedicalRecordManager();
            $recordFiles = $mrMgr->createMRFiles($mrid, $rt, $userid);
            if ($this->isAjaxRequest()) {
                if (emptyArray($recordFiles) === false) {
                    $files = array();
                    foreach ($recordFiles as $recordFile) {
                        $files[] = $this->createMRFileOutput($recordFile);                        
                    }
                    $output['files'] = $files;
                }
            }
        }
        $this->renderJsonOutput($output);
        Yii::app()->end();
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionAjaxUpdate() {

        if (isset($_POST['MedicalRecordForm']) && isset($_POST['id'])) {
            $model = $this->loadModel($_POST['id']);
            $values = $_POST['MedicalRecordForm'];
            $form = new MedicalRecordForm();
            $form->initModel($model);
            $form->scenario = 'updateDiseaseInfo';
            $form->attributes = $values;

            $mrMgr = new MedicalRecordManager();
            $success = $mrMgr->updateMedicalRecord($form, $model);
            $output = array();
            if ($success) {
                // success.
                $output['status'] = 'true';

                echo CJSON::encode($output);

                Yii::app()->end();
            } else {
                // error message.
                $error = CActiveForm::validate($form);
                if ($error != '[]') {
                    echo $error;
                }
                Yii::app()->end();
            }
        } else {
            $this->throwPageNotFoundException();
        }
    }

    /**
     * @request: post.
     * @param MedicalRecord[id]
     * @param MedicalRecordFile[id]
     */
    public function actionDeleteFile() {

        $output = array();
        if (isset($_POST['id']) && isset($_POST['fid'])) {
            $model = $this->model;
            $recordFileId = $_POST['fid'];

            $success = false;
            $recordFile = MedicalRecordFile::model()->getById($recordFileId);
            if (isset($recordFile)) {
                if ($this->model->id == $recordFile->mr_id) {
                    $mrMgr = new MedicalRecordManager();
                    $success = $mrMgr->deleteMRFile($recordFile);
                } else {
                    $output['status'] = 'Invalid request - Code 2.';
                }
            } else {
                $output['status'] = 'Invalid request - Code 1.';
            }
            if (isset($_POST['returnUrl'])) {
                $this->redirect($_POST['returnUrl']);
            } else if ($success) {
                $output['status'] = 'true';
            }
        }
        $this->renderJsonOutput($output);
    }

    /**
     * Deletes a particular model.
     * If deletion is successful, the browser will be redirected to the 'admin' page.
     * @param integer $id the ID of the model to be deleted
     */
    public function actionDelete($id) {
        $this->loadModel($id)->delete();

        // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
        if (!isset($_GET['ajax']))
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
    }

    /**
     * Lists all models.
     */
    public function actionIndex() {
        $this->current_page = 'index';
        $user = $this->loadUser();
        //$mr = MedicalRecord::model()->with('mrBookings')->findAllByAttributes(array('user_id'=>$user->getId()));
        //var_dump($mr);
        // $mr2 = $user->userMedicalRecords(array('with' => 'mrBookings'));


        $criteria = $user->createCriteriaMedicalRecords();
        $dataProvider = new CActiveDataProvider('MedicalRecord', array(
            'criteria' => $criteria,
            'pagination' => array(
                'pageSize' => 20,
            ),
        ));
        $this->render('index', array(
            'dataProvider' => $dataProvider,
        ));
    }

    /**
     *
     * @param type $id MedicalRecord.id
     * @param type $doctype MedicalRecord.doc_type
     */
    public function actionAjaxLoadFiles($id, $rt) {
        $model = $this->model;

        $files = $model->getAllFilesByReportType($rt);

        $output = array();
        $data = array();
        if (emptyArray($files) === false) {
            foreach ($files as $file) {
                //$data[] = $this->createTripImageOutput($file, $record->getTitle());
                $data[] = $this->createMRFileOutput($file);
            }
        }
        if (isset($_GET['tmpl']) && $_GET['tmpl'] == 1) {
            //using tmpl template, so add 'files'.
            $output['files'] = $data;
        } else {
            $output = $data;
        }

        $this->renderJsonOutput($output);
        Yii::app()->end();
    }

    /**
     * returns an output array for json encoding.
     * @param MedicalRecordFile $recordFile
     * @return type array
     * id => MedicalRecordFile.id
     * mrId => MedicalRecord.id
     * fileUrl => abs url of file.
     * thumbnailUrl => abs url of thumbnail of file.
     * fileDate => MedicalRecordFile.date_taken.
     * fileDesc => MedicalRecordFile.description.
     * deleteUrl => abs url to delete file.
     * deleteType => post.
     */
    public function createMRFileOutput(MedicalRecordFile $model) {
        $output = array(
            'id' => $model->getId(),
            'mrId' => $model->getMrId(),
            'fileUrl' => $model->getAbsFileUrl(),
            'thumbnailUrl' => $model->getAbsThumbnailUrl(),
            'deleteUrl' => $this->createUrl('medicalRecord/deleteFile'),
            'deleteType' => 'post',
            'fileDate' => $model->getDateTaken(),
            'fileDesc' => $model->getDescription()
        );
        return $output;
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return MedicalRecord the loaded model
     * @throws CHttpException
     */
    public function loadModel($id) {
        if (is_null($this->model)) {
            $this->model = MedicalRecord::model()->getById($id);
            if (is_null($this->model)) {
                throw new CHttpException(404, 'The requested page does not exist.');
            }
        }

        return $this->model;
    }

    public function loadModelByIdAndUserId($id, $userId, $with = null) {
        if (is_null($this->model)) {
            $this->model = MedicalRecord::model()->getByAttributes(array('id' => $id, 'user_id' => $userId), $with);
            if (is_null($this->model)) {
                throw new CHttpException(404, 'The requested page does not exist.');
            }
        }
        return $this->model;
    }

    public function loadMRFileByIdAndMrId($id, $mrid, $with = null) {
        return MedicalRecordFile::model()->getByAttributes(array('id' => $id, 'mr_id' => $mrid), $with);
    }

    public function loadPageMenu() {
        $list = $this->getPageList();
        $menu = array();
        foreach ($list as $key => $value) {
            $menuItem = array(
                'label' => $value,
                'key' => $key,
                'link' => $this->createUrl($key),
                'active' => ''
            );
            if ($key == $this->current_page) {
                $menuItem['active'] = 'active';
            }
            $menu[] = $menuItem;
        }
        return $menu;
    }

    public function getPageList() {
        if ($this->page_list === null) {
            $this->page_list = array('index' => '所有病例', 'create' => '创建新病例');
        }
        return $this->page_list;
    }

    /**
     * Performs the AJAX validation.
     * @param MedicalRecord $model the model to be validated
     */
    protected function performAjaxValidation($model) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'mr-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

}
