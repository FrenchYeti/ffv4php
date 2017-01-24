<?php

namespace FFV;

/**
 *
 * @author gb-michel
 *        
 */
class LogicalExpression
{
	private $_errors = false;
	private $_left = null;
	private $_right = null;	
	private $_operator = null;
	
	
	/**
	 * @method boolean Test if expression contain logical operator & or |  
	 * @param ts
	 * @return
	 */
	public static function isMatchLogical($expression_str)
	{
		$tmp = array();
		preg_match('#and|or#',$expression_str,$tmp);
	
		return (count($tmp)!= 0)? true : false;
	}
	
	/**
	 * 
	 * @param unknown $type
	 * @param unknown $left
	 * @param unknown $right
	 */
	public function __construct( $type, $left, $right)
	{
		$this->_operator = $type;
		$this->_left = $left;
		$this->_right = $right;
	}
	
	
	/**
	 * 
	 * @param unknown $expression_str
	 * @return Ambigous <LogicalExpression>
	 */
	public static function parse($expression_str)
	{
		// global
		$id = 0;
		$str = $expression_str;
		$conditions = array();
		$logical = array();
		
		$asrtParser = AssertionParser::getInstance();
		
		// condition parser 
		$tmp = array();
		$pattern = '#([@a-z]+\s*[\=|!=|>|>=|<|<\=]\s*[@a-z0-9])#';
		
		preg_match_all($pattern,$str,$tmp);
		
		foreach( $tmp[0] as $offset=>$expr)
		{
			$split = preg_split('#\s*[\=|!=|>|>=|<|<\=]\s*#',$expr);
			
			$sym = array();
			preg_match('#\s*[\=|!=|>|>=|<|<\=]\s*#',$expr,$sym);
		
			
			// !!!!!! ============> RAJOUTER DES TYPES ICI
			if( is_numeric($split[0])){
			    $left = $asrtParser->parseInteger(trim($split[0]));
			}
			elseif($split[0][0] == '@'){
				$left = $asrtParser->parseIdentifier(trim($split[0]));
			}
			else{
			    $left = $asrtParser->parseExpression(trim($split[0]));
			}
			
			// !!!!!! ============> RAJOUTER DES TYPES ICI
		    if( is_numeric($split[1])){
			    $right = $asrtParser->parseInteger(trim($split[1]));
			}
			elseif($split[1][0] == '@'){
				$right = $asrtParser->parseIdentifier(trim($split[1]));
			}
			else{
			    $right = $asrtParser->parseExpression(trim($split[1]));
			}
						
			$logical['#'.$id] = new LogicalExpression(trim($sym[0]), $left, $right);
			$str = str_replace( $expr, '#'.$id, $str);
			
			$id++;			
		}
		
		while( self::isMatchLogical($str))
		{
		    $tmp = array();
			$pattern = '/\((?P<left>[#$][0-9]+)\)\s*(?P<ope>(and|or))\s*\((?P<right>[#$][0-9]+)\)/';
			preg_match_all($pattern,$str,$tmp);
			
			foreach( $tmp[0] as $offset=>$block)
			{		
			    $left = $logical[$tmp['left'][$offset]];
                $right = $logical[$tmp['right'][$offset]];
			    
                $logical['$'.$id] = new LogicalExpression(trim($tmp['ope'][$offset]), $left, $right);
                $str = str_replace($block,'$'.$id,$str);
                
				$id++;	
			}
		}
		
		$l = array_pop($logical);
		unset($logical);
		
		return $l;
	}

	
	
	/**
	 * 
	 * @return unknown
	 */
	public function getLeft()
	{
		return $this->_left;
	}
	
	
	/**
	 * 
	 */
	public function getRight()
	{
		return $this->_right;
	}
		
	
	/**
	 * 
	 * @return unknown
	 */
	public function getOperator()
	{
		return $this->_operator;
	}
	
	
	/**
	 * 
	 * @return boolean
	 */
	public function hasErrors()
	{
		return $this->_errors;
	}
}

?>