<?php

namespace FFV;

class Module
{
    const TPL_CTRL_TRUE = 'true';
    const TPL_CTRL_FALSE = 'false';
    const TPL_CTRL_END = 'end';
    const TPL_CTRL_EXIT = 'exit';
    const TPL_CTRL_CALL = 'call';
    
    const TPL_OPE_FORMAT = 'end';
    const TPL_OPE_VALUE = 'exit';
    
	public $name = null;
	public $language = null;
	public $package_name = null;
	public $file_extension = null;
	
	public $module_path = null;
	public $rules_path = null;

	public $tpl_controller = array(
		'true'=>null,
		'false'=>null,
		'exit'=>null,
		'end'=>null,
		'call'=>null
	);
	
	public $tpl_operation = array(
		'format'=>null,
		'value'=>null
	);
	
	public $tpl_init = null;	
	public $tpl_case = null;
	public $tpl_function = null;
	public $tpl_main = null;
	
	private $_files = array(); 
    private $_filehandler = null;
    private $_compiled_tpl = null;
    
    private $_generator = null;
    private $_generator_file = null;
    private $_generator_class = null;
    
    private $_indent = null;
    private $_indent_last = null;
    public $_indent_base = "\t";
    
    /**
     * Make instance of generator, perhaps custom generator
     */
    private function loadGenerator( $rs_array)
    {
       if( is_null($this->_generator_file) && is_null($this->_generator_class)){
            $this->_generator = new Generator($rs_array);
            $this->_generator->setCtrlTemplate( $this->tpl_controller);
            return true;
       } 
       else{
            if( is_null($this->_generator_file)){
                echo '[MODULE_ERROR] ** Generator **  Failed to open Generator file : file not found for module : '.$this->name.'<br>';
                return false;
            }   
            
            require_once FFE_MODULE_DIRNAME.'/'.$this->name.'/'.$this->_generator_file ;
            $this->_generator = new $this->_generator_class($rs_array);
            $this->_generator->setCtrlTemplate( $this->tpl_controller);
            
            if( is_null($this->_generator)){
                echo '[MODULE_ERROR] ** Generator **  Failed to create instance of custom generator for module : '.$this->name.'<br>';
                return false;
            }
            else{
                return true;
            }
       } 
    }
    
	public function __construct( $modulename_str)
	{
		$this->name = $modulename_str;
		$this->module_path = FFE_MODULE_DIRNAME.'/'.$modulename_str;
		$this->_indent = $this->_indent_base;
	}
	
	
	/**
	 * Used by ModuleLoader to copied files
	 */
	public function addFile( $filename_str)
	{	    
		if( !file_exists( $this->module_path.'/'.$filename_str)){
			echo '[MODULE_ERROR] ** Loader ** Add file failed, file "'.$filename_str.'" not exists. <br>';
			return false;
		}	
		
		$this->_files[] = $filename_str ;
		return true;
	}
	
	
	/**
	* Used by ModuleLoader to set generator info
	*/
	public function setGenerator( $filename_str = null)
	{
	    if( is_null($filename_str)){
	        return true;    
	    }
	    
	    if( !file_exists( FFE_MODULE_DIRNAME.'/'.$this->name.'/'.$filename_str)){
	        echo '[MODULE_ERROR] ** Generator ** Generator file not found. In module : '.$this->name.'<br>';
	        return false;
	    }
	    
	    $this->_generator_file = $filename_str;
	    $this->_generator_class = substr( $filename_str, 0, strrpos($filename_str,'.'));
	}
	
	
	/**
	 * Make package struture :
	 *     - make package directory,
	 *     - make rules directory,
	 *     - copy required files
	 *
	 * @method boolean Make package structure ( directories + static files )
	 * @return boolean Return TRUE if operation successful, else FALSE
	 * @since 04/11/2013
	 */
	public function makePackageStructure()
	{
	    echo ' - Make package structure : ';
	    
	    $package_dir = FFE_PACKAGES_DIRNAME.'/'.$this->package_name;
	    $this->rules_path = $package_dir.'/Rules';
	    
	    // make package dir
	    if( !is_dir($package_dir)){
	        $f = mkdir( $package_dir, 0066, true);
	        if ($f === false){
	        	echo 'ERROR<br>';
	            echo '[MAKE_ERROR] ** Package** Failed to create package directory : '.$package_dir.'<br>';
	            return false;
	        }
	    }

	
	    // make rules dir
	    if( !is_dir($this->rules_path)){
	        $f = mkdir( $this->rules_path, 0066, false);
	        if ($f === false){
	        	echo 'ERROR<br>';
	            echo '[MAKE_ERROR] ** Package** Failed to create rules directory : '.$this->rules_path.'<br>';
	            return false;
	        }
	    }

	
	    // copy files
	    foreach( $this->_files as $file){
	        
	        if( FFE_PACKAGE_OVERWRITE !== true){
	            if( !file_exists($package_dir.'/'.basename($file))){
	                $f = copy( $this->module_path.'/'.$file, $package_dir.'/'.basename($file));
	            }
	            else{
	            	echo 'ERROR<br>';
	                echo '[MAKE_ERROR] ** Package** Try to overwrite static files in package but overwrite is disabled for : '.$this->module_path.'/'.$file.'<br>';
	                return false;
	            }
	        }
	        else{
	            $f = copy( $this->module_path.'/'.$file, $package_dir.'/'.basename($file));
	        }
	        
	        if( $f === false){
	        	echo 'ERROR<br>';
	            echo '[MAKE_ERROR] ** Package** Faild to create rules directory : '.$this->module_path.'/'.$file.'<br>';
	            return false;
	        }
	    }
	
	    echo 'OK<br>';
	    return true;
	}
	
	/**
	 * @method boolean Make new rulesset file
	 * @param string $name Rules set name
	 * @return boolean
	 */
	public function createRulesetFile( $name_str)
	{
	    if( file_exists($this->rules_path.'/'.$name_str.$this->file_extension)){
	        if( FFE_PACKAGE_OVERWRITE === true){
	            unlink($this->rules_path.'/'.$name_str.$this->file_extension);
	        }
	        else{
	            echo '[MAKE_ERROR] ** Package : '.$this->package_name.
	            ' ** Try to overwrite existing RulesSet file : '.$this->rules_path.
	            '/'.$name_str.$this->file_extension.'<br>';
	            return false;
	        }	        
	    }
	     
	    $fh = fopen( $this->rules_path.'/'.$name_str.$this->file_extension, 'w+');
	    if( $fh === false){
	        echo '[MAKE_ERROR] ** Package : '.$this->package_name.
	        ' ** Try to overwrite existing RulesSet file : '.$this->rules_path.
	        '/'.$name_str.$this->file_extension.'<br>';
	        return false;
	    }
	
	    $this->_filehandler = $fh;
	    return true;
	}
	
	
	
	/**
	 * Generate and write content of ruleset file
	 *
	 * @see GeneratorInterface::writeRulesetFile()
	 */
	public function writeRulesetFile( $ruleset_obj)
	{
	    $body = '';
	    $main = $this->makeMainTemplate( $ruleset_obj);

	    $this->_compiled_tpl = $main;
	
	    foreach( $ruleset_obj->getRules() as $id=>$rule)
	    {
	        if( $body == ''){
	        	$tmp_main = str_replace('{RS_FIRST_RULE_ID}',$id,$this->_compiled_tpl);
	            $this->_compiled_tpl = $tmp_main;
	        }
	        
	        $fn_tpl = $this->tpl_function;
	
	        $func_body = '';
	        $this->_generator->setIndentBase("\t");
	        foreach( $rule->getAsserts() as $ida=>$assert)
	        {
	            $tpl = $this->tpl_case;
	            
	            $this->_generator->init($assert);
	            $this->_generator->setIndent("\t\t\t\t");
	            
	            $start = $assert->getStructAttr('start');
	            if( is_null($start) or ($start == 'false')){
	                $start = 'false';
	            }
	            
	            $init = $this->makeInitTemplate($start,$assert->getStructAttr('endianess'));
	            
	            $ope = $this->_generator->makeAssertBlock();

	            $ctrl = $this->_generator->makeControlBlock();
	            	            
	            $tokens = array('{ASSERT_ID}','{ASSERT_INIT}','{ASSERT_OPE}');
	            $values = array($ida,$init,$ope.$ctrl);

	            $func_body .= str_replace( $tokens, $values, $tpl)."\r\n\r\n";   
	        }
	
	        $fn_tpl = str_replace( '{CASE_TPL}', $func_body, $fn_tpl);
	        $body .= str_replace( $rule->getTokens(), $rule->getTokensData(), $fn_tpl)."\r\n\r\n";
	    }
	
	    $cpl = str_replace( '{FUNCTION_TPL}', "\r\n".$body."\r\n", $this->_compiled_tpl);
	    $this->_compiled_tpl = $cpl;
	
	    if( $this->_filehandler !== false){
	       fwrite( $this->_filehandler, $this->_compiled_tpl);
	       fclose($this->_filehandler);
	       $this->_filehandler = null;
	       $this->_compiled_tpl = null;
	    }
	}
	
	
	/**
	 *
	 */
	public function makeRulesetFile($ruleset_obj)
	{
	    $f = $this->createRulesetFile( $ruleset_obj->name);
	
	    if( $f == false){
	        echo '[GENERATOR_ERROR] ** Ruleset File ** Fail to create ruleset file : '.$ruleset_obj->name.'<br>';
	        exit;
	    }   
	    
	    $this->writeRulesetFile($ruleset_obj);
	}
	
	
	
	
	
	public function getCaseTemplate()
	{	    
	    return $this->tpl_case;
	}
	
	
	/**
	 * 
	 * @param unknown $ruleset_obj
	 * @return mixed
	 */
	public function makeMainTemplate( $ruleset_obj)
	{
	    $main = str_replace( $ruleset_obj->getTokens(), $ruleset_obj->getTokensData(), $this->tpl_main);
	    
	    return $main;
	}
	
	
	/**
	 * 
	 * @param unknown $tokens_arr
	 */
	public function makeFunctionTemplate( $tokens_arr)
	{
	    return $this->tpl_function;
	}
	

	public function makeInitTemplate( $start_int, $endianess_int)
	{
		$main = str_replace( '{POSITION}', $start_int, $this->tpl_init);
		$main = str_replace( '{ASSERT_ENDIANESS}', $endianess_int, $main);
	    return $main;
	}
	
	/**
	 * 
	 * @param unknown $type
	 * @param string $ruleID_int
	 * @param string $assertID_int
	 * @return mixed
	 */
	public function makeControllerTemplate( $type, $ruleID_int = '', $assertID_int = '')
	{
	   $tpl = $this->tpl_controller[$type];
	   
	   return str_replace( array('{RULE_ID}','{ASSERT_ID}'), array($ruleID_int,$assertID_int), $tpl);
	}
	
	
	/**
	 * 
	 * @param unknown $rs_array
	 */
	public function makePackage( $rs_array)
	{
	    $this->loadGenerator($rs_array);
	    $this->makePackageStructure();
	    
	    foreach( $rs_array as $rs)
	    {
	        $this->makeRulesetFile($rs);
	    }
	    
	    echo ' Package generation OK<br>';
	}
	
	
	public function addControlTemplate()
	{
	    
	}
}


?>