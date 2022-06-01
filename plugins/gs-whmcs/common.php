<?php if ( defined('IN_GS') === false ) { die( 'You cannot lod this file directly!' ); }
/**
 * Plugin Name: WHMCS Connector
 * Description: Connects a WHMCS installation to GetSimple to allow the fetching of information.
 * Version: 1.0.0-alpha
 * Author: John Stray
 */

# Settings file location
define( 'WHMCSSETTINGS', GSDATAOTHERPATH . 'whmcsconnector.xml' );

/**---------------------------------------------------------------------------------------------------------------------
 * main()
 * The main entry point for the GS backend. Will load the settings management functions and page.
 * 
 * @return void
 */
function gs_whmcs_main() : void
{
    // Load the settings page
    require_once( WHMCSPATH . 'settings.php' );
    gs_whmcs_settings();
}

/**---------------------------------------------------------------------------------------------------------------------
 * filter()
 * Filters the page output content, looking for special tags that will then be replaced by a function's output
 * 
 * @param string $content - The input page content to be filtered
 * @return string $content - Filtered content with special tags replaced with plugin content
 */
function gs_whmcs_filter( string $content ) : string
{
    $matches = array();
    preg_match_all( '/(?<=\{w\{)(.*?)(?=\}\})/', $content, $matches, PREG_OFFSET_CAPTURE );
    
    if ( $matches != false && count($matches) > 0 ) {
        $difference = 0;
        foreach ( $matches[0] as $match ) {
        
            // Figure out the action and parameters
            $action = explode('|', $match[0]);
            $function = $action[0];
            $arguments = explode(',', $action[1]);
            
            // What API functions are supported?
            $apiFunctionFiles = glob(__DIR__ . '/functions/*.php');
            $apiFunctions = array();
            foreach ( $apiFunctionFiles as $file ) {
                if ( is_file($file) && is_readable($file) ) {
                    $apiFunctions[] = basename($file, '.php');
                }
            }
            
            // Bring in the API
            require_once( 'api.php' );
            
            // Build the replacement content
            $replace = ''; # NOTE: Functions must fill this variable
            if ( in_array($function, $apiFunctions) ) {
                include( 'functions/' . $function . '.php' );
            }
            
            // Calculate where the replacement will be
            $string = '{w{' . $match[0] . '}}';
            $length = strlen($string);
            $offset = ($match[1] - 3) + $difference;
            $diff = strlen($replace) - $length;
            $difference = $difference + $diff;
            
            // Do the actual replacement
            $content = substr_replace( $content, $replace, $offset, $length );
        }
    }
    
    return $content;
}

/**---------------------------------------------------------------------------------------------------------------------
 * testConnect()
 * Tests the connectivity to the configured WHMCS installation and if successful, returns some information about it.
 * 
 * @return array $testConnect - Array containing connectivity status and whmcs information.
 */
function gs_whmcs_testConnect() : array
{
    $testConnect = array( 'result' => "failed" );
    
    // We'll require the API for this, let's bring that in
    require_once( 'api.php' );
    
    // Test for Version config value
    $apiCallResult = gs_whmcs_api( 'GetConfigurationValue', ['setting' => "Version"] );
    if ( is_array($apiCallResult) && count($apiCallResult) > 0 ) {
        if ( $apiCallResult['result'] == "success" && isset($apiCallResult['value']) )
            $testConnect['result'] = "success";
            $testConnect['whmcs']['version'] = $apiCallResult['value'];
        }
    }
    
    // Test for SystemURL Config Value
    $apiCallResult = gs_whmcs_api( 'GetConfigurationValue', ['setting' => "SystemURL"] );
    if ( is_array($apiCallResult) && count($apiCallResult) > 0 ) {
        if ( $apiCallResult['result'] == "success" && isset($apiCallResult['value']) ) {
            $testConnect['result'] = "success";
            $testConnect['whmcs']['system_url'] = $apiCallResult['value'];
        }
    }
    
    // Test for MaintenanceMode config value
    $apiCallResult = gs_whmcs_api( 'GetConfigurationValue', ['setting' => "MaintenanceMode"] );
    if ( is_array($apiCallResult) && count($apiCallResult) > 0 ) {
        if ( $apiCallResult['result'] == "success" && isset($apiCallResult['value']) ) {
            $testConnect['result'] = "success";
            $testConnect['whmcs']['maintenance_mode'] = $apiCallResult['value'];
        }
    }
    
    // Test for CompanyName config value
    $apiCallResult = gs_whmcs_api( 'GetConfigurationValue', ['setting' => "CompanyName"] );
    if ( is_array($apiCallResult) && count($apiCallResult) > 0 ) {
        if ( $apiCallResult['result'] == "success" && isset($apiCallResult['value']) ) {
            $testConnect['result'] = "success";
            $testConnect['whmcs']['company_name'] = $apiCallResult['value'];
        }
    }
    
    // Test for LogoURL Config Value
    $apiCallResult = gs_whmcs_api( 'GetConfigurationValue', ['setting' => "LogoURL"] );
    if ( is_array($apiCallResult) && count($apiCallResult) > 0 ) {
        if ( $apiCallResult['result'] == "success" && isset($apiCallResult['value']) ) {
            $testConnect['result'] = "success";
            $testConnect['whmcs']['logo_url'] = $apiCallResult['value'];
        }
    }
    
    return $testConnect;
}

/**---------------------------------------------------------------------------------------------------------------------
 * displayMessage()
 * Displays a Success/Error/Warning/Info message only on the GS backend
 * 
 * @param string $message - The message text to be displayed
 * @param string $type - Message type, one of ['info', 'success', 'warn', 'error']
 * @param bool $close - Enables a close button to be added to the message
 * @return void
 */
function gs_whmcs_displayMessage( string $message = '???', string $type = 'info', bool $close = false) : void
{
    if(is_frontend() == false) {
        $removeit = ($close ? ".removeit()" : "");
        $type = ucfirst($type);
        if($close == false) {
            $message = $message . ' <a href="#" onclick="clearNotify();" style="float:right;">X</a>';
        }
        echo "<script>notify".$type."('".$message."').popit()".$removeit.";</script>";
    }
}
