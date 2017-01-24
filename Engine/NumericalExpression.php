<?php

namespace FFV;

/**
 *
 * @author gb-michel
 *
 */
class NumericalExpression
{
    static public $parser_stack = array();
    static public $parser_index = 0;
    
    private $_errors = false;
    private $_left = null;
    private $_right = null;
    private $_operator = null;


    /**
     * @method boolean Test if expression contain logical operator & or |
     * @param ts
     * @return
     */
    public static function isMatchNumerical($expression_str)
    {
        $tmp = array();
        preg_match('#[+-/%*]#',$expression_str,$tmp);

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
     * @param unknown $part_str
     * @return multitype:boolean NULL string number |multitype:|NULL|multitype:NULL string boolean number
     */
    public static function parsePart( $part_str)
    {
        $asrt = AssertionParser::getInstance();
        
        if( $part_str[0] == AssertionParser::IDENTIFIER_SYMBOL){
            return $asrt->parseIdentifier($part_str);
        }
        elseif( $part_str[0] == '#'){
            if( isset(self::$parser_stack[$part_str])){
                return self::$parser_stack[$part_str];
            }
            else{
                echo '[]';
                return null;
            }
        }
        elseif( is_numeric($part_str)){
            return $asrt->parseInteger($part_str);
        }
        else{
            echo '[PARSE_ERROR]';
            return null;
        }
        
    }
    
    
    /**
     * 
     * @param unknown $expression_str
     * @return string
     */
    public static function parseNode( $expression_str)
    {
        $parse_node = '#^(?P<rest>[@a-z0-9+-/%*\s]*)(?P<last_left>\s*@[a-z]+|[0-9]+|\#[0-9]+)(?P<last_ope>\s*[-+/%*]\s*)(?P<last_right>@[a-z]+|[0-9]+|\#[0-9]+)$#';

        if( preg_match('/^#[0-9]+$/',$expression_str) < 1)
        {
            $matches = array();
            preg_match_all($parse_node,$expression_str,$matches);
            
            if( count($matches['last_right'])>0){
                $right = self::parsePart(trim($matches['last_right'][0]));
            }
            if( count($matches['last_ope'])>0){
                $ope = trim($matches['last_ope'][0]);
            }
            if( count($matches['last_left'])>0){
                $left = self::parsePart(trim($matches['last_left'][0]));
            }
            
            self::$parser_index++;
            self::$parser_stack['#'.self::$parser_index] = new NumericalExpression($ope, $left, $right);
             
            if( count($matches['rest'])>0){
                $rest = trim($matches['rest'][0]).'#'.self::$parser_index;
            }
            else{
                $rest = '';
            }
             
            if( $rest !== '')
            {
                $rest = self::parseNode($rest);
            }
        }        
    }

    /**
     * 
     * @param unknown $expression_str
     * @return multitype:
     */
    public static function parse($expression_str)
    {        
        $terminal_node = '#(?:@[a-z]+|[0-9]+|\#[0-9]+)(?:\s*[+-/%*]\s*@[a-z]+|\s*[+-/%*]\s*[0-9]+|\s*[+-/%*]\s*\#[0-9]+)+#';
        
        while( preg_match('/^#[0-9]+$/',$expression_str) < 1)
        {
            $tmp = array();
            preg_match_all($terminal_node,$expression_str,$tmp);
            
            foreach( $tmp[0] as $match)
            {
                $tmp = array();
                
                self::parseNode($match);
                $expression_str = str_replace($match,'#'.self::$parser_index,$expression_str);
                
                if( preg_match('#\(\#'.self::$parser_index.'\)#',$expression_str) > 0){
                    $expression_str = str_replace('(#'.self::$parser_index.')','#'.self::$parser_index,$expression_str);
                }
            }
        }
        
        $i = self::$parser_index-1;
        return self::$parser_stack['#'.$i];
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