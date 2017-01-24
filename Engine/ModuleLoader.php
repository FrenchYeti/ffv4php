<?php

namespace FFV;

class ModuleLoader
{	
	/**
	 * Parse config file and fill Module object
	 * 
	 */
	public static function readConfigFile( $module_obj)
	{
		// Test if config file exists
		if( !file_exists( $module_obj->module_path.'/Config.xml')){
			echo '[MODULE_ERROR] ** Loader ** Config file (Config.xml) not exists in : '.$module_obj->module_path.'<br>';	
			return false;
		}
		
		// Load config file, and parse with XPath 		
		$dom = new DOMDocument(); 
		$dom->load( $module_obj->module_path.'/Config.xml');
		
		// Test if config file is valid with XSD
		if( $dom->schemaValidate( FFE_MODULE_DIRNAME.'/configSchema.xsd') === false){   
            echo '[MODULE_ERROR] ** Loader ** Config file exists but is not valid. <br>';
            return false;                    
        }else{
        	
        	$xp = new DOMXPath( $dom);
        	$modNode = $dom->getElementsByTagName('module')->item(0);
        	
        	$module_obj->package_name = $xp->evaluate( 'package_name', $modNode)->item(0)->nodeValue;    	
        	$module_obj->authors = $xp->evaluate( 'authors', $modNode)->item(0)->nodeValue;
        	$module_obj->version = $xp->evaluate( 'version', $modNode)->item(0)->nodeValue;
        	$module_obj->language = $xp->evaluate( 'language', $modNode)->item(0)->nodeValue;
        	$module_obj->setGenerator( $xp->evaluate( 'generator', $modNode)->item(0)->nodeValue);
        	$module_obj->file_extension = $xp->evaluate( 'file_extension', $modNode)->item(0)->nodeValue;
        	
        	$filesNodes = $xp->evaluate( 'files_required/file', $modNode);
        	foreach( $filesNodes as $files)
        	{
        		$module_obj->addFile( $files->nodeValue);
        	}
        	
        	$module_obj->tpl_init = $xp->evaluate( 'methods/init', $modNode)->item(0)->nodeValue;
        	
        	$module_obj->tpl_controller[Module::TPL_CTRL_TRUE] = $xp->evaluate( 'methods/controllers/true', $modNode)->item(0)->nodeValue;
        	$module_obj->tpl_controller[Module::TPL_CTRL_FALSE] = $xp->evaluate( 'methods/controllers/false', $modNode)->item(0)->nodeValue;
        	$module_obj->tpl_controller[Module::TPL_CTRL_END] = $xp->evaluate( 'methods/controllers/end', $modNode)->item(0)->nodeValue;
        	$module_obj->tpl_controller[Module::TPL_CTRL_EXIT] = $xp->evaluate( 'methods/controllers/exit', $modNode)->item(0)->nodeValue;
        	$module_obj->tpl_controller[Module::TPL_CTRL_CALL] = $xp->evaluate( 'methods/controllers/call', $modNode)->item(0)->nodeValue;
        	
        	$module_obj->tpl_operation[Module::TPL_OPE_FORMAT] = $xp->evaluate( 'methods/operations/format', $modNode)->item(0)->nodeValue;
        	$module_obj->tpl_operation[Module::TPL_OPE_VALUE] = $xp->evaluate( 'methods/operations/value', $modNode)->item(0)->nodeValue;
        	
            unset( $dom, $xp, $filesNodes, $modNode);
            return true;
        }
	}
	
	
	/**
	 * 
	 */
	public static function readCaseTemplate( $module_obj)
	{
		// Test if template file exists
		if( !file_exists( $module_obj->module_path.'/codetpl/Case.tpl')){
			echo '[MODULE_ERROR] ** Loader ** Case template file not exists in : '.$module_obj->module_path.'<br>';	
			return false;
		}
		
		$module_obj->tpl_case = file_get_contents( $module_obj->module_path.'/codetpl/Case.tpl');
		return true;
	}
	
	
	/**
	 * 
	 */
	public static function readFunctionTemplate( $module_obj)
	{
		// Test if template file exists
		if( !file_exists( $module_obj->module_path.'/codetpl/Function.tpl')){
			echo '[MODULE_ERROR] ** Loader ** Function template file not exists in : '.$module_obj->module_path.'<br>';	
			return false;
		}
		
		$module_obj->tpl_function = file_get_contents( $module_obj->module_path.'/codetpl/Function.tpl');
		return true;
	}
	
	
	/**
	 * 
	 */
	public static function readMainTemplate( $module_obj)
	{
		// Test if template file exists
		if( !file_exists( $module_obj->module_path.'/codetpl/Main.tpl')){
			echo '[MODULE_ERROR] ** Loader ** Main template file not exists in : '.$module_obj->module_path.'<br>';	
			return false;
		}
		
		$module_obj->tpl_main = file_get_contents( $module_obj->module_path.'/codetpl/Main.tpl');
		return true;
	}
	
	
	/**
	 * @return Module 
	 */
	public static function load( $moduleName_str)
	{	    
		$mod = new Module($moduleName_str);
		
		// read config.xml file and fill Module object
		if( self::readConfigFile($mod) !== true ){
			echo '[MODULE_ERROR] ** Loader ** readConfigFile() failed when loading module : '.$moduleName_str.'<br>';
		}
		
		// 
		if( self::readCaseTemplate($mod) !== true ){
			echo '[MODULE_ERROR] ** Loader ** readCaseTemplate() failed when loading module : '.$moduleName_str.'<br>';
		}
		
		// 
		if( self::readFunctionTemplate($mod) !== true ){
			echo '[MODULE_ERROR] ** Loader ** readFunctionTemplate() failed when loading module : '.$moduleName_str.'<br>';
		}
		
		// 
		if( self::readMainTemplate($mod) !== true ){
			echo '[MODULE_ERROR] ** Loader ** readMainTemplate() failed when loading module : '.$moduleName_str.'<br>';
		}
		
		return $mod;
	}
	
	
}

?>