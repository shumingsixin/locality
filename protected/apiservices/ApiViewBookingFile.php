<?php

class ApiViewBookingFile extends EApiViewService {

    private $bookingMgr;
    private $values;

    public function __construct($values) {
        parent::__construct();
        $this->values = $values;
        $this->bookingMgr = new BookingManager();
    }

    protected function createOutput() {
        if (is_null($this->output)) {
            $this->output = array(
                'status' => self::RESPONSE_OK,
                'errorCode' => 0,
                'errorMsg' => 'success',
                'results' => $this->results,
            );
        }
    }

    protected function loadData() {
        $this->saveBooking();
    }

    private function saveBooking() {
        if (isset($this->values['id']) === false) {
            $this->output['status'] = 'no';
            $this->output['errorMsg'] = 'invalid parameters';
            $this->results->file = null;
        } else {
            $bookingId = $this->values['id'];
            $booking = $this->bookingMgr->loadBookingMobileById($bookingId);
            if (isset($booking) === false) {
                $this->output['status'] = 'no';
                $this->output['errorMsg'] = 'invalid parameters';
                $this->results->file = null;
            } else {
                $ret = $this->bookingMgr->createBookingFile($booking);
                if (isset($ret['error'])) {
                    $this->output['status'] = 'no';
                    $this->output['errorMsg'] = 'file have errors';
                    $this->results->file = null;
                } else {
                    $fileModel = $ret['filemodel'];
                    $this->setFile($fileModel);
                }
            }
        }
    }

    private function setFile($fileModel) {
        $data = new stdClass();
        $data->id = $fileModel->getId();
        $data->bookingId = $fileModel->getBookingId();
        $data->fileUrl = $fileModel->getAbsFileUrl();
        $data->tnUrl = $fileModel->getAbsThumbnailUrl();
        $this->results->file = $data;
    }

}
