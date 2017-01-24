<?php

namespace FFV;

class Engine
{
    private $_modprefsize = 0;
    
	private $_rulessets = array();
	
	private $_modules = array();
	
	public $_rs_parser = null;
	public $_assert_parser = null;
	
	public function __construct()
	{
	    $this->_modprefsize = strlen(FFE_MODULE_PREFIX);
	    
		$this->_rs_parser = new RulesetAnalyzer();	
	}
	
	
	/**
	 * Load all modules
	 */
	public function loadModules( $modname_str = null)
	{
	    if( is_null($modname_str)){
	        
	        $mods = scandir(FFE_MODULE_DIRNAME);
	        foreach( $mods as $file)
	        {
	            if( is_dir(FFE_MODULE_DIRNAME.'/'.$file)
	            && (substr($file,0,$this->_modprefsize)==FFE_MODULE_PREFIX)){
	                 
	                $this->_modules[] = ModuleLoader::load($file);
	            }
	        }
	    }
	    else{
	        if( is_dir(FFE_MODULE_DIRNAME.'/'.$modname_str)
	            && (substr($modname_str,0,$this->_modprefsize)==FFE_MODULE_PREFIX)){
	           $this->_modules[] = ModuleLoader::load($modname_str);
	        }
	        else{
	            echo '[ENGINE_ERROR] ** Modules ** Module "'.$modname_str.'" not found. <br>';
	        }
	    }
	}
	
	
	/**
	 * 
	 */
	public function run()
	{
	    // Scan rs and make rs collection
		$this->_rulessets = $this->_rs_parser->analyze( FFE_RULESETS_DIRNAME);		
		
		$this->loadModules();
		
		// Make each package via associate module
		foreach( $this->_modules as $mod)
		{
		    $mod->makePackage( $this->_rulessets);
		}
	}
	
	
	/**
	 * To check validity and analyze an assertion in a rule file
	 * 
	 * @param unknown $rsname_str
	 * @param unknown $rulefilename_str
	 * @param unknown $assertID_int
	 */
	public function analyzeAssertionInRulefile( $rsname_str, $rulefilename_str, $assertID_int)
	{
	    
	}
	
	/**
	 * To check validity and analyze an assertion 
	 * 
	 * @param unknown $assertion_str
	 */
	public function analyzeAssertion( $assertion_str)
	{
	    
	}
	
	/**
	 * To check a rule file ( form + assertions )
	 * 
	 * @param unknown $rsname_str
	 * @param unknown $rulefilename_str
	 */
	public function analyzeRuleFile( $rsname_str, $rulefilename_str)
	{
	    
	}
	
	
	/**
	 * To check ruleset
	 * 
	 * @param unknown $rsname_str
	 */
	public function analyzeRuleset( $rsname_str)
	{
	    
	}
	
	
	/**
	 * Analyze assertion in argument and make source code to implement.
	 * Language of generate source code depends of module to load. 
	 * 
	 * 
	 * @method Make assertion and generate adapted source code to implement in a specific language depending of the loaded module
	 * @param string $assertion_str Assertion to make
	 * @param string $modname_str Name of module must be used
	 * 
	 */
	public function testAssertion( $assertion_str, $modname_str)
	{
	    // Parse assertion 
	    $ap = AssertionParser::getInstance();
	    $assert = $ap->parseGlobalAssertion($assertion_str);
	    
	    // Load module
	    $this->loadModules($modname_str);
	    
	    // 
	}
}

?>