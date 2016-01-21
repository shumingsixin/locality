<?php

class DownloadController extends WebsiteController {

    public function actionApp() {
        $userAgent = strtolower(Yii::app()->request->getUserAgent());

        $appMgr = new AppManager();
        if ($this->isUserAgentIOS()) {
            $dlUrl = $appMgr->loadCurrentIOSUrl();
            if (strIsEmpty($dlUrl) === false) {
                // redirect to apple appstore.
                $this->redirect($dlUrl);
            }
        } else if ($this->isUserAgentAndroid()) {
            //echo 'is Android <br>';
            $dlUrl = $appMgr->loadCurrentAndroidUrl();
            // redirect to apk downloading link.
            $this->redirect($dlUrl);
        } else if (strContains($userAgent, 'windows')) {
            //echo 'is Windows<br>';
        }

        // render downloading page.
        $this->render("app");
    }

}
