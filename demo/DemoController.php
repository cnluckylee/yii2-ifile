<?php

namespace iFile;

/**
 * Description of newPHPClass
 *
 * @author ray
 */
class DemoController extends \yii\web\Controller
{
    public $enableCsrfValidation = false;
    
    public function actionSimpleTest()
    {
        // 简单上传
        $objectUrl = \yii::$app->iFile->createClient()->uploadFile('/tmp/hello.txt', 'hello.txt', true);
        echo '简单上传:' . $objectUrl . PHP_EOL;
        
        // 分片上传
        $largeObjectUrl = \yii::$app->iFile->createClient()->multiUploadFile('/tmp/OmniGrafflePro.dmg', 'bigFile.dmg', true);
        echo '分片上传:' . $largeObjectUrl . PHP_EOL;
                
        // 下载
        $localFile = \yii::$app->iFile->createClient()->downloadFile($objectUrl, '/tmp/upload.txt');
        echo '下载:'  . $localFile . PHP_EOL;
        
        // 获取预签名地址
        $viewUrl = \yii::$app->iFile->createClient()->getFileUrl('upfile.txt', 'get', 160);
        echo '下载url:' . $viewUrl . PHP_EOL;
        
        $upUrl = \yii::$app->iFile->createClient()->getFileUrl('upfile.txt', 'put', 160);
        echo '上传url:' . $upUrl . PHP_EOL;
        
        // 删除
        $result = \yii::$app->iFile->createClient()->deleteFile($largeObjectUrl);
        echo '删除文件:' . $result . PHP_EOL;
    }
    
    
    public function actionContentTest()
    {
        // 上传文件内容
        $content = file_get_contents('/tmp/hello.txt');
        $objectUrl = \yii::$app->iFile->createClient()->uploadContent($content, 'hello.txt');
        echo '上传文件内容:' . $objectUrl . PHP_EOL;
        
        // 指定内容类型
        $options = new \iFile\Options();
        $options->contentType = 'text/plain';
        $objectUrl = \yii::$app->iFile->createClient()->uploadContent($content, 'hello.txt', true, $options);
        
        // 读取文件内容
        $content = \yii::$app->iFile->createClient()->getFileContent($objectUrl);
        echo '读取文件内容:' . $content . PHP_EOL;
    }

    /**
     * 使用默认值
     */
    public function actionDefaultTest()
    {
        // 省略第二个参数时等同于logo.jpg
        $objectUrl = \yii::$app->iFile->createClient()->uploadFile('/tmp/logo.jpg', '', true);
        echo '简单上传:' . $objectUrl . PHP_EOL;
        
        // 省略第二个参数时等同于OmniGrafflePro.dmg
        $largeObjectUrl = \yii::$app->iFile->createClient()->multiUploadFile('/tmp/OmniGrafflePro.dmg', '', true);
        echo '分片上传:' . $largeObjectUrl . PHP_EOL;
        
        // 省略第二个参数时等同于logo.jpg
        $localFile = \yii::$app->iFile->createClient()->downloadFile('logo.jpg', '');
        echo '下载:'  . $localFile . PHP_EOL;
    }
    
    /**
     * 使用对象上传
     */
    public function actionUpload()
    {
        $file = \yii\web\UploadedFile::getInstanceByName('file');
        $objectUrl = \yii::$app->iFile->createClient()->uploadFile($file, 'uploadedFile.txt', true);
        echo '简单上传:' . $objectUrl . PHP_EOL;
        
        $objectUrl = \yii::$app->iFile->createClient()->uploadFile($_FILES['file']['tmp_name'], 'uploadedFileFromFILES.txt');
        echo '简单上传:' . $objectUrl . PHP_EOL;
    }
    
    /**
     * 生成sts凭证
     */
    public function actionSts()
    {
        $data = \yii::$app->iFile->createClient()->createStsCredentials();
        \Yii::$app->response->data = $data;
        \Yii::$app->end();
    }
    
    /**
     * 生成表单签名
     */
    public function actionSignature()
    {
        $data = \yii::$app->iFile->createClient()->createFormSignature(600);
        \Yii::$app->response->data = $data;
        \Yii::$app->end();
    }
    
    /**
     * 操作非默认bucket
     */
    public function actionBucket()
    {
        \yii::$app->iFile->createClient('mybucket1')->uploadFile($file);
        // 等同于
        \yii::$app->iFile->createClient()->useBucket('mybucket1')->uploadFile($file);
    }
    
    /**
     * 上传目录
     */
    public function actionUploadDirectory()
    {
        $data = \yii::$app->iFile->createClient()->uploadDirectory('/tmp/oss', 'dir', true);
        \Yii::$app->response->data = $data;
        \Yii::$app->end();
    }
}
