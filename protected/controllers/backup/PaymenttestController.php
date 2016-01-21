<?php

class PaymenttestController extends WebsiteController {

    private $booking;

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules() {
        return array(
            array('allow', // allow all users to perform 'index' and 'view' actions
                'actions' => array('doPingxxPay', 'alipayReturn', 'alipayNotify', 'pingxxReturn', 'yeepayReturn'),
                'users' => array('*'),
            ),
            array('allow', // allow authenticated user to perform 'create' and 'update' actions
                'actions' => array(''),
                'users' => array('@'),
            ),
            array('deny', // deny all users
                'users' => array('*'),
            ),
        );
    }

    public function actionDoPingxxPay() {
        require_once('protected/sdk/pingpp-php-master/init.php');
        $output = new stdClass();
        //$output->status = 'no';
        $output->errorMsg = null;
        $output->pingCharge = null;
        try {
            $input_data = file_get_contents('php://input');
            $post = CJSON::decode($input_data);
            if (isset($post['order_no'])) {
                $refno = $post['order_no'];
            } else {
                throw new CException('invalid parameters - missing ref_no.');
            }
            if (isset($post['channel'])) {
                $channel = strtolower($post['channel']);
            } else {
                throw new CException('invalid parameters - missing channel.');
            }
            if (isset($post['ref_url'])) {
                $refurl = strtolower($post['ref_url']);
            } else {
                throw new CException('invalid parameters - missing ref_url.');
            }
            $payMgr = new PayManager();
            $output->pingCharge = $payMgr->doPingxxPay($refno, $channel, $refurl);
        } catch (\Pingpp\Error\Base $e) {
            header('Status: ' . $e->getHttpStatus());
            $output->errorMsg = $e->getHttpBody();
            Yii::log($e->getHttpBody(), CLogger::LEVEL_ERROR, __METHOD__);
        } catch (CDbException $cdbex) {
            Header("http/1.1 404 Not Found");
            $output->errorMsg = 'error loading data';
            Yii::log($cdbex->getMessage(), CLogger::LEVEL_ERROR, __METHOD__);
        } catch (CException $cex) {
            Header("http/1.1 400 Bad Request");
            $output->errorMsg = $cex->getMessage();
            Yii::log($cex->getMessage(), CLogger::LEVEL_ERROR, __METHOD__);
        }

        if (is_null($output->errorMsg)) {
            // success.
            header('Content-Type: application/json; charset=utf-8');
            try {
                echo $output->pingCharge;
                CoreLogPayment::log($output->pingCharge, CoreLogPayment::LEVEL_INFO, Yii::app()->request->url, __METHOD__);
            } catch (\Pingpp\Error\Base $e) {
                header('Status: ' . $e->getHttpStatus());
                echo($e->getHttpBody());
                CoreLogPayment::log($e->getHttpBody(), CoreLogPayment::LEVEL_ERROR, Yii::app()->request->url, __METHOD__);
            }
        } else {
            //error.
            //var_dump($output->errorMsg);
            throw new CHttpException(404, $output->errorMsg);
        }
    }

    public function actionPingxxReturn() {
        $post = json_decode(file_get_contents('php://input'), true);
        CoreLogPayment::log('pingxxReturnJson: '.CJSON::encode($post), CoreLogPayment::LEVEL_INFO, Yii::app()->request->url, __METHOD__);
        $payMgr = new PayManager();
        $pingChargeId = $post['data']['object']['id'];
        $orderNo = $post['data']['object']['order_no'];
        CoreLogPayment::log('orderNo: '.$orderNo, CoreLogPayment::LEVEL_INFO, Yii::app()->request->url, __METHOD__);
        $payment = SalesPayment::model()->getByAttributes(array('uid' => $orderNo, 'ping_charge_id' => $pingChargeId));
        if (isset($payment) && $post['type'] == 'charge.succeeded') {
            //交易成功
            $payMgr->updateDataAfterTradeSuccess($payment, $post);
        } else if (isset($payment) && $post['type'] != 'charge.succeeded') {
            //交易失败
            $payMgr->updateDataAfterTradeFail($payment, $post);
        } else if ($payment == NULL) {
            //没有此笔交易
        }
    }

    public function actionAlipayReturn() {
        CoreLogPayment::log('AlipayReturnJson: '.CJSON::encode($_GET), CoreLogPayment::LEVEL_INFO, Yii::app()->request->url, __METHOD__);
        $outTradeNo = $_GET['out_trade_no'];
        $payment = SalesPayment::model()->getByAttributes(array('uid' => $outTradeNo), array('paymentOrder'));
//        $order = $payment->paymentOrder;

        $this->redirect(array('payResult', 'paymentcode' => $payment->uid));
    }
    
    public function actionYeepayReturn() {
        CoreLogPayment::log('YeepayReturnJson: '.CJSON::encode($_GET), CoreLogPayment::LEVEL_INFO, Yii::app()->request->url, __METHOD__);
        $outTradeNo = $_GET['out_trade_no'];
        $payment = SalesPayment::model()->getByAttributes(array('uid' => $outTradeNo), array('paymentOrder'));
//        $order = $payment->paymentOrder;

        $this->redirect(array('payResult', 'paymentcode' => $payment->uid));
    }

    public function actionPayResult($paymentcode) {
        $payment = SalesPayment::model()->getByAttributes(array('uid' => $paymentcode), array('paymentOrder'));
        $order = $payment->paymentOrder;
        if ($order === null) {
            throw new CHttpException(404, 'The requested page does not exist.');
        }
        $this->show_header = true;
        $this->show_footer = false;
        $this->show_baidushangqiao = false;
        $this->render('result', array('model' => $order));
    }

    public function actionPayBooking($ref) {

        $bookingMgr = new BookingManager();
        $booking = $bookingMgr->loadBookingByRefNo($ref);

        if (isset($_POST['payment'])) {
            $paymentMgr = new PaymentManager();
            $payment = $paymentMgr->BookingPayment($booking, $payMethod);
        }
    }

    public function actionAlipay($bid) {
        $booking = $this->loadBookingById($bid, array('mrbUser', 'mrbMedicalRecord', 'mrbFaculty'));

        if (isset($_POST['payment'])) {
            $post = $_POST['payment'];
            $bookingId = $post['bid'];
            $booking = $this->loadBookingById($bookingId, array('mrbUser', 'mrbMedicalRecord', 'mrbFaculty'));
            $payMethod = 1;

            $paymentMgr = new PaymentManager();
            $payment = $paymentMgr->BookingPayment($booking, $payMethod);
            if (isset($payment)) {
                if ($payment->hasErrors() === false) {
                    $this->redirect($payment->getRequestUrl());
                    echo CHtml::link($payment->getRequestUrl(), $payment->getRequestUrl(), array('target' => '_blank'));
                } else {
                    $errors = $payment->getErrors();
                    echo CJSON::encode(array('status' => 'false', 'errors' => $errors));
                }
            } else {
                echo CJSON::encode(array('status' => 'false', 'errors' => array('访问错误')));
            }
        }
        $this->render('alipay', array(
            'booking' => $booking
        ));
    }

    public function actionTest() {
        if (isset($_POST['payment']['amt'])) {
            $amt = floatval($_POST['payment']['amt']);
            $payMethod = 1;
            $booking = new MedicalRecordBooking();
            $booking->id = 100000;
            $booking->user_id = 3;
            $booking->faculty_id = 1;
            $booking->subject = '测试付款';
            $booking->total_price = $amt;
            $booking->currency = 'RMB';

            $paymentMgr = new PaymentManager();
            $payment = $paymentMgr->BookingPayment($booking, $payMethod);
            if (isset($payment)) {
                if ($payment->hasErrors() === false) {
                    $this->redirect($payment->getRequestUrl());
                    echo CHtml::link($payment->getRequestUrl(), $payment->getRequestUrl(), array('target' => '_blank'));
                } else {
                    $errors = $payment->getErrors();
                    echo CJSON::encode(array('status' => 'false', 'errors' => $errors));
                }
            } else {
                echo CJSON::encode(array('status' => 'false', 'errors' => array('访问错误')));
            }
        }
        $this->render('test');
    }

    public function loadBookingById($id, $with = null) {
        $model = MedicalRecordBooking::model()->getById($id, $with);
        if (is_null($model)) {
            $this->throwPageNotFoundException();
        } else {
            return $model;
        }
    }

}
