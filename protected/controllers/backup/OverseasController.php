<?php

class OverseasController extends WebsiteController {

    public $menu;
    public $urlUpload;
    public $page_list;
    public $current_page;
    private $defaultPage = 'sg';

    public function init() {
        $this->setPageTitle('海外名医');
        //   $this->urlUpload = Yii::app()->baseUrl . '/upload/overseas/';
        return parent::init();
    }

    public function actionIndex() {
        $this->redirect(array('view', 'page' => $this->defaultPage));
        //$this->redirect(array('view', 'id' => $this->defaultPage));
    }

    public function actionView($page) {
        $this->current_page = $page;

        $this->render('viewXiumi');
    }

    public function actionHospital() {
        $this->redirect(array('view', 'page' => $this->defaultPage));
        $this->current_page = 'h-elizabeth';
        $this->loadPageMenu();

        $this->render('hospital');
    }

    public function actionSurgery() {
        $this->current_page = 'surgery';
        $menuList = array('#surgery-1' => '无刀飞秒激光白内障手术', '#surgery-2' => '肾癌切除术', '#surgery-3' => '清醒开颅手术');
        $this->loadPageMenu($menuList);
        $this->render('surgery');
    }

    /*
      public function actionView($id=null) {
      $this->redirect(array('hospital'));
      $this->content_container = "container-fluid";
      $list = $this->getPageList();

      if (isset($list[$id])) {
      $view = $id;
      $folder = $id;
      $this->current_page = $id;
      } else {
      $this->redirect(array('view', 'id' => $this->defaultPage));
      }

      $this->render('pages/' . $view, array(
      'urlUpload' => $this->urlUpload . $folder . '/'
      ));
      }
     */

    public function getPageList() {
        if ($this->page_list === null) {
            $this->page_list = array('elizabeth' => '伊丽莎白医院', 'neuroscience' => '神经外科专题');
        }
        return $this->page_list;
    }

    public function getCurrentPageLabel() {
        $list = $this->getPageList();
        if (isset($list[$this->current_page])) {
            return $list[$this->current_page];
        }
    }

    public function loadPageMenu($list = null) {
        if ($this->menu === null) {
            if ($list === null) {
                $list = array('sg' => '新加坡', 'usa' => '美国', 'korea' => '韩国', 'japan' => '日本');
            }
            $this->menu = array();
            foreach ($list as $key => $value) {
                if (strStartsWith($key, '#')) {
                    $link = $key;
                } else {
                    $link = $this->createUrl('view', array('page' => $key));
                }
                $menuItem = array(
                    'label' => $value,
                    'key' => $key,
                    //'link' => '#' . $key,
                    'link' => $link,
                    'active' => ''
                );
                if ($key == $this->current_page) {
                    $menuItem['active'] = 'active';
                }
                $this->menu[] = $menuItem;
            }
        }
        return $this->menu;
    }

}
