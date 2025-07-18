<?php 
if(!defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');

set_time_limit(0);

error_log(' ------- START upgrade-funcs ------- ');

if (!defined('ABS_PATH')) {
  define('ABS_PATH', dirname(dirname(__DIR__)) . '/');
}

require_once ABS_PATH . 'oc-load.php';
require_once LIB_PATH . 'osclass/helpers/hErrors.php';

if(!defined('OC_ADMIN_FOLDER')) { define('OC_ADMIN_FOLDER', 'oc-admin'); }
if(!defined('OC_INCLUDES_FOLDER')) { define('OC_INCLUDES_FOLDER', 'oc-includes'); }
if(!defined('OC_CONTENT_FOLDER')) { define('OC_CONTENT_FOLDER', 'oc-content'); }

if (!defined('AUTO_UPGRADE') && UPGRADE_SKIP_DB === false) {
  $error_queries = array();
  
  if (file_exists(osc_lib_path() . 'osclass/installer/struct.sql')) {
    $sql = file_get_contents(osc_lib_path() . 'osclass/installer/struct.sql');

    $conn = DBConnectionClass::newInstance();
    $c_db = $conn->getOsclassDb();
    $comm = new DBCommandClass($c_db);

    $error_queries = $comm->updateDB(str_replace('/*TABLE_PREFIX*/', DB_TABLE_PREFIX, $sql));
  } else {
    $error_queries[0] = true;
  }

  if (Params::getParam('skipdb') == '' && !$error_queries[0]) {
    $skip_db_link = osc_admin_base_url(true) . '?page=upgrade&action=upgrade-funcs&skipdb=true';
    $title  = __('Osclass has some errors');
    $message  = __("We've encountered some problems while updating the database structure. The following queries failed:");
    $message .= '<br/><br/>' . '<span class="upgr-errors">' . implode('<br>', $error_queries[2]) . '</span>';
    $message .= '<br/><br/>' . '<strong class="upgr-notice">' . __("These errors could be false-positive errors. If you're sure that is the case, you can continue with the upgrade or <a href=\"https://forums.osclasspoint.com\">ask in our forums</a>.") . '</strong>';
    $message .= '<br/><br/>' . sprintf('<a href="%s" class="btn btn-submit">Continue with the upgrade</a>', $skip_db_link) . '</strong>';
    osc_die($title, $message);
  }
}

$aMessages = array();
//osc_set_preference('last_version_check', time());

$conn = DBConnectionClass::newInstance();
$c_db = $conn->getOsclassDb();
$comm = new DBCommandClass($c_db);


$current_version = (int)str_replace('.', '', getPreference('version'));   // osc_version()

// UPLOAD ONLY AVAILABLE FROM v3.0.0
if($current_version < 300) {
  throw new \RuntimeException(sprintf('Unsupported Osclass version for upgrade: "%s"', getPreference('version')));
}

if($current_version < 310) {
  $comm->query(sprintf('ALTER TABLE %st_pages ADD  `s_meta` TEXT NULL', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_pages ADD  `b_link` TINYINT(1) NOT NULL DEFAULT 1', DB_TABLE_PREFIX));
  $comm->query(sprintf("UPDATE %st_alerts SET dt_date = '%s' ", DB_TABLE_PREFIX, date('Y-m-d H:i:s')));

  // remove files moved to controller folder
  @unlink(osc_base_path() . 'ajax.php');
  @unlink(osc_base_path() . 'contact.php');
  @unlink(osc_base_path() . 'custom.php');
  @unlink(osc_base_path() . 'item.php');
  @unlink(osc_base_path() . 'language.php');
  @unlink(osc_base_path() . 'login.php');
  @unlink(osc_base_path() . 'main.php');
  @unlink(osc_base_path() . 'page.php');
  @unlink(osc_base_path() . 'register.php');
  @unlink(osc_base_path() . 'search.php');
  @unlink(osc_base_path() . 'user-non-secure.php');
  @unlink(osc_base_path() . 'user.php');
  @unlink(osc_base_path() . 'readme.php');
  @unlink(osc_lib_path() . 'osclass/plugins.php');
  @unlink(osc_lib_path() . 'osclass/feeds.php');

  $comm->query(sprintf('UPDATE %st_user t, (SELECT pk_i_id FROM %st_user) t1 SET t.s_username = t1.pk_i_id WHERE t.pk_i_id = t1.pk_i_id', DB_TABLE_PREFIX, DB_TABLE_PREFIX));
  osc_set_preference('username_blacklist', 'admin,user');
  osc_set_preference('rewrite_user_change_username', 'username/change');
  osc_set_preference('csrf_name', 'CSRF'.mt_rand(0, mt_getrandmax()));

  if(!@mkdir(osc_uploads_path() . 'page-images') && ! is_dir(osc_uploads_path() . 'page-images')) {
    throw new \RuntimeException(sprintf('Directory "%s" was not created', osc_uploads_path() . 'page-images'));
  }
  $updated_version = 310;
}

if($current_version < 320) {
  osc_set_preference('mailserver_mail_from');
  osc_set_preference('mailserver_name_from');
  osc_set_preference('seo_url_search_prefix');

  $comm->query(sprintf('ALTER TABLE %st_category ADD `b_price_enabled` TINYINT(1) NOT NULL DEFAULT 1', DB_TABLE_PREFIX));

  osc_set_preference('subdomain_type');
  osc_set_preference('subdomain_host');
  // email_new_admin
  $comm->query(sprintf("INSERT INTO %st_pages (s_internal_name, b_indelible, dt_pub_date) VALUES ('email_new_admin', 1, '%s' )", DB_TABLE_PREFIX, date('Y-m-d H:i:s')));
  $comm->query(sprintf("INSERT INTO %st_pages_description (fk_i_pages_id, fk_c_locale_code, s_title, s_text) VALUES (%d, 'en_US', '{WEB_TITLE} - Success creating admin account!', '<p>Hi {ADMIN_NAME},</p><p>The admin of {WEB_LINK} has created an account for you,</p><ul><li>Username: {USERNAME}</li><li>Password: {PASSWORD}</li></ul><p>You can access the admin panel here {WEB_ADMIN_LINK}.</p><p>Thank you!</p><p>Regards,</p>')", DB_TABLE_PREFIX, $comm->insertedId()));

  osc_set_preference('warn_expiration', '0', 'osclass', 'INTEGER');

  $comm->query(sprintf("INSERT INTO %st_pages (s_internal_name, b_indelible, dt_pub_date) VALUES ('email_warn_expiration', 1, '%s' )", DB_TABLE_PREFIX, date('Y-m-d H:i:s')));
  $comm->query(sprintf("INSERT INTO %st_pages_description (fk_i_pages_id, fk_c_locale_code, s_title, s_text) VALUES (%d, 'en_US', '{WEB_TITLE} - Your ad is about to expire', '<p>Hi {USER_NAME},</p><p>Your listing <a href=\"{ITEM_URL}\">{ITEM_TITLE}</a> is about to expire at {WEB_LINK}.')", DB_TABLE_PREFIX, $comm->insertedId()));

  osc_set_preference('force_aspect_image', '0', 'osclass', 'BOOLEAN');
  $updated_version = 320;
}

if($current_version < 321) {
  if(function_exists('osc_calculate_location_slug')) {
    osc_calculate_location_slug(osc_subdomain_type());
  }
  $updated_version = 321;
}

if($current_version < 330) {
  if(!@mkdir(osc_content_path() . 'uploads/temp/') && ! is_dir(osc_content_path() . 'uploads/temp/')) {
    throw new \RuntimeException(sprintf('Directory "%s" was not created', osc_content_path() . 'uploads/temp/'));
  }
  
  $concurrentDirectory = osc_content_path() . 'downloads/oc-temp/';
  if(!@mkdir($concurrentDirectory) && !is_dir($concurrentDirectory)) {
    throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
  }
  
  unset($concurrentDirectory);
  @unlink(osc_lib_path() . 'osclass/classes/Watermark.php');
  osc_set_preference('title_character_length', '100', 'osclass', 'INTEGER');
  osc_set_preference('description_character_length', '5000', 'osclass', 'INTEGER');
  $updated_version = 330;
}

if($current_version < 340) {
  $comm->query(sprintf('ALTER TABLE `%st_widget` ADD INDEX `idx_s_description` (`s_description`);', DB_TABLE_PREFIX));
  osc_set_preference('force_jpeg', '0', 'osclass', 'BOOLEAN');
  $updated_version = 340;
}

if($current_version < 343) {
  $mAlerts = Alerts::newInstance();
  $aAlerts = $mAlerts->findByType('HOURLY');
  
  foreach ($aAlerts as $alert) {
    $s_search = base64_decode($alert['s_search']);
    if(stripos(strtolower($s_search), 'union select')!==false || stripos(strtolower($s_search), 't_admin')!==false) {
      $mAlerts->delete(array('pk_i_id' => $alert['pk_i_id']));
    } else {
      $mAlerts->update(array('s_search' => $s_search), array('pk_i_id' => $alert['pk_i_id']));
    }
  }
  unset($aAlerts);

  $aAlerts = $mAlerts->findByType('DAILY');
  foreach ($aAlerts as $alert) {
    $s_search = base64_decode($alert['s_search']);
    if(stripos(strtolower($s_search), 'union select')!==false || stripos(strtolower($s_search), 't_admin')!==false) {
      $mAlerts->delete(array('pk_i_id' => $alert['pk_i_id']));
    } else {
      $mAlerts->update(array('s_search' => $s_search), array('pk_i_id' => $alert['pk_i_id']));
    }
  }
  unset($aAlerts);

  $aAlerts = $mAlerts->findByType('WEEKLY');
  foreach ($aAlerts as $alert) {
    $s_search = base64_decode($alert['s_search']);
    if(stripos(strtolower($s_search), 'union select')!==false || stripos(strtolower($s_search), 't_admin')!==false) {
      $mAlerts->delete(array('pk_i_id' => $alert['pk_i_id']));
    } else {
      $mAlerts->update(array('s_search' => $s_search), array('pk_i_id' => $alert['pk_i_id']));
    }
  }
  unset($aAlerts);
  $updated_version = 343;
}

if($current_version < 370) {
  osc_set_preference('recaptcha_version', '1');
  $comm->query(sprintf('ALTER TABLE %st_category_description MODIFY s_slug VARCHAR(255) NOT NULL', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_preference MODIFY s_section VARCHAR(128) NOT NULL', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_preference MODIFY s_name VARCHAR(128) NOT NULL', DB_TABLE_PREFIX));
  $updated_version = 370;
}

if($current_version < 372) {
  osc_delete_preference('recaptcha_version', 'STRING');
  $updated_version = 372;
}

if($current_version < 374) {
  $admin = Admin::newInstance()->findByEmail('demo@demo.com');
  
  if(isset($admin['pk_i_id'])) {
    Admin::newInstance()->deleteByPrimaryKey($admin['pk_i_id']);
  }
  
  $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(ABS_PATH), RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD);
  $objects = iterator_to_array($iterator);
  
  foreach ($objects as $file => $object) {
    try {
      $handle = @fopen($file, 'rb');
      if($handle!==false) {
        $exist = false;
        $text = array("htmlspecialchars(file_get_contents(\$_POST['path']))", '?option&path=$path', 'msdsaa' ,"shell_exec('cat /proc/cpuinfo');", 'PHPTerm', 'lzw_decompress');

        while (($buffer = fgets($handle)) !== false) {
          foreach ($text as $_t) {
            if(strpos($buffer, $_t) !== false) {
              $exist = true;
              break;
            }
          }
        }
        
        fclose($handle);
        
        if($exist && strpos($file, __FILE__) === false) {
          error_log('remove ' . $file);
          @unlink($file);
        }
      }
    } catch (Exception $e) {
      error_log($e);
    }
  }
  $updated_version = 374;
}

if($current_version < 390) {
  osc_delete_preference('marketAllowExternalSources');
  osc_delete_preference('marketURL');
  osc_delete_preference('marketAPIConnect');
  osc_delete_preference('marketCategories');
  osc_delete_preference('marketDataUpdate');
  $updated_version = 390;
}

if($current_version < 400) {
  $comm->query(sprintf('ALTER TABLE %st_item ADD COLUMN s_contact_other VARCHAR(100) NULL AFTER s_contact_email;', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_item ADD COLUMN s_contact_phone VARCHAR(100) NULL AFTER s_contact_email;', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_item ADD COLUMN b_show_phone TINYINT(1) NULL DEFAULT 1 AFTER b_show_email;', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_category ADD COLUMN s_color VARCHAR(20) NULL AFTER s_icon;', DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'best_fit_image', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $updated_version = 400;
}

if($current_version < 401) {
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'search_pattern_method', '', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'enabled_tinymce_items', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $updated_version = 401;
}

if($current_version < 410) {
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'osclasspoint_api_key', '', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'reg_user_can_see_phone', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_country ADD COLUMN s_name_native VARCHAR(80) NULL AFTER s_name;', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_country ADD COLUMN s_phone_code VARCHAR(10) NULL AFTER s_name_native;', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_country ADD COLUMN s_currency VARCHAR(10) NULL AFTER s_phone_code;', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_region ADD COLUMN s_name_native VARCHAR(60) NULL AFTER s_name;', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_city ADD COLUMN s_name_native VARCHAR(60) NULL AFTER s_name;', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_city ADD COLUMN d_coord_lat DECIMAL(20, 10) NULL AFTER b_active;', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_city ADD COLUMN d_coord_long DECIMAL(20, 10) NULL AFTER d_coord_lat;', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_item_location ADD COLUMN s_country_native VARCHAR(80) NULL AFTER s_country;', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_item_location ADD COLUMN s_region_native VARCHAR(60) NULL AFTER s_region;', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_item_location ADD COLUMN s_city_native VARCHAR(60) NULL AFTER s_city;', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_item_location CHANGE d_coord_lat d_coord_lat DECIMAL(20, 10) NULL DEFAULT NULL;', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_item_location CHANGE d_coord_long d_coord_long DECIMAL(20, 10) NULL DEFAULT NULL;', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_locale ADD COLUMN b_locations_native TINYINT(1) NULL DEFAULT 0 AFTER b_enabled_bo;', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_user CHANGE dt_access_date dt_access_date DATETIME NULL;', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_user ADD COLUMN s_country_native VARCHAR(80) NULL AFTER s_country;', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_user ADD COLUMN s_region_native VARCHAR(60) NULL AFTER s_region;', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_user ADD COLUMN s_city_native VARCHAR(60) NULL AFTER s_city;', DB_TABLE_PREFIX));
  $updated_version = 410;
}

if($current_version < 411) {
  $comm->query(sprintf('ALTER TABLE %st_item CHANGE b_show_phone b_show_phone TINYINT(1) NULL DEFAULT 1;', DB_TABLE_PREFIX));
  $updated_version = 411;
}

if($current_version < 420) { 
  if(!@mkdir(osc_uploads_path() . 'user-images/') && !is_dir(osc_uploads_path() . 'user-images/')) {   // user profile pictures dir
    throw new \RuntimeException(sprintf('Directory "%s" was not created', osc_uploads_path() . 'user-images/'));
  }
  
  if(!@mkdir(osc_uploads_path() . 'minify/') && !is_dir(osc_uploads_path() . 'minify/')) {   // user profile pictures dir
    throw new \RuntimeException(sprintf('Directory "%s" was not created', osc_uploads_path() . 'minify/'));
  }
  
  if(file_exists(osc_lib_path() . 'phpmailer') && is_dir(osc_lib_path() . 'phpmailer')) {
    $phpmailer_files = glob(osc_lib_path() . 'phpmailer/*');  
   
    if(count($phpmailer_files) > 0) {
      foreach($phpmailer_files as $fl) {
        if(is_file($fl)) {
          @unlink($fl);
        }
      }
    }
    
    @rmdir(osc_lib_path() . 'phpmailer');
  }

  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'enable_comment_rating', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'item_post_redirect', '', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'enabled_tinymce_users', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'enable_profile_img', '1', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'dimProfileImg', '180x180', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'admindash_widgets_collapsed', '', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'admindash_widgets_hidden', '', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'admindash_columns_hidden', '', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'enabled_renewal_items', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'renewal_update_pub_date', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'renewal_limit', 0, 'INTEGER')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'rewrite_item_renew', 'item/renew', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'structured_data', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'css_merge', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'css_minify', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'js_merge', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'js_minify', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'css_banned_words', 'font,awesome', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'css_banned_pages', '', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'js_banned_words', 'tiny', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'js_banned_pages', '', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'admin_toolbar_front', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'can_deactivate_items', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'rewrite_item_deactivate', 'item/deactivate', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_pages ADD b_index TINYINT(1) NOT NULL DEFAULT 1 AFTER b_link', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_meta_fields ADD i_order INT(3) NOT NULL DEFAULT 0 AFTER b_searchable', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_item_comment ADD i_rating INT(3) NULL AFTER s_body', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_user ADD i_login_fails INT(3) NULL DEFAULT 0 AFTER s_access_ip', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_user ADD dt_login_fail_date DATETIME NULL AFTER i_login_fails', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_user ADD s_profile_img VARCHAR(100) AFTER dt_login_fail_date', DB_TABLE_PREFIX)); 
  $comm->query(sprintf('ALTER TABLE %st_admin ADD s_moderator_access VARCHAR(1000) NULL AFTER b_moderator', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_admin ADD i_login_fails INT(3) NULL DEFAULT 0 AFTER s_moderator_access', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_admin ADD dt_login_fail_date DATETIME NULL AFTER i_login_fails', DB_TABLE_PREFIX));
  $comm->query(sprintf('ALTER TABLE %st_item ADD i_renewed INT(3) NULL DEFAULT 0 AFTER b_show_phone', DB_TABLE_PREFIX));
  $updated_version = 420;
}

if($current_version < 421) { 
  // change backoffice theme if upgrading from different branch
  if(file_exists(osc_base_path() . 'oc-admin/themes/evolution/') && is_dir(osc_base_path() . 'oc-admin/themes/evolution/')) {
    osc_deleteDir(osc_base_path() . 'oc-admin/themes/evolution/');
  }
  $updated_version = 421;
}

if($current_version < 430) { 
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'custom_css', '', 'STRING')", DB_TABLE_PREFIX));
  $updated_version = 430;
}

if($current_version < 440) { 
  $comm->query(sprintf("UPDATE %st_region SET s_name_native = null WHERE s_name_native = '' ", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'custom_html', '', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'breadcrumbs_item_page_title', '1', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'breadcrumbs_item_country', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'breadcrumbs_item_region', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'breadcrumbs_item_city', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'breadcrumbs_item_category', '1', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'breadcrumbs_item_parent_categories', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'breadcrumbs_hide', '', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'breadcrumbs_hide_custom', '', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'widget_data_api', '', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'widget_data_blog', '', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'widget_data_product', '', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'widget_data_update', '', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'market_products_version', '', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'jquery_version', '1', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'admin_color_scheme', '', 'STRING')", DB_TABLE_PREFIX));
  $updated_version = 440;
}

if($current_version < 800) { 
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'widget_data_product_updates', '', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("ALTER TABLE %st_item_description ENGINE = InnoDB", DB_TABLE_PREFIX));
  $comm->query(sprintf("ALTER TABLE %st_meta_fields CHANGE e_type e_type ENUM('TEXT','NUMBER','TEL','EMAIL','COLOR','TEXTAREA','DROPDOWN','RADIO','CHECKBOX','URL','DATE','DATEINTERVAL') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'TEXT'", DB_TABLE_PREFIX));
  $updated_version = 800;
}

if($current_version < 801) { 
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'update_include_occontent', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'item_contact_form_disabled', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'web_contact_form_disabled', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("ALTER TABLE %st_locale ADD COLUMN b_rtl TINYINT(1) NULL DEFAULT 0 AFTER b_locations_native;", DB_TABLE_PREFIX));
  $comm->query(sprintf("ALTER TABLE %st_user CHANGE s_pass_ip s_pass_ip VARCHAR(64) NULL;", DB_TABLE_PREFIX));
  $comm->query(sprintf("ALTER TABLE %st_user CHANGE s_access_ip s_access_ip VARCHAR(64) NOT NULL DEFAULT '';", DB_TABLE_PREFIX));
  $comm->query(sprintf("ALTER TABLE %st_ban_rule CHANGE s_ip s_ip VARCHAR(64) NOT NULL DEFAULT '';", DB_TABLE_PREFIX));
  $comm->query(sprintf("ALTER TABLE %st_log CHANGE s_ip s_ip VARCHAR(64) NULL;", DB_TABLE_PREFIX));
  $updated_version = 801;
}

if($current_version < 802) { 
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'canvas_background', 'white', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'hide_generator', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'username_generator', 'ID', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("ALTER TABLE %st_ban_rule ADD COLUMN dt_date DATETIME NULL AFTER s_email;", DB_TABLE_PREFIX));
  $comm->query(sprintf("ALTER TABLE %st_cron CHANGE e_type e_type ENUM('INSTANT','MINUTELY','HOURLY','DAILY','WEEKLY','MONTHLY','YEARLY','CUSTOM') NOT NULL;", DB_TABLE_PREFIX));
  $comm->query("SET SQL_MODE='ALLOW_INVALID_DATES';");
  $comm->query(sprintf("INSERT INTO %st_cron VALUES ('MINUTELY', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),('MONTHLY', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),('YEARLY', '0000-00-00 00:00:00', '0000-00-00 00:00:00');", DB_TABLE_PREFIX));
  $updated_version = 802;
}

if($current_version < 810) { 
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'enable_comment_reply', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'enable_comment_reply_rating', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'comment_reply_user_type', '', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'notify_new_comment_reply', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'notify_new_comment_reply_user', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'comment_rating_limit', '1', 'INTEGER')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'latest_searches_restriction', '0', 'INTEGER')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'latest_searches_words', '', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'subdomain_landing', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'subdomain_redirect', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'subdomain_restricted_ids', '', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'subdomain_language_slug_type', '', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'locale_to_base_url_enabled', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'locale_to_base_url_type', '', 'STRING')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'item_mark_disable', '0', 'BOOLEAN')", DB_TABLE_PREFIX));

  $comm->query(sprintf("ALTER TABLE %st_item_comment ADD COLUMN fk_i_reply_id INT(10) UNSIGNED NULL AFTER fk_i_user_id;", DB_TABLE_PREFIX));
  $comm->query(sprintf("ALTER TABLE %st_pages ADD COLUMN i_visibility TINYINT(1) NULL DEFAULT 0 AFTER b_index;", DB_TABLE_PREFIX));

  @unlink(osc_base_path() . OC_ADMIN_FOLDER . '/controller/settings/currencies.php');
  @unlink(osc_base_path() . OC_ADMIN_FOLDER . '/themes/omega/settings/currencies.php');
  @unlink(osc_base_path() . OC_ADMIN_FOLDER . '/themes/omega/settings/currency_form.php');

  @unlink(osc_base_path() . OC_ADMIN_FOLDER . '/controller/settings/locations.php');
  @unlink(osc_base_path() . OC_ADMIN_FOLDER . '/themes/omega/settings/locations.php');

  // Remove language less folder from omega as it is not used
  if(file_exists(osc_base_path() . OC_ADMIN_FOLDER  . '/themes/omega/less/') && is_dir(osc_base_path() . OC_ADMIN_FOLDER  . '/themes/omega/less/')) {
    osc_deleteDir(osc_base_path() . OC_ADMIN_FOLDER  . '/themes/omega/less/');
  }
  
  // Remove language folder of omega, as Core already contains it
  if(file_exists(osc_base_path() . OC_ADMIN_FOLDER  . '/themes/omega/languages/en_US/') && is_dir(osc_base_path() . OC_ADMIN_FOLDER  . '/themes/omega/languages/en_US/')) {
    osc_deleteDir(osc_base_path() . OC_ADMIN_FOLDER  . '/themes/omega/languages/en_US/');
  }
  
  // Remove class related to metadata DB (multisite)
  @unlink(osc_base_path() . OC_INCLUDES_FOLDER . '/osclass/model/SiteInfo.php');
  $updated_version = 810;
}

if($current_version < 811) { 
  // No changes
  $updated_version = 811;
}

if($current_version < 812) { 
  osc_set_preference('css_banned_pages', 'item-item_add,item-item_edit');
  osc_set_preference('js_banned_pages', 'item-item_add,item-item_edit');
  osc_set_preference('css_banned_words', 'font,awesome,tiny,fineuploader');
  osc_set_preference('js_banned_words', 'tiny,fineuploader');

  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'gen_hreflang_tags', '0', 'BOOLEAN')", DB_TABLE_PREFIX));
  $comm->query(sprintf("INSERT INTO %st_preference VALUES ('osclass', 'always_gen_canonical', '0', 'BOOLEAN')", DB_TABLE_PREFIX));

  $updated_version = 812;
}

// Resolve current version
// Critical for auto-upgrade, where constant OSCLASS_VERSION keeps current installation version, as new file is being downloaded
$known_version = (int)str_replace('.', '', OSCLASS_VERSION);

if($updated_version >= $known_version) {
  $current_version = $updated_version;
} else {
  $current_version = $known_version;
}
  
osc_changeVersionTo($current_version);

// Make sure Omega is selected as admin theme
osc_set_preference('admin_theme', 'omega');

// Disable maintenance mode
@unlink(ABS_PATH . '.maintenance');


if(!defined('IS_AJAX') || !IS_AJAX) {
  if(empty($aMessages)) {
    osc_add_flash_ok_message(_m('Osclass has been updated successfully. <a href="https://forums.osclasspoint.com">Need more help?</a>'), 'admin');
    echo '<script type="text/javascript"> window.location = "'.osc_admin_base_url(true).'?page=tools&action=version"; </script>';
  } else {
    echo '<div class="well ui-rounded-corners separate-top-medium">';
    echo '<p>'.__('Osclass updated correctly').'</p>';
    echo '<p>'.__('Osclass has been updated successfully. <a href="https://forums.osclasspoint.com">Need more help?</a>').'</p>';
    foreach ($aMessages as $msg) {
      echo '<p>' . $msg . '</p>';
    }
    echo '</div>';
  }
}
