<?php

define('FFV_RULES_PATH',__DIR__.'\Rules');

require_once __DIR__.'/FFVOpe.php';

/**
 * @package FFV
 * @author Georges-B. MICHEL
 * @version 0.1
 * @since 17 nov. 2013
 */
class FFV
{
    const LITTLE_ENDIAN = '0';
    const BIG_ENDIAN = '1';
    const UNKNOW_ENDIAN = 'unknow';
    
	/**
	 * @var Raw assertion as write by developer, default set to NULL 
	 */
	private $_raw_assert = null;
	
	/**
	 * @var Parsed assertion after run parser on raw assertion, default set to NULL
	 */
	private $_parsed_assert = null;
	
	/**
	 * @var Error message, default set to FALSE
	 */
	private $_error = false; 
	private $_endianess = null;
	private $_cache = array();
	
	
	private function loadRules( $format_str)
	{
		if( !file_exists(FFV_RULES_PATH.'/'.$format_str.'.php')){
			return false;
		}
		
		require_once FFV_RULES_PATH.'/'.$format_str.'.php';
		$class = 'ffr'.$format_str;
		
		$rule = new $class();
		$rule->setSrvEndianess($this->_endianess);

		return $rule;
	}
	
	/**
	 *
	 * @return boolean
	 */
	private function isLittleEndian()
	{
	    $testint = 0x00FF;
	    $p = pack('S', $testint);
	    return $testint===current(unpack('v', $p));
	}
	
	
	/**
	 * 
	 */
	public function __construct()
	{
	    if( $this->isLittleEndian()){
	        $this->_endianess = self::LITTLE_ENDIAN;
	    }
	    else{
	        $this->_endianess = self::BIG_ENDIAN;
	    }
	}
	
	
	/**
	 * @method boolean Set assertion to use
	 * @param string $assertion_str Raw assertion
	 * @return boolean If assertion is parsed without error survains return TRUE, else FALSE 
	 * @since 17 nov. 2013
	 * @version 0.1
	 * @author Georges-B. MICHEL
	 */
	public function setAssertion( $assertion_str)
	{
		$parser = AssertionParser::getInstance();
		$this->_raw_assert = $assertion_str;
		$this->_parsed_assert = $parser->parseGlobalAssertion($assertion_str);
	}
	
	
	/**
	 * @method boolean Pass assertion on a file pass in argument
	 * @param string $filepath_str Path of file
	 * @return boolean Result of assertion, TRUE if assertion successful, else FALSE
	 * @since 17 nov. 2013
	 * @author Georges-B. MICHEL
	 * @version 0.1
	 */
	public function passAssertion( $filepath_str)
	{
		if( !file_exists($filepath_str)){
			$this->_error = 'File "'.$filepath_str.'" not exists !'; 
			return false;
		}
		
		
	}
	
	/**
	 * @method string|boolean Return error message
	 * @return string|boolean Return error message if exists, else FALSE
	 * @author Georges-B. MICHEL
	 * @since 17 nov. 2013
	 * @version 0.1
	 */
	public function getError()
	{
		return $this->_error;
	}
	
	
	/**
	 * Return an associative array containing multiple informations about file's format.
	 * Infos :
	 *  - ext => extension determined by validation 
	 *  - desc => description of format ( version, etc..)
	 * 
	 * @method string Determine format of file
	 * @param string $filepath_str Path of file
	 * @return string Return an array with multiple informations about format, see description for more details 
	 * @author Georges-B. MICHEL
	 * @since 17 nov. 2013
	 * @version 0.1
	 */
	public function getFileFormat( $filepath_str)
	{
		// WARNING : "extension" is tempory shunt for debug 
		/* $p = explode('.',$filepath_str);
		$ext = array_pop($p);
		unset($p); */
		
	}
	
	
	/**
	 * @method boolean Return error message
	 * @return boolean Return error message if exists, else FALSE
	 * @author Georges-B. MICHEL
	 * @since 17 nov. 2013
	 * @version 0.1
	 */
	public function validateFileFormat( $filepath_str, $format_str = null)
	{
		// WARNING : "extension" is tempory shunt for debug 
		if( is_null($format_str)){
			$p = explode('.',$filepath_str);
			$ext = array_pop($p);
			unset($p);
		}
		else{
			$ext = $format_str;
		}
		
		// pass rules
		if( !isset($this->_cache[$ext])){
			$this->_cache[$ext] = $this->loadRules($ext);			
		}			
		$this->_cache[$ext]->passRules($filepath_str);
	}	
}

?>