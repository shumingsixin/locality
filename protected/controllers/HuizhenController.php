<?php

class HuizhenController extends WebsiteController {

    public $ifaculty;    // IFaculty object.
    public $resource_url;
    public $page_list;
    public $current_page;
    private $default_page = 'guke';

    public function init() {
        $this->resource_url = Yii::app()->baseUrl . '/resource/huizhen/';
        return parent::init();
    }

    public function actionIndex() {
        $this->redirect(array('view', 'id' => $this->default_page));
    }

    public function actionView($id = null) {

        $this->content_container = "";
        if (is_null($id)) {
            $id = 'guke';
        }
        $this->current_page = $id;

        $ifaculty = $this->loadIFaculty($id);
        $this->render('view', array(
            'model' => $ifaculty,
        ));
    }

    /*
      public function actionView($id=null) {
      $this->content_container = "";
      $list = $this->getPageList();

      if (isset($list[$id])) {
      $view = $id;
      $folder = $id;
      $this->current_page = $id;
      } else {
      $this->redirect(array('view', 'id' => $this->default_page));
      }
      $this->resource_url = $this->resource_url . $folder . '/';

      $this->setPageTitle('国内会诊 - ' . $list[$id]);

      $this->render('pages/' . $view, array(
      //'urlResource' => $this->resource_url . $folder . '/'
      ));
      }
     * 
     */

    public function getPageList() {
        if ($this->page_list === null) {
            $this->page_list = array('gandan' => '肝胆外科', 'guke' => '骨科', /*'xiongwaike' => '胸外科', 'miniaowaike' => '泌尿外科',*/ 'xinxueguan' => '心血管', 'zhongliu' => '肿瘤', 'fuchan' => '妇产','shenjingke'=>'神经科');
        }
        return $this->page_list;
    }

    public function getCurrentPageLabel() {
        $list = $this->getPageList();
        if (isset($list[$this->current_page])) {
            return $list[$this->current_page];
        }
    }

    public function loadPageMenu() {
        $list = $this->getPageList();
        $menu = array();
        foreach ($list as $key => $value) {
            $menuItem = array(
                'label' => $value,
                'key' => $key,
                'link' => $this->createUrl('view', array('id' => $key)),
                'active' => ''
            );
            if ($key == $this->current_page) {
                $menuItem['active'] = 'active';
            }
            $menu[] = $menuItem;
        }
        return $menu;
    }

    /**
     * Queries Faculty by code. Generate and return IFaculty object.
     * @param string $code Faculty.code.
     * @return IFaculty $this->ifaculty. 
     */
    public function loadIFaculty($code) {
        //  $limitHospital = 4;
        // $limitDoctor = 0;
        if ($this->ifaculty === null) {
            $faculty = Faculty::model()->getByCode($code);

            if ($faculty === null) {
                throw new CHttpException(404, 'The requested page does not exist.');
            }
        }
        $this->ifaculty = new IFaculty();
        $this->ifaculty->initModel($faculty);
        $listJoins = $faculty->getHospitalJoinsVisible();
        $hospitals = $faculty->getVisibleHospitals();
        $limit=10;
        if (arrayNotEmpty($listJoins) && arrayNotEmpty($hospitals)) {
            $count=0;
            foreach ($listJoins as $join) {
                $count++;
                if($count>$limit){
                    break;
                }
                foreach ($hospitals as $hospitalModel) {
                    if ($join->getHospitalId() === $hospitalModel->getId()) {
                        $hospital = new IHospital();
                       // $hospital = new stdClass();
                        $hospital->initModel($hospitalModel);
                        /*
                        $hospital->id = $hospitalModel->getId();
                        $hospital->name = $hospitalModel->getName();
                        $hospital->class = $hospitalModel->getClass();
                        $hospital->type = $hospitalModel->getType();
                        $hospital->description = $join->getDescription();
                         * 
                         */
                        $this->ifaculty->addHospital($hospital);
                        break;
                    }
                }
            }
        }
        /*
          if (arrayNotEmpty($hospitals)) {
          foreach ($hospitals as $hospital) {
          $this->ifaculty->addHospital($hospital);
          }
          }
         */
        $listJoins = $faculty->getDoctorJoinsVisible();
        $doctors = $faculty->getVisibleDoctors();
        if (arrayNotEmpty($listJoins) && arrayNotEmpty($doctors)) {
            foreach ($listJoins as $join) {
                foreach ($doctors as $doctorModel) {
                    if ($join->getDoctorId() === $doctorModel->getId()) {
                        $doctor = new stdClass();
                        $doctor->id = $doctorModel->getId();
                        $doctor->name = $doctorModel->getName();
                        $doctor->urlAvatar = $doctorModel->getAbsUrlAvatar();
                        $doctor->hospitalName = $doctorModel->getHospitalName(true);
                        $doctor->faculty = $doctorModel->faculty;
                        $doctor->medicalTitle = $doctorModel->getMedicalTitle();
                        $doctor->academicTitle = $doctorModel->getAcademicTitle();
                        $doctor->description=$doctorModel->getDescription();
                        $this->ifaculty->addDoctor($doctor);
                    }
                }
            }
        }
        return $this->ifaculty;
    }

}
