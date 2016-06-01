<?php

class Config {

    const ACCESS_KEY = 'hGzG98hXIjjNWBsM27THRzN9RwvRC124OBHgrtGH';
    const SECRET_KEY = 'NXmufbJuvEhRYnS5M8FfR0tO0PE4aLtuehJOBkyb';
    const URL_TIME = 120;

    //根据表名获取的对应的空间名
    public static function getBucketByTableName($tableName) {
        $buckets = array('user_doctor_cert' => 'doctor-cert',
            'booking_file' => 'medical-record',
            'patient_mr_file' => 'medical-record',
            'doctor' => 'doctor',
            'test' => 'test',
            'admin_booking" => "medical-record',
            'hospital' => 'hospital',
        );
        return $buckets[$tableName];
    }

    //根据空间名获取空间链接
    public static function getDomainByBucket($bucket) {
        $domains = array('medical-record' => 'http://mr.file.mingyizhudao.com', //http://7xq93p.com2.z0.glb.qiniucdn.com
            'doctor-cert' => 'http://drcert.file.mingyizhudao.com', //http://7xq939.com2.z0.glb.qiniucdn.com
            'doctor' => 'http://dr.static.mingyizhudao.com', //http://7xtetc.com1.z0.glb.clouddn.com
            'test' => 'http://7xrh6w.com2.z0.glb.qiniucdn.com',
            'hospital' => 'http://hp.static.mingyizhudao.com',);//http://7xti4c.com1.z0.glb.clouddn.com
        return $domains[$bucket];
    }

}
