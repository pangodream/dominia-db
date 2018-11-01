<?php
/**
 * Created by Pangodream.
 * Date: 01/11/2018
 * Time: 17:56
 */

namespace DominiaDb;


class DDL
{
    public function getRecordDDL(){
        $sql='
                DROP TABLE IF EXISTS `dmn_feed`;
                CREATE TABLE IF NOT EXISTS `dmn_feed` (
                  `feed_id` int(11) NOT NULL,
                  `file_hash` char(32) NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ';
        return $sql;
    }
    public function getFeedDDL(){
        $sql='
                DROP TABLE IF EXISTS `dmn_record`;
                CREATE TABLE IF NOT EXISTS `dmn_record` (
                  `domain_name` varchar(230) NOT NULL,
                  `reg_date` date NOT NULL,
                  `feed_id` int(11) NOT NULL,
                PRIMARY KEY (`reg_date`,`domain_name`),
                KEY `idx_record_domain` (`domain_name`),
                KEY `idx_record_reg_date` (`reg_date`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ';
        return $sql;
    }
}