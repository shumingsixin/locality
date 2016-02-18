<?php

class ApiController extends Controller {

    // Members
    /**
     * Key which has to be in HTTP USERNAME and PASSWORD headers 
     */
    Const APPLICATION_ID = 'ASCCPE';
    const TYPE_DOCTOR = 'user_doctor_cert';
    const TYPE_PATIENT = 'patient_mr_file';
    const TYPE_BOOKING = 'booking_file';

    /**
     * Default response format
     * either 'json' or 'xml'
     */
    private $format = 'json';

    /**
     * @return array action filters
     */
    public function filters() {
        return array();
    }

    public function domainWhiteList() {
        return array(
            'http://192.168.31.169',
            'http://md.mingyizd.com',
        );
    }

    public function init() {
        $domainWhiteList = $this->doaminWhiteList();
        $this->setHeaderSafeDomain($domainWhiteList, null);
        header('Access-Control-Allow-Credentials:true');      // 允许携带 用户认证凭据（也就是允许客户端发送的请求携带Cookie）
        return parent::init();
    }

    // Actions
    public function actionList($model) {
        switch ($model) {
            case 'tokendrcert'://获取医生证明上传权限
                $tableName = self::TYPE_DOCTOR;
                $apiService = new ApiViewUploadToken($tableName);
                $output = $apiService->loadApiViewData();
                break;
            case 'tokenpatientmr'://获取病人病历上传权限
                $tableName = self::TYPE_PATIENT;
                $apiService = new ApiViewUploadToken($tableName);
                $output = $apiService->loadApiViewData();
                break;
            case 'tokenbookingmr'://获取预约病历上传权限
                $tableName = self::TYPE_BOOKING;
                $apiService = new ApiViewUploadToken($tableName);
                $output = $apiService->loadApiViewData();
                break;
            case 'loaddrcert'://获取医生证明文件链接 
                $values = $_GET;
                $values['tableName'] = self::TYPE_DOCTOR;
                if (isset($values['userId']) === false) {
                    $user = $this->userLoginRequired($values);
                    $values['userId'] = $user->getId();
                }
                $apiService = new ApiViewFileUrl($values);
                $output = $apiService->loadApiViewData();
                break;
            case 'loadpatientmr'://获取病人病历文件链接
                $values = $_GET;
                $values['tableName'] = self::TYPE_PATIENT;
                if (isset($values['userId']) === false) {
                    $user = $this->userLoginRequired($values);
                    $values['userId'] = $user->getId();
                }
                $apiService = new ApiViewFileUrl($values);
                $output = $apiService->loadApiViewData();
                break;
            case 'loadbookingmr'://获取预约病历链接 
                $values = $_GET;
                $values['tableName'] = self::TYPE_BOOKING;
                if (isset($values['userId']) === false) {
                    $user = $this->userLoginRequired($values);
                    $values['userId'] = $user->getId();
                }
                $apiService = new ApiViewFileUrl($values);
                $output = $apiService->loadApiViewData();
                break;
            case 'imagedrcert'://加载医生证明
                $tableName = self::TYPE_DOCTOR;
                $uid = $_GET['uid'];
                $type = $_GET['type'];
                $fileMgr = new FileManager();
                $url = $fileMgr->getFileUrl($tableName, $uid, $type);
                $this->renderImageOutput($url);
                exit();
                break;
            case 'imagepatientmr'://加载病人病历
                $tableName = self::TYPE_PATIENT;
                $uid = $_GET['uid'];
                $type = $_GET['type'];
                $fileMgr = new FileManager();
                $url = $fileMgr->getFileUrl($tableName, $uid, $type);
                $this->renderImageOutput($url);
                exit();
                break;
            case 'imagebookingmr'://加载预约病历
                $tableName = self::TYPE_BOOKING;
                $uid = $_GET['uid'];
                $type = $_GET['type'];
                $fileMgr = new FileManager();
                $url = $fileMgr->getFileUrl($tableName, $uid, $type);
                $this->renderImageOutput($url);
                exit();
                break;
            case 'qiniudrcert'://定时任务调用接口上传
                $tableName = self::TYPE_DOCTOR;
                $fileMgr = new FileManager();
                $fileMgr->filesUploadQiniu($tableName);
                break;
            case 'qiniupatientmr'://定时任务调用接口
                $tableName = self::TYPE_PATIENT;
                $fileMgr = new FileManager();
                $fileMgr->filesUploadQiniu($tableName);
                break;
            case 'qiniubooking'://定时任务调用接口
                $tableName = self::TYPE_BOOKING;
                $fileMgr = new FileManager();
                $fileMgr->filesUploadQiniu($tableName);
                break;
            case 'deletedrcert'://删除医生证明
                $values = $_GET;
                $values['tableName'] = self::TYPE_DOCTOR;
                if (isset($values['userId']) === false) {
                    $user = $this->userLoginRequired($values);
                    $values['userId'] = $user->getId();
                }
                $fileMgr = new FileManager();
                $output = $fileMgr->deleteFile($values);
                break;
            case 'deletepatientmr'://删除病人病历
                $values = $_GET;
                $values['tableName'] = self::TYPE_PATIENT;
                if (isset($values['userId']) === false) {
                    $user = $this->userLoginRequired($values);
                    $values['userId'] = $user->getId();
                }
                $fileMgr = new FileManager();
                $output = $fileMgr->deleteFile($values);
                break;
            default:
                // Model not implemented error
                //$this->_sendResponse(501, sprintf('Error: Mode <b>list</b> is not implemented for model <b>%s</b>', $model));
                $this->_sendResponse(501, sprintf('Error: Invalid request', $model));
                Yii::app()->end();
        }
        // Did we get some results?
        if (empty($output)) {
            // No
            //$this->_sendResponse(200, sprintf('No items where found for model <b>%s</b>', $model));
            $this->_sendResponse(200, sprintf('No result', $model));
        } else {
            $this->renderJsonOutput($output);
            //  header('Content-Type: text/html; charset=utf-8');
            // var_dump($output);
        }
    }

    public function actionCreate($model) {
        $get = $_GET;
//        $post = $_POST;
        if (empty($_POST)) {
            // application/json
            $post = CJSON::decode($this->getPostData());
        } else {
            // application/x-www-form-urlencoded
            $post = $_POST;
        }
        $api = $this->getApiVersionFromRequest();
        if ($api >= 4) {
            $output = array('status' => EApiViewService::RESPONSE_NO, 'errorCode' => ErrorList::BAD_REQUEST, 'errorMsg' => 'Invalid request.');
        } else {
            $output = array('status' => false, 'error' => 'Invalid request.');
        }

        // var_dump($get);var_dump($post);exit;
        switch ($get['model']) {
            // Get an instance of the respective model
            //@TODO: delete this.
            case 'savedrcert'://保存app上传七牛的各类文件数据
                $appFile = $_POST['appFile'];
                $appFile['tableName'] = self::TYPE_DOCTOR;
                $user = $this->userLoginRequired($appFile);
                $appFile['userId'] = $user->getId();
                $apiService = new ApiViewSaveAppFile($appFile);
                $output = $apiService->loadApiViewData();
                break;
            case 'savepatientmr'://保存app上传七牛的各类文件数据
                $appFile = $_POST['appFile'];
                $appFile['tableName'] = self::TYPE_PATIENT;
                $user = $this->userLoginRequired($appFile);
                $appFile['userId'] = $user->getId();
                $apiService = new ApiViewSaveAppFile($appFile);
                $output = $apiService->loadApiViewData();
                break;
            case 'savebookingmr'://保存app上传七牛的各类文件数据
                $appFile = $_POST['appFile'];
                $appFile['tableName'] = self::TYPE_BOOKING;
                $user = $this->userLoginRequired($appFile);
                $appFile['userId'] = $user->getId();
                $apiService = new ApiViewSaveAppFile($appFile);
                $output = $apiService->loadApiViewData();
                break;
            case 'uploaddoctorcert'://保存md上传的医生证明
                if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
                    Yii::app()->end(200, true); // finish preflight CORS requests here
                }
                $values = $_POST['doctor'];
                if (isset($values['id']) === false) {
                    $user = $this->userLoginRequired($values);
                    $values['id'] = $user->getId();
                }
                $apiService = new ApiViewDoctorCert($values);
                $output = $apiService->loadApiViewData();
                // android 插件
                if (isset($_POST['plugin'])) {
                    echo CJSON::encode($output);
                    Yii::app()->end(200, true); //结束 返回200
                }
                break;
            case 'uploadparientmr'://病人病历
                if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
                    Yii::app()->end(200, true); // finish preflight CORS requests here
                }
                $values = $_POST['patient'];
                $apiService = new ApiViewPatientMr($values);
                $output = $apiService->loadApiViewData();
                if (isset($_POST['plugin'])) {
                    echo CJSON::encode($output);
                    Yii::app()->end(200, true); //结束 返回200
                }
                break;
            case 'uploadbookingfile'://预约的病历
                if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
                    Yii::app()->end(200, true); // finish preflight CORS requests here
                }
                $values = $_POST['booking'];
                $apiService = new ApiViewBookingFile($values);
                $output = $apiService->loadApiViewData();
                if (isset($_POST['plugin'])) {
                    echo CJSON::encode($output);
                    Yii::app()->end(200, true); //结束 返回200
                }
                break;
            default:
                $this->_sendResponse(501, sprintf('Error: Invalid request', $model));
                Yii::app()->end();
        }
        $this->renderJsonOutput($output);
    }

    private function userLoginRequired($values) {
        if (isset($values['username']) === false || isset($values['token']) === false) {
            if ($this->getApiVersionFromRequest() >= 4) {
                $this->renderJsonOutput(array('status' => EApiViewService::RESPONSE_NO, 'errorCode' => ErrorList::BAD_REQUEST, 'errorMsg' => '没有权限执行此操作'));
            } else {
                $this->_sendResponse(ErrorList::UNAUTHORIZED, '没有权限执行此操作', 'application/json; charset=utf-8');
            }
        }
        $username = $values['username'];
        $token = $values['token'];
        $tableName = $values['tableName'];
        $authMgr = new AuthManager();
        if ($tableName == 'booking_file') {
            $authUserIdentity = $authMgr->authenticateUserByToken($username, $token);
        } else {
            $authUserIdentity = $authMgr->authenticateDoctorByToken($username, $token);
        }

        if (is_null($authUserIdentity) || $authUserIdentity->isAuthenticated === false) {
            if ($this->getApiVersionFromRequest() >= 4) {
                $this->renderJsonOutput(array('status' => EApiViewService::RESPONSE_NO, 'errorCode' => ErrorList::BAD_REQUEST, 'errorMsg' => '用户名或token不正确'));
            } else {
                $this->_sendResponse(ErrorList::UNAUTHORIZED, '用户名或token不正确', 'application/json; charset=utf-8');
            }
        }
        return $authUserIdentity->getUser();
    }

    private function _sendResponse($status = 200, $body = '', $content_type = 'text/html') {
        // set the status
        $status_header = 'HTTP/1.1 ' . $status . ' ' . $this->_getStatusCodeMessage($status);
        header($status_header);
        // and the content type
        header('Content-type: ' . $content_type);

        // pages with body are easy
        if ($body != '') {
            // send the body
            echo $body;
        }
        // we need to create the body if none is passed
        else {
            // create some body messages
            $message = '';

            // this is purely optional, but makes the pages a little nicer to read
            // for your users.  Since you won't likely send a lot of different status codes,
            // this also shouldn't be too ponderous to maintain
            switch ($status) {
                case 401:
                    $message = 'You must be authorized to view this page.';
                    break;
                case 404:
                    $message = 'The requested URL ' . $_SERVER['REQUEST_URI'] . ' was not found.';
                    break;
                case 500:
                    $message = 'The server encountered an error processing your request.';
                    break;
                case 501:
                    $message = 'The requested method is not implemented.';
                    break;
            }

            // servers don't always have a signature turned on 
            // (this is an apache directive "ServerSignature On")
            $signature = ($_SERVER['SERVER_SIGNATURE'] == '') ? $_SERVER['SERVER_SOFTWARE'] . ' Server at ' . $_SERVER['SERVER_NAME'] . ' Port ' . $_SERVER['SERVER_PORT'] : $_SERVER['SERVER_SIGNATURE'];

            // this should be templated in a real-world solution
            $body = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
            <html>
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
                    <title>' . $status . ' ' . $this->_getStatusCodeMessage($status) . '</title>
                </head>
                <body>
                    <h1>' . $this->_getStatusCodeMessage($status) . '</h1>
                    <p>' . $message . '</p>
                    <hr />
                    <address>' . $signature . '</address>
                </body>
            </html>';

            echo $body;
        }
        Yii::app()->end();
    }

    private function _getStatusCodeMessage($status) {
        // these could be stored in a .ini file and loaded
        // via parse_ini_file()... however, this will suffice
        // for an example
        $codes = Array(
            200 => 'OK',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
        );
        return (isset($codes[$status])) ? $codes[$status] : '';
    }

    private function _checkAuth() {
        // Check if we have the USERNAME and PASSWORD HTTP headers set?
        if (!(isset($_SERVER['HTTP_X_USERNAME']) and isset($_SERVER['HTTP_X_PASSWORD']))) {
            // Error: Unauthorized
            $this->_sendResponse(401);
        }
        $username = $_SERVER['HTTP_X_USERNAME'];
        $password = $_SERVER['HTTP_X_PASSWORD'];
        // Find the user
        $user = User::model()->find('LOWER(username)=?', array(strtolower($username)));
        if ($user === null) {
            // Error: Unauthorized
            $this->_sendResponse(401, 'Error: User Name is invalid');
        } else if (!$user->validatePassword($password)) {
            // Error: Unauthorized
            $this->_sendResponse(401, 'Error: User Password is invalid');
        }
    }

    private function loadOverseasHospitalJson() {
        $overseasController = new OverseasController();


        $hospitals = array(
            array(
                'id' => 1,
                'name' => '新加坡伊丽莎白医院',
                'url' => '',
                'urlImage' => 'http://mingyihz.oss-cn-hangzhou.aliyuncs.com/static%2Foverseas_sg_elizabeth.jpg'
            ),
            array(
                'id' => 2,
                'name' => '新加坡邱德拔医院',
                'url' => '',
                'urlImage' => 'http://mingyihz.oss-cn-hangzhou.aliyuncs.com/static%2Foverseas_sg_ktph.jpg'
            ),
            array(
                'id' => 3,
                'name' => '新加坡中央医院',
                'url' => '',
                'urlImage' => 'http://mingyihz.oss-cn-hangzhou.aliyuncs.com/static%2Foverseas_sg_sgh.jpg'
            ),
            array(
                'id' => 4,
                'name' => '新加坡国立大学医院',
                'url' => '',
                'urlImage' => 'http://mingyihz.oss-cn-hangzhou.aliyuncs.com/static%2Foverseas_sg_nuh.jpg',
            )
        );
        $output = array('hospitals' => array());
        foreach ($hospitals as $hospital) {
            $obj = new stdClass();
            foreach ($hospital as $key => $value) {
                $obj->{$key} = $value;
                $output['hospitals'][] = $obj;
            }
        }

        return $output;
    }

    private function parseQueryOptions($values) {
        $options = array();
        if (isset($values['offset']))
            $options['offset'] = $values['offset'];
        if (isset($values['limit']))
            $options['limit'] = $values['limit'];
        if (isset($values['order']))
            $options['order'] = $values['order'];
        return $options;
    }

    private function getApiVersionFromRequest() {
        return Yii::app()->request->getParam("api", 1);
    }

}
