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
 * This class takes items descriptions and generates a RSS feed from that information.
 * @author Osclass
 */
class RSSFeed {
  private $title;
  private $link;
  private $description;
  private $items;

  public function __construct() {
    $this->items = array();
  }

  /**
   * @param $title
   */
  public function setTitle( $title ) {
    $this->title = $title;
  }

  /**
   * @param $link
   */
  public function setLink( $link ) {
    $this->link = $link;
  }

  /**
   * @param $description
   */
  public function setDescription( $description ) {
    $this->description = $description;
  }

  /**
   * @param $item
   */
  public function addItem( $item ) {
    $this->items[] = $item;
  }

  public function dumpXML() {
    echo '<?xml version="1.0" encoding="UTF-8"?>', PHP_EOL;
    echo '<rss version="2.0">', PHP_EOL;
    echo '<channel>', PHP_EOL;
    echo '<title>', $this->title, '</title>', PHP_EOL;
    echo '<link>', $this->link, '</link>', PHP_EOL;
    echo '<description>', $this->description, '</description>', PHP_EOL;
    foreach ($this->items as $item) {
      echo '<item>', PHP_EOL;
      echo '<title><![CDATA[', $item['title'], ']]></title>', PHP_EOL;
      echo '<link>', $item['link'], '</link>', PHP_EOL;
      echo '<guid>', $item['link'], '</guid>', PHP_EOL;

      echo '<description><![CDATA[';
      if(@$item['image']) {
        echo '<a href="'.$item['image']['link'].'" title="'.$item['image']['title'].'" rel="nofollow">';
        echo '<img style="float:left;border:0px;" src="'.$item['image']['url'].'" alt="'.$item['image']['title'].'"/> </a>';
      }
      echo $item['description'], ']]>';
      echo '</description>', PHP_EOL;

      echo '<country><![CDATA[', $item['country'], ']]></country>', PHP_EOL;
      echo '<region><![CDATA[', $item['region'], ']]></region>', PHP_EOL;
      echo '<city><![CDATA[', $item['city'], ']]></city>', PHP_EOL;
      echo '<cityArea><![CDATA[', $item['city_area'], ']]></cityArea>', PHP_EOL;
      echo '<category><![CDATA[', $item['category'], ']]></category>', PHP_EOL;

      echo '<pubDate>', date('r',strtotime($item['dt_pub_date'])) , '</pubDate>', PHP_EOL;
      
      echo '</item>', PHP_EOL;
    }
    echo '</channel>', PHP_EOL;
    echo '</rss>', PHP_EOL;
  }
}