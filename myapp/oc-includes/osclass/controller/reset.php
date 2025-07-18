<?php
if(!defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');


class CWebMyCustomController extends BaseModel
{
  public function __construct()
  {
    parent::__construct();
    //specific things for this class
    osc_run_hook( 'init_custom' );
  }

  public function doModel() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Check if post token is equal to the session token
        if ( !isset($_POST['token']) || $_POST['token'] !== getenv('RESET_TOKEN') ) {
            echo "Error: Invalid token";
            return;
        }
        
        if ( defined( 'DB_HOST' ) && $dbHost == null ) {
            $dbHost = osc_db_host();
        }
        if ( defined( 'DB_USER' ) && $dbUser == null ) {
            $dbUser = osc_db_user();
        }
        if ( defined( 'DB_PASSWORD' ) && $dbPassword == null ) {
            $dbPassword = osc_db_password();
        }
        if ( defined( 'DB_NAME' ) && $dbName == null ) {
            $dbName = osc_db_name();
        }
        // Path to the SQL file
        $sqlFilePath = '/usr/src/myapp/classifieds_restore.sql';
        
        // Command to execute
        $command = "mysql -h {$dbHost} -u {$dbUser} --password='{$dbPassword}' {$dbName} < {$sqlFilePath}";
        echo "Command: " . $command;
        
        // Execute the command
        exec($command, $output, $return_var);
        
        // Check the result
        if ($return_var === 0) {
            echo "SQL file executed successfully.\n";
        } else {
            echo "Error occurred: " . implode("\n", $output) . "\n";
        }
    } else {
        // Handle non-POST requests
        echo "<h1>Error: This page only handles POST requests</h1>";
    }
  }

  /**
   * @param $file
   *
   * @return mixed|void
   */
  public function doView( $file )
  {
    osc_run_hook( 'before_html' );
    osc_current_web_theme_path($file);
    Session::newInstance()->_clearVariables();
    osc_run_hook( 'after_html' );
  }
}

/* file end: ./reset.php */
