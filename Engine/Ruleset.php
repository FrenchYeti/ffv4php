<?php

namespace FFV;

class Ruleset
{
	public $name = null;
	public $path = null;
	
	private $_rules = array();	
	private $_extensions = null;
	
	private $_tokens = array(
		'{RS_EXTENSION}'=>null
	);	
	
	private $_tokens_data = array(
	   'tokens'=>array(),
	   'values'=>array()	
	);

	
    /**
     * @method bool Load rulesSet
     * @param string $rulesSetName_str Nom du dossier contenant les regles
     * @return bool Retourne FALSE en cas d'erreur
     * @author GB Michel
     * @version 1.0
     * @since 09/06/2013
     */
    private function scanRules( $rulesSetDir_str)
    {   	    		   	
        $files = scandir( $rulesSetDir_str);
        if( $files===false){
            return false;
        }
    
    	$paths = array();
    	foreach( $files as $file){
    		
    		$rule_path = $rulesSetDir_str.'/'.$file;
    		if( !is_dir($rule_path) && ($file !== '.') && ($file !== '..') && ($file !== 'INFO')){
    			
    		    $rule = new Rule( $rule_path);
    			$this->_rules[$rule->getID()] = $rule;
    		}
    	}
    	

        ksort( $this->_rules, SORT_NUMERIC);
    }
    
    
    /**
     * 
     */
    private function makeTokens()
    {
        $tokens = array();
        $values = array();
        $i = 0;
        
        foreach( $this->_tokens as $tok=>$val)
        {
            $tokens[$i] = $tok;
            $values[$i] = $val;
            $i++;
        }
        
        $this->_tokens_data = array('tokens'=>$tokens,'values'=>$values);
    }
	
    
	/**
	 * Set info
	 * 
	 * @param unknown $name_str
	 */
	public function __construct( $name_str)
	{
		$this->name = $name_str;
		$this->path = FFE_RULESETS_DIRNAME.'/'.$name_str;
		$this->_tokens['{RS_EXTENSION}'] = $name_str;
		
		$this->scanRules( $this->path);
		$this->makeTokens();
	}
	
	
	

	
	/**
	 * 
	 * @param unknown $ext_str
	 */
	public function addExtension($ext_str)
	{
		$this->_extensions[] = $ext_str;
	}
	
	
	/**
	 * 
	 * @param unknown $rule_obj
	 */
	public function addRules( $rule_obj)
	{
		$this->_rules[$rule_obj->getID()] = $rule_obj;
	}

    
	/**
	 * 
	 * @return unknown
	 */
	public function getName()
	{
		return $this->name;
	}
	
	/**
	 * Return tokens used in search/replace context
	 *
	 * @return multitype:multitype:unknown
	 */
	public function getTokens()
	{
	    return $this->_tokens_data['tokens'];
	}
	
	/**
	 * Return tokens used in search/replace context
	 *
	 * @return multitype:multitype:unknown
	 */
	public function getTokensData()
	{
	     return $this->_tokens_data['values'];
	}
	
	
	public function getRules()
	{
	    return $this->_rules;
	}
}

?>
