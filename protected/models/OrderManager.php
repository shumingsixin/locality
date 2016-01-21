<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of OrderManager
 *
 * @author shuming
 */
class OrderManager {

    public function createSalesOrder($model) {
        $order = new SalesOrder();
        $order->order_type = SalesOrder::ORDER_TYPE_DEPOSIT;
        if ($model instanceof PatientBooking) {
            $order->createRefNo($model->refNo, $model->id, StatCode::TRANS_TYPE_PB);
            $order->user_id = $model->creator_id;
            //根据creator_id 查询其所在地址
            $userDoctorPorfile = UserDoctorProfile::model()->getByUserId($model->creator_id);
            if (isset($userDoctorPorfile)) {
                $stateName = $userDoctorPorfile->getStateName();
                if ($stateName == '北京' || $stateName == '天津' || $stateName == '上海' || $stateName == '重庆') {
                    $bdCode = $stateName;
                } else {
                    $bdCode = $stateName . $userDoctorPorfile->getCityName();
                }
                $order->bd_code = $bdCode;
            }
            $order->bk_ref_no = $model->refNo;
            $order->bk_type = StatCode::TRANS_TYPE_PB;
            if ($model->travel_type == StatCode::BK_TRAVELTYPE_DOCTOR_COME) {
                $order->order_type = SalesOrder::ORDER_TYPE_SERVICE;
            }
            $order->subject = $order->getOrderType(true) . '-' . $model->getPatientName();
            $order->description = '预约号:' . $model->getRefNo() . '。' . $model->getTravelType(true) . '所支付的' . $order->subject . '! 订单号:' . $order->ref_no;
        } elseif ($model instanceof Booking) {
            $order->createRefNo($model->refNo, $model->id, StatCode::TRANS_TYPE_BK);
            $order->user_id = $model->getUserId();
            $order->bk_ref_no = $model->refNo;
            $order->bk_type = StatCode::TRANS_TYPE_BK;
            $order->subject = $order->getOrderType(true) . '-' . $model->getContactName();
            if (strIsEmpty($model->getExpertNameBooked())) {
                $description = $model->getDiseaseDetail();
            } else {
                $description = '支付给"' . $model->getExpertNameBooked() . '"的预约金';
            }
            $order->description = '预约号:' . $model->getRefNo() . '。' . $description . '!  订单号:' . $order->ref_no;
        } else {
            throw new CException('Unknown class');
        }
        //相同的值
        $order->is_paid = SalesOrder::ORDER_UNPAIDED;
        $order->bk_id = $model->getId();
        $order->created_by = Yii::app()->user->id;
        $order->date_open = date('Y-m-d H:i:s');
        $order->setAmount($order->getOrderTypeDefaultAmount());
        $order->save();
        return $order;
    }

    //查询预约单的支付情况
    public function loadSalesOrderByBkIdAndBkTypeAndOrderType($bkId, $bkType = StatCode::TRANS_TYPE_PB, $orderType = SalesOrder::ORDER_TYPE_DEPOSIT, $attributes = '*', $with = null, $options = null) {
        return SalesOrder::model()->getByBkIdAndBkTypeAndOrderType($bkId, $bkType, $orderType, $attributes, $with, $options);
    }

    //查询预约单的所有支付情况
    public function loadSalesOrderByBkIdAndBkType($bkId, $bkType = StatCode::TRANS_TYPE_PB, $attributes = '*', $with = null, $options = null) {
        return SalesOrder::model()->getByBkIdAndBkType($bkId, $bkType, $attributes, $with, $options);
    }

}
