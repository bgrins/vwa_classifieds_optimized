<?php
if(!defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');

/*
 * Copyright 2014 Osclass
 * Copyright 2021 Osclass by OsclassPoint.com
 *
 * Osclass maintained & developed by OsclassPoint.com
 * You may not use this file except in compliance with the License.
 * You may download copy of Osclass at
 *
 *     https://osclass-classifieds.com/download
 *
 * Do not edit or add to this file if you wish to upgrade Osclass to newer
 * versions in the future. Software is distributed on an "AS IS" basis, without
 * warranties or conditions of any kind, either express or implied. Do not remove
 * this NOTICE section as it contains license information and copyrights.
 */


/**
 * Model database for Currency table
 *
 * @package Osclass
 * @subpackage Model
 * @since unknown
 */
class Currency extends DAO
{
  /**
   * It references to self object: Currency.
   * It is used as a singleton
   *
   * @access private
   * @since unknown
   * @var Currency
   */
  private static $instance;
  private static $_currencies;

  /**
   * It creates a new Currency object class ir if it has been created
   * before, it return the previous object
   *
   * @access public
   * @since unknown
   * @return Currency
   */
  public static function newInstance()
  {
    if( !self::$instance instanceof self ) {
      self::$instance = new self;
    }
    return self::$instance;
  }

  /**
   * Set data related to t_currency table
   */
  public function __construct()
  {
    parent::__construct();
    $this->setTableName('t_currency');
    $this->setPrimaryKey('pk_c_code');
    $this->setFields(array('pk_c_code', 's_name', 's_description', 'b_enabled'));
  }

  /**
   * @param string $value
   *
   * @return bool|mixed
   */
  public function findByPrimaryKey( $value )
  {
    if ( isset( self::$_currencies[ $value ] ) ) {
      return self::$_currencies[ $value ];
    }

    if(trim($value) == '') {
      return false;
    }

    $this->dao->select($this->fields);
    $this->dao->from($this->getTableName());
    $this->dao->where($this->getPrimaryKey(), $value);
    $result = $this->dao->get();

    if( $result === false ) {
      return false;
    }

    if( $result->numRows() !== 1 ) {
      return false;
    }

    self::$_currencies[ $value ] = $result->row();

    return self::$_currencies[ $value ];
  }

}

/* file end: ./oc-includes/osclass/model/Currency.php */