<?php

require_once dirname(__FILE__) . '/../sdk/vendor/autoload.php';
require_once dirname(__FILE__) . '/../sdk/vendor/config.php';

// 引入鉴权类
use Qiniu\Auth;

class ApiServiceQiniuFileUrl {

    private $accessKey = 'ZazX5SOZGVJdq-yaSGclvATTeRlQb0D6mKPwGra5';
    private $secretKey = 'c8cUyQBTmGOsSEjqjYLOFFYiCu7OO4DI_0EaWYR8';
    private $urlTime = Config::URL_TIME;

    /**
     * 获取图片链接
     * @param type $fileUploadModel
     * @param type $type
     * @param type $actionUrl
     * @return type
     */
    public function getAbsFileUrl($fileUploadModel, $actionUrl = '') {

        $data = new stdClass();
        if ($fileUploadModel->getHasRemote()) {
            $baseUrl = $fileUploadModel->getRemoteDomain() . '/' . $fileUploadModel->getRemoteFileKey(); //. '.' . $fileUploadModel->mime_type;
            $auth = new Auth($this->accessKey, $this->secretKey);
            $absFileUrl = $auth->privateDownloadUrl($baseUrl, $this->urlTime);
            $thumbnailUrl = $absFileUrl . '&imageView2/2/w/90/h/127';
        } else {
            $absFileUrl = Yii::app()->createAbsoluteUrl($actionUrl, array('uid' => $fileUploadModel->getUID(), 'type' => 'absFile'));
            $thumbnailUrl = Yii::app()->createAbsoluteUrl($actionUrl, array('uid' => $fileUploadModel->getUID(), 'type' => 'thumbnail'));
        }
        $data->absFileUrl = $absFileUrl;
        $data->thumbnailUrl = $thumbnailUrl;
        return $data;
    }

}
