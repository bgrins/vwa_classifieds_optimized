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


$shift_seconds = 60;
$shift_seconds_minutely = 20;
$d_now = date('Y-m-d H:i:s');
$i_now = strtotime($d_now);
$i_now_truncated = strtotime(date('Y-m-d H:i:00'));

if (!defined('CLI')) {
  define('CLI', PHP_SAPI === 'cli');
}


// Intitiate theme to get cron functions defined by theme - os812
WebThemes::newInstance();


// Minutely crons
$cron = Cron::newInstance()->getCronByType('MINUTELY');
if(is_array($cron)) {
  $i_next = strtotime($cron['d_next_exec']);

  if((CLI && (Params::getParam('cron-type') === 'minutely')) || ((($i_now - $i_next + $shift_seconds_minutely) >= 0) && !CLI)) {
    // update the next execution time in t_cron
    $d_next = date('Y-m-d H:i:s', $i_now_truncated + (5 * 60));  // once per 5 minutes
    Cron::newInstance()->update(array('d_last_exec' => $d_now, 'd_next_exec' => $d_next), array('e_type' => 'MINUTELY'));
    osc_run_hook('cron_minutely');
  }
}


// Hourly crons
$cron = Cron::newInstance()->getCronByType('HOURLY');
if(is_array($cron)) {
  $i_next = strtotime($cron['d_next_exec']);

  if((CLI && (Params::getParam('cron-type') === 'hourly')) || ((($i_now - $i_next + $shift_seconds) >= 0) && !CLI)) {
    // update the next execution time in t_cron
    $d_next = date('Y-m-d H:i:s', $i_now_truncated + 3600);
    Cron::newInstance()->update(array('d_last_exec' => $d_now, 'd_next_exec' => $d_next), array('e_type' => 'HOURLY'));
    
    osc_runAlert('HOURLY', $cron['d_last_exec']);
    
    // Run cron AFTER updating the next execution time to avoid double run of cron
    $purge = osc_purge_latest_searches();
    if( $purge === 'hour' ) {
      LatestSearches::newInstance()->purgeDate( date( 'Y-m-d H:i:s', time() - 3600 ) );
    } else if( !in_array($purge, array('forever', 'day', 'week')) ) {
      LatestSearches::newInstance()->purgeNumber($purge);
    }
    osc_update_location_stats(true, 'auto');

    // WARN EXPIRATION EACH HOUR (COMMENT TO DISABLE)
    // NOTE: IF THIS IS ENABLE, SAME CODE SHOULD BE DISABLE ON CRON DAILY
    if(is_numeric(osc_warn_expiration()) && osc_warn_expiration()>0) {
      $items = Item::newInstance()->findByHourExpiration(24*osc_warn_expiration());
      foreach($items as $item) {
        osc_run_hook('hook_email_warn_expiration', $item);
      }
    }

    $qqprefixes = array('qqfile_*', 'auto_qqfile_*');
    foreach ($qqprefixes as $qqprefix) {
      $qqfiles = glob(osc_content_path().'uploads/temp/'.$qqprefix);
      if(is_array($qqfiles)) {
        foreach($qqfiles as $qqfile) {
          if((time()-filemtime($qqfile))>(2*3600)) {
            @unlink($qqfile);
          }
        }
      }
    }

    osc_run_hook('cron_hourly');
  }
}


// Daily crons
$cron = Cron::newInstance()->getCronByType('DAILY');
if( is_array($cron) ) {
  $i_next = strtotime($cron['d_next_exec']);

  if( (CLI && (Params::getParam('cron-type') === 'daily')) || ((($i_now - $i_next + $shift_seconds) >= 0) && !CLI) ) {
    // update the next execution time in t_cron
    $d_next = date('Y-m-d H:i:s', $i_now_truncated + (24 * 3600));
    Cron::newInstance()->update(array('d_last_exec' => $d_now, 'd_next_exec' => $d_next), array('e_type' => 'DAILY'));

    // upgrade osclass if there are new updates
    osc_do_auto_upgrade();

    osc_runAlert('DAILY', $cron['d_last_exec']);

    // Run cron AFTER updating the next execution time to avoid double run of cron
    $purge = osc_purge_latest_searches();
    if( $purge === 'day' ) {
      LatestSearches::newInstance()->purgeDate( date( 'Y-m-d H:i:s', time() - ( 24 * 3600) ) );
    }
    osc_update_cat_stats();

    // WARN EXPIRATION EACH DAY (UNCOMMENT TO ENABLE)
    // NOTE: IF THIS IS ENABLE, SAME CODE SHOULD BE DISABLE ON CRON HOURLY
    /*if(is_numeric(osc_warn_expiration()) && osc_warn_expiration()>0) {
      $items = Item::newInstance()->findByDayExpiration(osc_warn_expiration());
      foreach($items as $item) {
        osc_run_hook('hook_email_warn_expiration', $item);
      }
    }*/

    osc_run_hook('cron_daily');
  }
}


// Weekly crons
$cron = Cron::newInstance()->getCronByType('WEEKLY');
if(is_array($cron)) {
  $i_next = strtotime($cron['d_next_exec']);

  if( (CLI && (Params::getParam('cron-type') === 'weekly')) || ((($i_now - $i_next + $shift_seconds) >= 0) && !CLI) ) {
    // update the next execution time in t_cron
    $d_next = date('Y-m-d H:i:s', $i_now_truncated + (7 * 24 * 3600));
    Cron::newInstance()->update(array('d_last_exec' => $d_now, 'd_next_exec' => $d_next), array('e_type' => 'WEEKLY'));
    
    osc_runAlert('WEEKLY', $cron['d_last_exec']);
    
    // Run cron AFTER updating the next execution time to avoid double run of cron
    $purge = osc_purge_latest_searches();
    if( $purge === 'week' ) {
      LatestSearches::newInstance()->purgeDate( date( 'Y-m-d H:i:s', time() - ( 7 * 24 * 3600) ) );
    }
    osc_run_hook('cron_weekly');
  }
}


// Monthly crons
$cron = Cron::newInstance()->getCronByType('MONTHLY');
if(is_array($cron)) {
  $i_next = strtotime($cron['d_next_exec']);

  if((CLI && (Params::getParam('cron-type') === 'monthly')) || ((($i_now - $i_next + $shift_seconds) >= 0) && !CLI)) {
    // update the next execution time in t_cron
    //$d_next = date('Y-m-d H:i:s', $i_now_truncated + (30 * 24 * 3600));
    $d_next = date('Y-m-d H:i:s', strtotime('next month', $i_now_truncated));

    Cron::newInstance()->update(array('d_last_exec' => $d_now, 'd_next_exec' => $d_next), array('e_type' => 'MONTHLY'));
    osc_run_hook('cron_monthly');
  }
}


// Yearly crons
$cron = Cron::newInstance()->getCronByType('YEARLY');
if(is_array($cron)) {
  $i_next = strtotime($cron['d_next_exec']);

  if((CLI && (Params::getParam('cron-type') === 'yearly')) || ((($i_now - $i_next + $shift_seconds) >= 0) && !CLI)) {
    // update the next execution time in t_cron
    $d_next = date('Y-m-d H:i:s', strtotime('+1 year', $i_now_truncated));

    Cron::newInstance()->update(array('d_last_exec' => $d_now, 'd_next_exec' => $d_next), array('e_type' => 'YEARLY'));
    osc_run_hook('cron_yearly');
  }
}


osc_run_hook('cron');