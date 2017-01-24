<?php

namespace FFV;

class RulesetAnalyzer
{	
	public $rsList = array();
    
    
    
    public function __construct()
    {
    	
    }

    
    /**
     * @method Array Extract from FFDatabse all rules sets and rules 
     * @param string $rsdir_str Path to rules sets directory
     * @return Array|Boolean Return a array of rule_sets and rule and parse results of rule, else FALSE 
     * @author Georges-Bastien MICHEL
     * @version 0.9a
     * @since 13/10/2013
     */
    public function analyze( $rsdir_str)
    {
        $rulessets = array();
        $this->_rulessetdir = $rsdir_str;
    
        if( !is_dir( $this->_rulessetdir)){
            $logs_ptr[] = 'Failed to open Rules set directory.';
            return false;
        }
    
        $tmp = scandir( $this->_rulessetdir);
        if( $tmp === false) return false;
    
    	// Scan rules sets directory and extract and sort rules sets
        foreach ($tmp as $f){
            if( is_dir($rsdir_str.'/'.$f) && ($f != '.') && ($f != '..')){
    
    			// Return all parsed rules from single rules set
    			$this->rsList[] = new Ruleset( $f);
            }
        }
    
        return $this->rsList;
    }
}

?>