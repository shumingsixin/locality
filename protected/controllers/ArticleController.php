<?php

class ArticleController extends WebsiteController {

    public function actionView($id) {
        $list = array(1, 2, 3, 4, 5, 6,7,8,9);
        if (in_array($id, $list)) {
            $this->render('pages/article' . $id);
        } else {
            throw new CHttpException(404, 'The requested page does not exist.');
        }
    }

}