<?php

/* Change apache\bin\php.ini settings to allow larger files.
 * post_max_size = 16m
 * upload_max_filesize = 16M
 */

/**
 * This is the model class for base file model.
 *
 * @property integer $id
 * @property string $uid      
 * @property string $file_ext
 * @property string $mime_type 
 * @property string $file_url
 * @property integer $file_size
 * @property string $thumbnail_name
 * @property string $thumbnail_url 
 * @property string $base_url
 * @property integer $has_remote
 * @property string remote_domain
 * @property string remote_file_key
 * @property integer $display_order
 * @property string $date_created
 * @property string $date_updated
 * @property string $date_deleted
 * 
 */
abstract class FileUploadModel extends EActiveRecord {

    public $file;   // EUploadedFile.
    public $file_upload_field = 'file';
    // const THUMB_POSTFIX = 'tn';
    //  const IMAGE_JPG='jpg';
    //  protected $standard_image_width = 1024;
    //  protected $standard_image_height = 1024;
    protected $thumbnail_width = 90;
    protected $thumbnail_height = 127;

    const HAS_REMOTE = 1;
    const HASNOT_REMOTE = 0;

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('uid', 'required'),
            array('file_size, display_order, has_remote', 'numerical', 'integerOnly' => true),
            array('uid', 'length', 'is' => 32),
            array('thumbnail_name, remote_file_key', 'length', 'max' => 40),
            array('file_ext', 'length', 'max' => 10),
            array('mime_type', 'length', 'max' => 20),
            array('file_url, thumbnail_url, base_url, remote_domain', 'length', 'max' => 255),
            array('date_created, date_updated, date_deleted', 'safe'),
                // The following rule is used by search().
                // @todo Please remove those attributes that should not be searched.
                //array('id, uid, file_ext, file_url, thumbnail_url, display_order, date_created, date_updated, date_deleted', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'uid' => 'UID',
            'file_ext' => 'File Extention',
            'mime_type' => 'MIME Type',
            'file_url' => 'File Url',
            'file_size' => 'File Size',
            'thumbnail_name' => 'Thumbnail Name',
            'thumbnail_url' => 'Thumbnail Url',
            'has_remote' => 'Has Remote',
            'remote_domain' => 'Remote Domain',
            'remote_file_key' => 'Remote File Key',
            'display_order' => 'Display Order',
            'date_created' => 'Date Created',
            'date_updated' => 'Date Updated',
            'date_deleted' => 'Date Deleted',
        );
    }

    /**
     * 
     * @param EUploadedFile $file
     */
    public function setFileAttributes($file) {
        $this->createUID();
        $this->file = $file;
        $this->setFileExtension($file->extensionName);
        $this->setFileSize($file->size);
        $this->setMimeType($file->type); //Since this MIME type is not checked on the server side, do not take this value for granted. Instead, use CFileHelper::getMimeType to determine the exact MIME type.
        $this->setThumbnailName($this->uid . 'tn.' . $this->getFileExtension());
        //URL Path.
        $fileUploadDir = $this->getFileUploadPath();
        $this->setFileUrl($fileUploadDir . '/' . $this->getFileName());
        $this->setThumbnailUrl($fileUploadDir . '/' . $this->getThumbnailName());
        $this->setBaseUrl(Yii::app()->getBaseUrl(true));
    }

    /**
     * 保存已上传至七牛的文件属性
     * @param type $appFile
     */
    public function setAppFileAttributes($appFile) {
        $hasRemote = self::HAS_REMOTE;
        $this->createUID();
        $this->setFileSize($appFile['size']);
        $this->setMimeType($appFile['type']);
        $this->setFileExtension($appFile['type']);
        $this->setHasRemote($hasRemote);
        $this->setRemoteDomain($appFile['remoteDomain']);
        $this->setRemoteFileKey($appFile['remoteKey']);
    }

    /**
     * 查询未存于七牛的文件
     */
    public function loadAllHasNotRemote($options = null) {
        return $this->getAllByAttributes(array('has_remote' => self::HASNOT_REMOTE), null, $options);
    }

    /**
     * is_writable(): open_basedir restriction in effect. File(/tmp) is not within the allowed path(s): (/var/php-fpm/5.4/superbeta/tmp:/virtualhost/superbeta
     */
    public function saveModel() {
        if ($this->validate()) {    // validates model attributes before saving file.
            try {
                $fileSysDir = $this->getFileSystemUploadPath();
                //createDirectory($fileSysDir);   // TODO: delete.
                //Thumbnail.
                $thumbImage = Yii::app()->image->load($this->file->getTempName());
                // $image->resize(400, 100)->rotate(-45)->quality(75)->sharpen(20);
                $thumbImage->resize($this->thumbnail_width, $this->thumbnail_height);
                if ($thumbImage->save($fileSysDir . '/' . $this->getThumbnailName()) === false) {
                    throw new CException('Error saving file thumbnail.');
                }
                if ($this->file->saveAs($fileSysDir . '/' . $this->getFileName()) === false) {
                    throw new CException('Error saving file.');
                }

                // validation is done before hand, so skip validation when saving into db.
                return $this->save(false);
            } catch (CException $e) {
                $this->addError('file', $e->getMessage());
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * gets the relative file upload root path.
     * @return type 
     */
    public function getFileUploadRootPath() {
        return '';
    }

    /**
     * gets the file upload path of given foler name.
     * @param type $folderName
     * @return type 
     */
    public function getFileUploadPath($folderName = null) {
        if ($folderName === null) {
            return $this->getFileUploadRootPath();
        } else {
            return ($this->getFileUploadRootPath() . $folderName);
        }
    }

    /**
     * get File System Path
     *
     * @param string        	
     * @return string
     */
    public function getFileSystemUploadPath($folderName = null) {
        $fileUploadPath = $this->getFileUploadPath($folderName);
        if (strStartsWith($fileUploadPath, 'upload')) {
            // upload/file
            return (Yii::getPathOfAlias('webroot') . DIRECTORY_SEPARATOR . $fileUploadPath);
        } else {
            // D:/file
            return $fileUploadPath;
        }
    }

    public function getFileLocation() {
        return $this->getFileSystemUploadPath() . DIRECTORY_SEPARATOR . $this->getFileName();
    }

    public function getThumbnailLocation() {
        return $this->getFileSystemUploadPath() . DIRECTORY_SEPARATOR . $this->getThumbnailName();
    }

    protected function createUID() {
        $this->uid = strRandomLong(32);
    }

    public function deleteModel($absolute = false) {
        if ($absolute) {
            if ($this->delete(true)) {
                try {
                    $fileSysDir = $this->getFileSystemUploadPath();
                    deleteFile($this->getFileLocation());
                    deleteFile($this->getThumbnailLocation());
                    return true;
                } catch (CException $e) {
                    return false;
                }
            }
        } else {
            return $this->delete(false);
        }
    }

    public function getByUID($uid) {
        return $this->getByAttributes(array('uid' => $uid));
    }

    /*     * ****** Accessors ******* */

    public function getAbsFileUrl() {
        $url = $this->getFileUrl();
        if (strStartsWith($url, 'http')) {
            return $url;
        } else {
            return $this->getRootUrl() . '/' . $url;
        }
    }

    public function getAbsThumbnailUrl() {
        $url = $this->getThumbnailUrl();
        if (strStartsWith($url, 'http')) {
            return $url;
        } else {
            return $this->getRootUrl() . '/' . $url;
        }
    }

    public function getRootUrl() {
        if (isset($this->base_url) && ($this->base_url != '')) {
            return $this->base_url;
        } else {
            return Yii::app()->getBaseUrl(true);
        }
    }

    public function getUID() {
        return $this->uid;
    }

    public function setUID($v) {
        $this->uid = $v;
    }

    public function getFileUrl() {
        return $this->file_url;
    }

    public function setFileUrl($v) {
        $this->file_url = $v;
    }

    public function getFileExtension() {
        return $this->file_ext;
    }

    public function setFileExtension($v) {
        $this->file_ext = $v;
    }

    public function getFileName() {
        return $this->uid . '.' . $this->file_ext;
    }

    public function getFileSize() {
        return $this->file_size;
    }

    public function setFileSize($v) {
        $this->file_size = $v;
    }

    public function getMimeType() {
        return $this->mime_type;
    }

    public function setMimeType($v) {
        $this->mime_type = $v;
    }

    public function getThumbnailName() {
        return $this->thumbnail_name;
    }

    public function setThumbnailName($v) {
        $this->thumbnail_name = $v;
    }

    public function getThumbnailUrl() {
        return $this->thumbnail_url;
    }

    public function setThumbnailUrl($v) {
        $this->thumbnail_url = $v;
    }

    public function getBaseUrl() {
        return $this->base_url;
    }

    public function setBaseUrl($v) {
        $this->base_url = $v;
    }

    public function setHasRemote($v) {
        $this->has_remote = $v;
    }

    public function getHasRemote($v = true) {
        if ($v) {
            return $this->has_remote == self::HAS_REMOTE ? true : false;
        } else {
            return $this->has_remote;
        }
    }

    public function setRemoteDomain($v) {
        $this->remote_domain = $v;
    }

    public function getRemoteDomain() {
        return $this->remote_domain;
    }

    public function setRemoteFileKey($v) {
        $this->remote_file_key = $v;
    }

    public function getRemoteFileKey() {
        return $this->remote_file_key;
    }

    public function getDisplayOrder() {
        return $this->display_order;
    }

    public function setDisplayOrder($v) {
        $this->display_order = $v;
    }

}
