/*
SQLyog Ultimate v11.24 (32 bit)
MySQL - 5.6.16-log 
*********************************************************************
*/
/*!40101 SET NAMES utf8 */;

create table `file_test` (
	`id` int (11),
	`uid` char (96),
	`file_ext` varchar (30),
	`mime_type` varchar (60),
	`file_url` varchar (765),
	`file_size` int (11),
	`thumbnail_name` varchar (120),
	`thumbnail_url` varchar (765),
	`base_url` varchar (765),
	`has_remote` tinyint (4),
	`remote_domain` varchar (765),
	`remote_file_key` varchar (765),
	`display_order` int (11),
	`date_created` timestamp ,
	`date_updated` timestamp ,
	`date_deleted` timestamp 
); 
