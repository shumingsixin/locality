<?php

require_once dirname(__FILE__) . '/../sdk/vendor/autoload.php';
require_once dirname(__FILE__) . '/../sdk/vendor/config.php';
// 引入鉴权类
use Qiniu\Auth;
//引入上传类
use Qiniu\Storage\UploadManager;

class ApiServiceFileUploadQiniu {

    private $accessKey = Config::ACCESS_KEY;
    private $secretKey = Config::SECRET_KEY;

    /**
     * 将查询到的文件上传至七牛云盘
     */
    public function fileUpdateCloud($files, $tableName = '') {

        $bucket = Config::getBucketByTableName($tableName);
        // 要上传的空间
        $auth = new Auth($this->accessKey, $this->secretKey);
        //查询为存于云盘的文件
        $fileDbCount = count($files);
        $fileQnCount = 0;
        //在循环之外定义时间 文件存储时间
        $endTime = time() + 3 * 60;
        if (arrayNotEmpty($files)) {
            foreach ($files as $v) {
                //循环开始 时间判断 
                if (time() > $endTime) {
                    //跳出循环
                    break;
                }
                // 生成上传 Token
                $token = $auth->uploadToken($bucket);
                $key = $v->getFileName();
                //获取文件本地地址
                $filePath = $v->getAbsFileUrl();
                if (strIsEmpty($filePath)) {
                    continue;
                }

                // 初始化 UploadManager 对象并进行文件的上传。
                $uploadMgr = new UploadManager();
                try {
                    //文件开始上传 log记录
                    $fileArr = array('tableName' => $tableName, 'rowId' => $v->getId(), 'level' => CLogger::LEVEL_INFO, 'category' => __METHOD__, 'subject' => '文件上传七牛', 'message' => $key . '文件开始上传到七牛!');
                    $filelog = new FileuploadLog();
                    $filelog->createModel($fileArr);
                    //文件上传云盘
                    list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
                    if ($err == null) {
                        $v->setRemoteDomain($this->domain);
                        $v->setHasRemote(FileTest::HAS_REMOTE);
                        $v->setRemoteFileKey($key);
                        //上传成功 数据更新
                        $v->update();
                        $fileQnCount++;
                    } else {
                        $response = $err->getResponse();
                        //上传失败 
                        $fileArr = array('tableName' => $tableName, 'rowId' => $v->getId(), 'level' => CLogger::LEVEL_ERROR, 'category' => __METHOD__ . $response->statusCode, 'subject' => '文件上传七牛', 'message' => $key . '文件上传到七牛出错!错误信息:' . $response->error);
                        $filelog = new FileuploadLog();
                        $filelog->createModel($fileArr);
                    }
                } catch (CDbException $cdbex) {
                    //数据库错误
                    $fileArr = array('tableName' => $tableName, 'rowId' => $v->getId(), 'level' => CLogger::LEVEL_ERROR, 'category' => __METHOD__ . $cdbex->getCode(), 'subject' => '数据库更新', 'message' => $cdbex->getMessage());
                    $filelog = new FileuploadLog();
                    $filelog->createModel($fileArr);
                } catch (CException $cex) {
                    $fileArr = array('tableName' => $tableName, 'rowId' => $v->getId(), 'level' => CLogger::LEVEL_ERROR, 'category' => __METHOD__ . $cex->getCode(), 'subject' => '文件上传七牛', 'message' => $cex->getMessage());
                    $filelog = new FileuploadLog();
                    $filelog->createModel($fileArr);
                }
            }
        }
        $message = '本次任务查询出的文件数:' . $fileDbCount . ', 上传七牛成功的文件数:' . $fileQnCount;
        $fileArr = array('tableName' => $tableName, 'rowId' => null, 'level' => CLogger::LEVEL_INFO, 'category' => __METHOD__, 'subject' => '文件上传七牛定时任务', 'message' => $message);
        $filelog = new FileuploadLog();
        $filelog->createModel($fileArr);
    }

}
