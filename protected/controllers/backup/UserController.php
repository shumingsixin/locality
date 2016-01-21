<?php

class UserController extends WebsiteController {
    // public $model;  // User model.

    /**
     * @return array action filters
     */
    public function filters() {
        return array(
            'accessControl', // perform access control for CRUD operations
            'postOnly + delete', // we only allow deletion via POST request
            'userContext + account updateProfile changePassword',
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
                'actions' => array('register', 'login', 'captcha'),
                'users' => array('*'),
            ),
            array('allow', // allow authenticated user to perform 'create' and 'update' actions
                'actions' => array('logout', 'changePassword', 'account'),
                'users' => array('@'),
            ),
            array('allow', // allow admin user to perform 'admin' and 'delete' actions
                'actions' => array(''),
                'users' => array('admin'),
            ),
            array('deny', // deny all users
                'users' => array('*'),
            ),
        );
    }

    /**
     * Declares class-based actions.
     */
    public function actions() {
        return array(
            // captcha action renders the CAPTCHA image displayed on the contact page
            'captcha' => array(
                'class' => 'CCaptchaAction',
                'backColor' => 0xFFFFFF,
                'maxLength' => 6,
                'offset' => 0,
                'testLimit' => 0,
                'height' => 34
            ),
        );
    }

    public function actionLogin() {
        $form = new UserLoginForm();
        $form->setRole(StatCode::USER_ROLE_PATIENT);

        // collect user input data
        if (isset($_POST['UserLoginForm'])) {
            $form->attributes = $_POST['UserLoginForm'];
            $userMgr = new UserManager();
            $userMgr->doLogin($form);
            if ($form->hasErrors() === false) {
                $this->redirect(Yii::app()->user->returnUrl);
            }
        }

        // display the login form
        $this->render('login', array('model' => $form));
    }

    public function actionLogout() {
        Yii::app()->user->logout();
        $this->redirect(Yii::app()->user->returnUrl);
    }

    /**
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     */
    public function actionAccount() {
        $this->setPageTitle('我的账户');
        $user = $this->getCurrentUser();

        $passwordForm = new UserPasswordForm('new');
        $passwordForm->initModel($user);

        $this->render('account', array(
            'model' => $user,
            'userPasswordForm' => $passwordForm,
        ));
    }

    public function actionRegister() {
        $userRole = User::ROLE_PATIENT;
        $form = new UserRegisterForm();
        $form->role = $userRole;
        $form->terms = 1;
        //$form->sms_verify_code = 123456;

        $this->performAjaxValidation($form);

        if (isset($_POST['UserRegisterForm'])) {
            $form->attributes = $_POST['UserRegisterForm'];
            //   $form->scenario = 'getSmsCode';   //set this scenario when using sms verify code.

            $userMgr = new UserManager();
            $userMgr->registerNewUser($form);
            if ($form->hasErrors() === false) {
                // success                
                $loginForm = $userMgr->autoLoginUser($form->username, $form->password, $userRole, 1);
                $this->redirect(Yii::app()->user->returnUrl);
            }
        }

        $this->render('register', array(
            'model' => $form,
        ));
    }

    public function actionChangePassword() {
        $user = $this->getCurrentUser();
        $form = new UserPasswordForm('new');
        $form->initModel($user);
        $this->performAjaxValidation($form);

        if (isset($_POST['UserPasswordForm'])) {
            $form->attributes = $_POST['UserPasswordForm'];
            $userMgr = new UserManager();
            $success = $userMgr->doChangePassword($form);
            if ($this->isAjaxRequest()) {
                if ($success) {
                    //do anything here
                    echo CJSON::encode(array(
                        'status' => 'true'
                    ));
                    Yii::app()->end();
                } else {
                    $error = CActiveForm::validate($form);
                    if ($error != '[]') {
                        echo $error;
                    }
                    Yii::app()->end();
                }
            } else {
                if ($success) {
                    // $this->redirect(array('user/account'));
                    $this->setFlashMessage('user.password', '密码修改成功！');
                }
            }
        }

        $this->render('changePassword', array(
            'model' => $form
        ));
    }

    /*
      public function actionSendSmsVerifyCode() {
      if (isset($_POST['UserSmsVerify'])) {
      $values = $_POST['UserSmsVerify'];
      if (isset($values['mobile']) && isset($values['action_type'])) {
      $mobile = $values['mobile'];
      $actionType = $values['action_type'];
      $captchaCode = null;
      if (isset($values['captcha_code'])) {
      $captchaCode = $values['captcha_code'];
      }
      $form = new UserRegisterForm();
      $form->username = $mobile;
      $form->captcha_code = $captchaCode;
      $userMgr = new UserManager();
      //Check if captcha code is correct.
      $success = $userMgr->validateCaptchaCode($form);
      if ($form->hasErrors()) {
      $this->renderJsonOutput($form->getErrors());
      Yii::app()->end();
      }

      // Create UserSmsVerify record in db.
      $authMgr = new AuthManager();
      $userSmsVerify = $authMgr->createUserSmsVerify($mobile, $actionType);
      if ($userSmsVerify->hasErrors()) {
      $this->renderJsonOutput($userSmsVerify->getErrors());
      } else {
      $this->renderJsonOutput(array('status' => 'true'));
      }
      Yii::app()->end();
      } else {
      $this->throwPageNotFoundException();
      }
      } else {
      $this->throwPageNotFoundException();
      //  $this->redirect($this->getHomeUrl());
      }
      }
     */

    public function loadSideMenu($activeItem = null) {
        $menu = array(
            'setting' => array('key' => '', 'label' => '账户设置', 'link' => $this->createUrl('user/account'), 'active' => ''),
                //'mr'=>array('key' => '', 'label' => '', 'link' => '', 'active' => ''),            
        );
        foreach ($menu as $key => &$menuItem) {
            if ($key == $activeItem) {
                $menuItem['active'] = 'active';
                break;
            }
        }
        return $menu;
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return User the loaded model
     * @throws CHttpException
     */
    public function loadModel($id) {
        $model = User::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param User $model the model to be validated
     */
    protected function performAjaxValidation($model) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'user-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

}
