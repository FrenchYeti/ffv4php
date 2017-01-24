<?php

namespace FFV;

class AssertionParser
{
    private static $_instance = null; 
    public $_filepath = '';
    
    const ANONYMOUS_NAME = 'anonymous';
    
    // CONSTANTS OF TYPE OF PARSED DATAS
    // ----------------------------------
    
    const TYPE_ASSERT = 'assertion';
    const TYPE_ASSERT_FORMAT = 'format_assertion';
    const TYPE_ASSERT_VALUE = 'value_assertion';
    const TYPE_ASSERT_ARRAY = 'array_assertion';
    
    const TYPE_EXPRESSION = 'expression';
    const TYPE_STRING_EXPRESSION = 'string_expression';
    const TYPE_PREPROC_EXPRESSION = 'preproc_expression';
    
    const TYPE_IDENTIFIER = 'identifier';
    const TYPE_INTEGER = 'integer';
    const TYPE_INTERVAL = 'interval';
    const TYPE_LIST = 'list';
    const TYPE_NEGATION = 'negation';
    
    const TYPE_FORMATT = 'format_type';
    
    const PREPROC_HEX = 'hexadecimal_string';
    const PREPROC_BIN = 'binary_string';
    const PREPROC_OCT = 'octal_string';
    
    // CONSTANTS OF LANGUAGE SYMBOLS
    // -----------------------------
    
	const PREPROC_DELIMITER = '"';
	const STRING_DELIMITER = "'";
	
	const INTERVAL_SYMBOL = '>';
	const IDENTIFIER_SYMBOL = '@';
	const CONCAT_SYMBOL = '.';
	const NEGATION_SYMBOL = '!';
	const REPLACEMENT_SYMBOL = '#';
	
	const LIST_OPEN = '(';
	const LIST_CLOSE = ')';
	const LIST_SEPARATOR = ',';
	
	const ASSERT_FORMAT_OPEN = self::IDENTIFIER_SYMBOL;
	const ASSERT_FORMAT_CLOSE = '';
	const ASSERT_FORMAT_SEPARATOR = ':';
	
	const ASSERT_VALUE_OPEN = '{';
	const ASSERT_VALUE_CLOSE = '}';
	
	const ASSERT_ARRAY_OPEN = '[';
	const ASSERT_ARRAY_CLOSE = ']';
	const ASSERT_ARRAY_SEPARATOR = ';';
	
	const END_OF_ASSERT = '/';
	
	
	private function __construct()
	{}
	
	
	/**
	 * 
	 * @return AssertionParser
	 */
	public static function getInstance()
	{
	    if( is_null(self::$_instance)){
	        self::$_instance = new AssertionParser();
	    }
	    
	    return self::$_instance;
	}
	
	/**
	 * @method string Protect a symbol which go to be add to a regular expression, add escape character if necessary
	 * @param string $symbol_char Symbol to protect
	 * @return string Return the symbol, escaped if necessary. 
	 */
	private function protectSym( $symbol_char)
	{
	    $sym = array('.','^','[',']',"'",'{','}','+','?','!','(',')','|','#');
	    if( in_array($symbol_char,$sym)){
	        return '\\'.$symbol_char; 
	    }
	    else{
	        return $symbol_char;
	    }
	}
	
	
	/**
	 * @method boolean Test if a chars string represent an interval
	 * @param string $string Chars string to test
	 * @return boolean Return TRUE if input string represent an interval, else FALSE
	 * @since 01/11/2013
	 */
	private function isInterval($str)
	{
	    if( preg_match('#^('.$this->protectSym(self::IDENTIFIER_SYMBOL).'[a-z]*|[0-9]+)\s*'.$this->protectSym(self::INTERVAL_SYMBOL).'\s*('.$this->protectSym(self::IDENTIFIER_SYMBOL).'[a-z]*|[0-9]+)$#', $str) == 1){
	        return true;
	    }
	    else{
	        return false;
	    }    
	}
	
	
	/**
	 * @method boolean Test if a chars string represent an integer
	 * @param string $string Chars string to test
	 * @return boolean Return TRUE if input string represent an integer, else FALSE 
	 * @since 01/11/2013
	 */
	private function isInteger($str)
	{
	    if( preg_match('#^\d+$#', $str) == 1){
	        return true;
	    }
	    else{
	        return false;
	    }
	}
	
	
	private function isNumericalExpression($str)
	{
	    if( preg_match('#^(?:\(([0-9]+|@[a-z]+)(\s*[+-/%*]\s*([0-9]+|@[a-z]+))+\))$#', $str) == 1){
	        return true;
	    }
	    else{
	        return false;
	    }
	}
	
	
	/**
	 * 
	 */
	public function parsePreprocExpression( $str)
	{
	    $rx = '#^'.$this->protectSym(self::PREPROC_DELIMITER).'(?P<value>[a-fA-F0-9]+)(?P<type>b|h|o)'.$this->protectSym(self::PREPROC_DELIMITER).'$#';
	    $matches = array();
	    $return = array(
	        'type'=>self::TYPE_PREPROC_EXPRESSION,
	        'error'=>false,
	        'value'=>null,
	        'value_type'=>null,
	        'position'=>-1,
	    );
	    
	    preg_match_all( $rx, $str, $matches, PREG_OFFSET_CAPTURE);
	    
	    if( (count($matches['value'])>0) && (count($matches['type'])>0) ){
	    
	        $return['value'] = strtolower($matches['value'][0][0]);
	        
	        switch( strtolower($matches['type'][0][0])){
	        	case 'o':
	        	    $return['value_type'] = self::PREPROC_OCT;
	        	    break;
	        	case 'h':
	        	    $return['value_type'] = self::PREPROC_HEX;
	        	    break;
	        	case 'b':
	        	    $return['value_type'] = self::PREPROC_BIN;
	        	    break;
	        	default:
	        	    echo '[PARSE_ERROR] ** Preproc expression ** Unknow type (Tips : allowed type : "b" or "h" or "o"). Error on :'.$str.' in "'.$this->filepath.'"<br>';
	        	    $return['error'] = true;
	        	    break;
	        }
	        
	        $return['position'] = $matches['value'][0][1]-1;
	        return $return;
	    }
	    else{
	        echo '[PARSE_ERROR] ** Preproc expression ** It can contain only alpha num characters and finished by "b" or "h" or "o" (Tips: Preproc expression is case insensitive). Error on :'.$str.' in "'.$this->filepath.'"<br>';
	    
	        $return['error'] = true;
	        return $return;
	    }
	}
	
	
	/**
	 * Parse a string expression and return a array to parser of expression
	 * 
	 * @param unknown $str
	 * @return multitype:string boolean NULL number
	 */
	public function parseStringExpression( $str)
	{
	    $rx = '#^'.$this->protectSym(self::STRING_DELIMITER).'[a-zA-Z0-9\s]+'.$this->protectSym(self::STRING_DELIMITER).'$#';
	    $matches = array();
	    $return = array(
	    	'type'=>self::TYPE_STRING_EXPRESSION,
	        'error'=>false,
	        'value'=>null,
	        'value_type'=>'characters_string',
	        'position'=>-1,
	    );
	    
	    preg_match_all( $rx, $str, $matches, PREG_OFFSET_CAPTURE);
	    
	    if( count( $matches[0]>0) && ($matches[0][0][0] == $str)){
	        
	        $return['value'] = substr( $matches[0][0][0], 1, strlen($matches[0][0][0])-2);
	        $return['position'] = $matches[0][0][1];
	        return $return;
	    }
	    else{
	        echo '[PARSE_ERROR] ** String expression ** can contain only alphanum characters, string expressions are case sensitive. Error on :'.$str.' in "'.$this->filepath.'"<br>';
	        
	        $return['error'] = true;
	        return $return;
	    }
	}
	
	
	public function parseIdentifier( $str)
	{
	    $rx = '#^'.$this->protectSym(self::IDENTIFIER_SYMBOL).'[a-z]*$#';
	    $matches = array();
	    $return = array(
	        'type'=>self::TYPE_IDENTIFIER,
	        'error'=>false,
	        'value'=>null,
	        'value_type'=>'volatil',
	        'position'=>-1,
	    );
	    
	    $f = preg_match_all( $rx, $str, $matches, PREG_OFFSET_CAPTURE);
	    
	    if( ($f==1) && ($matches[0][0][0] == $str)){
	    
	    	$id = substr( $matches[0][0][0], 1, strlen($matches[0][0][0])-1);
	    	if( $id == ''){
	    		$id = self::ANONYMOUS_NAME;
	    	}
	   	
	        $return['value'] = $id;
	        $return['position'] = $matches[0][0][1];
	        return $return;
	    }
	    else{
	        echo '[PARSE_ERROR] ** Identifier ** can contain alpha characters. Error on :'.$str.' in "'.$this->filepath.'"<br>';
	    
	        $return['error'] = true;
	        return $return;
	    }
	}
	
	public function parseInteger($str)
	{
	    $return = array(
	        'type'=>self::TYPE_INTEGER,
	        'value'=>null,
	        'value_type'=>'integer',
	        'error'=>false,
	        'position'=>-1
	    );
	    
	    if( preg_match( '#\d+#', $str) == 0){
	        echo '[PARSE_ERROR] ** Integer ** can contain only numeric characters. Error on :'.$str.' in "'.$this->filepath.'"<br>';
	        $return['error'] = true;
	    }
	    else{
	        $return['value'] = intval($str);
	    }
	
	    return $return;
	}
	
	public function parseFormatType($str)
	{
	    $_var_format = array(
	        'int4','uint4','uint8','int8','int16',
	        'uint16','int32','uint32',
	        'hexh','hexl','float',
	        'double','char','string'
	    );
	
	    $return = array(
	        'type'=>self::TYPE_FORMATT,
	        'value'=>null,
	        'value_type'=>self::TYPE_FORMATT,
	        'error'=>false,
	        'position'=>-1
	    );
	
	    if( !in_array( $str, $_var_format)){
	        echo '[PARSE_ERROR] ** Format type ** Unknow format type ( Tips : allowed format type : int4|uint4|int8|uint8|int16|uint16|hexh|hexl|hex|float|double|long|ulong|short|ushort|char|string ). In : '.(string)$str.' in "'.$this->filepath.'"<br>';
	        $return['error'] = true;
	    }
	    else{
	        $return['value'] = $str;
	    }
	
	    return $return;
	}
	
	/**
	 * Return a stack of concatened elements in the expression
	 * 
	 * @param unknown $str
	 */
	public function parseExpression( $str)
	{
	    $stack = array(
	    	'type'=>self::TYPE_EXPRESSION,
	        'value'=>null,
	        'value_type'=>'mixed',
	        'error'=>false,
	        'position'=>-1
	    );
	    
	    // Split expression arround dot, each match must be a compatible expression, see BNF for more details 
	    $matches = preg_split( '#'.$this->protectSym(self::CONCAT_SYMBOL).'#', $str, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_OFFSET_CAPTURE);
	
	    for( $i=0 ; $i<count($matches) ; $i++)
	    {
	
	        // Parse string according with type of delimiter
	        $res = null;
	        switch( $matches[$i][0][0])
	        {
	        	case self::STRING_DELIMITER:
	        	    $res = $this->parseStringExpression($matches[$i][0]);        	    
	        	    break;
	        	    
	        	case self::PREPROC_DELIMITER:
	        	    $res = $this->parsePreprocExpression($matches[$i][0]);
	        	    break;
	        	    
	        	case self::IDENTIFIER_SYMBOL:
	        	    $res = $this->parseIdentifier($matches[$i][0]);
	        	    break;
	        }
	        
	        // add parsed data to stack
	        if( !is_null($res) && ($res['error'] === false)){
	            $res['position'] = $matches[$i][1];
		        $stack['value'][$i] = $res;
		    }
		    else{
		        $stack['error'] = true;
		    }
	    }
	    
	    return $stack;
	}
	
	public function parseVolatil( $str)
	{
	    switch( $str[0])
	    {
	    	case self::IDENTIFIER_SYMBOL :
	    	    $tmp = $this->parseIdentifier($str);
	    	    if( $tmp['error'] === true){
	    	        echo '[PARSE_ERROR] ** Volatile ** Volatile support identifier but not declaration ( Tips : ). In : "'.$str.'"  in "'.$this->filepath.'" <br>';
	    	    }
	    	    break;
	    	    
	    	case self::STRING_DELIMITER :
	    	    $tmp = $this->parseExpression($str);
	    	    if( $tmp['error'] === true){
	    	        echo '[PARSE_ERROR] ** Volatile ** Volatile support string and preproc concatened expression but there are an error ( Tips : ). In : "'.$str.'" in "'.$this->filepath.'" <br>';
	    	    }
	    	    break;
	    	    
	    	case self::PREPROC_DELIMITER :
	    	    $tmp = $this->parseExpression($str);
	    	    if( $tmp['error'] === true){
	    	        echo '[PARSE_ERROR] ** Volatile ** Volatile support string and preproc concatened expression but there are an error ( Tips : ). In : "'.$str.'" in "'.$this->filepath.'" <br>';
	    	    }
	    	    break;
	    	    
	    	default :
	    	    if( $this->isInterval($str) === true){
	    	        $tmp = $this->parseInterval($str);
	    	    }
	    	    elseif( isInteger($str) == 1){
	    	        $tmp = $this->parseInteger($str);
	    	    }
	    	    else{
	    	        // if data == '' , value is null but error is false
	    	        if( $str !== ''){
	    	            echo '[PARSE_ERROR] ** List ** Unknow value type in list ( Tips : allowed type : expression ( string expression and/or preproc expression ). In : "'.$str.'"  in "'.$this->filepath.'" <br>';
	    	           $tmp = null;
	    	        }
	    	    }
	    	    break;
	    }
	}
	
	public function parseInterval( $str)
	{
	    $return = array(
	        'type'=>self::TYPE_INTERVAL,
	        'value'=>array(
	            'min'=>null,
	            'max'=>null,
	        ),
	        'vale_type'=>'volatil',
	        'error'=>false,
	        'position'=>-1
	    );
	    
	    $matches = preg_split( '#\s*'.$this->protectSym(self::INTERVAL_SYMBOL).'\s*#', $str, -1, PREG_SPLIT_NO_EMPTY);
	    
	    if( count($matches) !== 2){
	        
	        echo '[PARSE_ERROR] ** Identifier ** There are more to one > in interval declaration ( Tips : min > max ). In : '.(string)$str.' in "'.$this->filepath.'"<br>';    
	        $return['error'] = true;
	    }
	    else{
	        
	        $min = trim($matches[0]);
	        $max = trim($matches[1]);
	        
	        if( $min[0] === self::IDENTIFIER_SYMBOL){
	            $return['min'] = $this->parseIdentifier($min);
	        }  
	        else{
	            if( $this->isInteger($min) === true){
	                $return['min'] = $this->parseInteger($min);
	            }            
	            else{
	                $return['error'] = true;
	                echo '[PARSE_ERROR] ** Interval ** Unknow min value type ( Tips : allowed type : identifier as @ident, integer as 12). In : '.(string)$str.' in "'.$this->filepath.'"<br>';
	            }
	        } 
	        
	        if( $max[0] === self::IDENTIFIER_SYMBOL){
	            $return['max'] = $this->parseIdentifier($max);
	        }
	        else{
	            if( $this->isInteger($max) === true){
	                $return['max'] = $this->parseInteger($max);
	            }  
	            else{
	                $return['error'] = true;
	                echo '[PARSE_ERROR] ** Interval ** Unknow max value type ( Tips : allowed type : identifier as @ident, integer as 12). In : '.(string)$str.' in "'.$this->filepath.'"<br>';
	            }
	        }
	    }
	    
	    if( !is_null($return['value']['min']) && ($return['value']['min']['error'] === true)){
	        $return['error'] = true;
	    }
	    
	    if( !is_null($return['value']['max']) && ($return['value']['max']['error'] === true)){
	        $return['error'] = true;
	    }
	    
	    return $return;   
	}
	
	
	/**
	 * @method 
	 * @param unknown $str
	 * @return Ambigous <NULL, multitype:NULL string boolean number , unknown, multitype:string boolean number multitype:NULL  multitype:NULL string boolean number  NULL multitype:boolean NULL string number  >
	 */
	public function parseList($str)
	{
	    $return = array(
	        'type'=>self::TYPE_LIST,
	        'value'=>null,
	        'value_type'=>'volatil',
	        'error'=>false,
	        'position'=>-1
	    );  
	    
	    if( preg_match( '#^'.$this->protectSym(self::LIST_OPEN).'[^'.$this->protectSym(self::LIST_OPEN).$this->protectSym(self::LIST_CLOSE).']*'.$this->protectSym(self::LIST_CLOSE).'$#', $str) == 0 ){
	        $return['error'] = true;
	        echo '[PARSE_ERROR] ** List ** List is not well formed ( Tips : list start with "(" and close with ")", and she cannot contain parenthesis ). In : '.(string)$str.' in "'.$this->filepath.'"<br>';        
	    }
	    else{
	        
	        $list = substr( $str, 1, strlen($str)-2);
	        $matches = preg_split( '#\s*'.$this->protectSym(self::LIST_SEPARATOR).'\s*#', $list, -1);
	        $size = count($matches);
	        
	        if( ($size == 1) && (strlen(trim($matches[0]))==0) ){        		
        	    $return['value'] = array();    		
        	}
        	else{
        	    for( $i=0 ; $i<$size; $i++)
        	    {
            	    $raw = trim($matches[$i]);
            	     
            	    if( strlen($raw) == 0){
            	       echo '[PARSE_ERROR] ** List ** Void value detected in list : '.$list.'  File : "'.$this->filepath.'"<br>';
            	        exit;
            	    }
            	    
            	    
            	    if($raw[0] == self::NEGATION_SYMBOL){
                	    $data = substr( $raw, 1, strlen($raw)-1);
                	    $offset = 1;
            	    }
            	    else{
                	    $data = $raw;
                	    $offset = 0 ;
            	    }
            	    
            	     
            	    switch( $raw[$offset])
            	        {
            	        case self::IDENTIFIER_SYMBOL :
                	        $tmp = $this->parseExpression($data);
                	        if( $tmp['error'] === true){
                	        echo '[PARSE_ERROR] ** List ** List support identifier but not declaration ( Tips : ). In : "'.$matches[$i].'" IN "'.(string)$str.'"  in "'.$this->filepath.'"<br>';
                	        $return['error'] = true;
                	        }
                	        break;
            	        case self::STRING_DELIMITER :
                	        $tmp = $this->parseExpression($data);
        	            	   if( $tmp['error'] === true){
        	            	       echo '[PARSE_ERROR] ** List ** List support string and preproc concatened expression but there are an error ( Tips : ). In : "'.$matches[$i].'" IN "'.(string)$str.'" in "'.$this->filepath.'" <br>';
                	        $return['error'] = true;
                	        }
                	        break;
            	        case self::PREPROC_DELIMITER :
                	        $tmp = $this->parseExpression($data);
                	            if( $tmp['error'] === true){
                	            echo '[PARSE_ERROR] ** List ** List support string and preproc concatened expression but there are an error ( Tips : ). In : "'.$matches[$i].'" IN "'.(string)$str.'" in "'.$this->filepath.'" <br>';
                	            $return['error'] = true;
                	        }
                	        break;
            	        default :
                	        if( $this->isInterval($data) === true){
                	            $tmp = $this->parseInterval($data);
                    	        if( $tmp['error'] === true){
                    	           $return['error'] = true;
                    	        }
                	        }
                	        elseif( $this->isInteger($data) == 1){
        	            	    $tmp = $this->parseInteger($data);
        	            	    if( $tmp['error'] === true){
                	               $return['error'] = true;
                	            }
                	        }
            	            else{
                	            // if data == '' , value is null but error is false
                	            if( $data !== ''){
                    	            echo '[PARSE_ERROR] ** List ** Unknow value type in list ( Tips : allowed type : expression ( string expression and/or preproc expression ). In : "'.$matches[$i].'" IN "'.(string)$str.'"  in "'.$this->filepath.'" <br>';
                    	            $return['error'] = true;
                	            }
            	            }
            	            break;
            	        }
            	         
            	        if( $offset == 1){
                	        $return['value'][$i] = array(
                    	        'type'=>self::TYPE_NEGATION,
                	            'value'=>$tmp,
                	            'value_type'=>'volatil',
                	            'error'=>false,
                	            'position'=>-1
            	        );
            	    }
            	    else{
            	        $return['value'][$i] = $tmp;
            	    }
        	    }
        	}
	        
	        
	    }
	    
	    return $return;
	}
	
	
	
	public function parseFormatAssertion($str)
	{
	    $return = array(
	        'type'=>self::TYPE_ASSERT_FORMAT,
	        'value'=>array(
	            'identifier'=>null,
	            'format'=>null,
	            'length'=>null,
	            'range'=>null
	        ),
	        'value_type'=>'volatil',
	        'error'=>false,
	        'position'=>-1
	    );
	    
	  
	    $matches = preg_split( '#'.$this->protectSym(self::ASSERT_FORMAT_SEPARATOR).'#', $str, -1);
	    
	    if( count($matches) !== 4){
	        $return['error'] = true;
	        echo '[PARSE_ERROR] ** Interval ** Unknow max value type ( Tips : allowed type : identifier as @ident, integer as 12). In : '.(string)$str.' in "'.$this->filepath.'"<br>';
	    }
	    else{
	        
	        // In first position : identifier, it can be void just prefixed of IDENTIFIER_SYMBOL
	        $identifier = $this->parseIdentifier($matches[0]);
	        if( $identifier['error'] === true){
	            echo '[PARSE_ERROR] ** Format assertion ** First argument must be an identifier ( Tips : a identifier is a alpha characters string prefixed by @, see BNF). In : '.(string)$str.' in "'.$this->filepath.'"<br>';
	            $return['error'] =  true;
	        }
	        else{
	            $return['value']['identifier'] = $identifier; 
	        }
	        
	        // In second position : format type according with allowed value, see BNF for more detail.
	        $format = $this->parseFormatType($matches[1]);
	        if( $format['error'] === true){
	            echo '[PARSE_ERROR] ** Format assertion ** Second argument must be an format type ( Tips : allowed format type : int4|uint4|int8|uint8|int16|uint16|hexh|hexl|hex|float|double|long|ulong|short|ushort|char|string ). In : '.(string)$str.' in "'.$this->filepath.'"<br>';
	            $return['error'] =  true;
	        }
	        else{
	            $return['value']['format'] = $format;
	        }
	        
	        // In third position : format type according with allowed value, see BNF for more detail.
	        if( trim($matches[2]) == ''){
	            $return['value']['length'] = null;
	        }
	        elseif( is_numeric($matches[2])){
	            $return['value']['length'] = array(
	                'type'=>'format_length',
	                'value'=>(int)$matches[2],
	                'value_type'=>'integer',
	                'error'=>false,
	                'position'=>-1
	            );
	        }
	        elseif( $matches[2][0] == self::IDENTIFIER_SYMBOL){ 
	            $return['value']['length'] = $this->parseIdentifier($matches[2]);
	        }       
	        else{
	            echo '[PARSE_ERROR] ** Format assertion ** Third argument must be void, an integer or an identifier ( Tips : ). In : '.(string)$str.' in "'.$this->filepath.'"<br>';
	            $return['error'] =  true;
	        }       
	        
	        // In fourth position : format type according with allowed value, see BNF for more detail.
	        $range = $this->parseList($matches[3]);
	        if( $range['error'] === true){
	            echo '[PARSE_ERROR] ** Format assertion ** Fourth argument must be void or a range of values ( Tips : ). In : '.(string)$str.' in "'.$this->filepath.'"<br>';
	            $return['error'] =  true;
	        }
	        else{
	            $return['value']['range'] = $range;
	        }
	    
	    }
	
	    return $return;
	}
	
	
	/**
	 * 
	 * @param unknown $str
	 * @return multitype:NULL string boolean number Ambigous <multitype:NULL string boolean number , unknown>
	 */
	public function parseValueAssertion($str)
	{
		$return = array(
	        'type'=>self::TYPE_ASSERT_VALUE,
	        'value'=>null,
	        'value_type'=>'expression',
	        'error'=>false,
	        'position'=>-1
	    );
	    
	    $rx = '#^'.$this->protectSym(self::ASSERT_VALUE_OPEN).'[^'.$this->protectSym(self::ASSERT_VALUE_OPEN).$this->protectSym(self::ASSERT_VALUE_CLOSE).']*'.$this->protectSym(self::ASSERT_VALUE_CLOSE).'$#';
		
		if( preg_match( $rx, $str) == 0){
			echo '[PARSE_ERROR] ** Value assertion ** Value assertion is not well formed ( Tips : ). In : '.(string)$str.' in "'.$this->filepath.'"<br>';
	        $return['error'] =  true;
		}
		else{
			$data = substr( $str, 1, strlen($str)-2);
			$return['value'] = $this->parseExpression($data); 
		}
		
		return $return;
	}
	
	
	/**
	 * 
	 * @param unknown $str
	 * @return Ambigous <NULL, multitype:string boolean number multitype:NULL  , multitype:boolean NULL string number , Ambigous, multitype:multitype: string boolean number >
	 */
	public function parseArrayAssertion($str)
	{	    
		$return = array(
			'type'=>self::TYPE_ASSERT_ARRAY,
	        'value'=>array(
	        	'cell_format'=>null,
	        	'cell_size'=>null,
	        	'separator'=>null,
	         	'size'=>null
	        ),
	        'value_type'=>'stack',
	        'error'=>false,
	        'position'=>-1
		);
		
		$rx = '#^'.$this->protectSym(self::ASSERT_ARRAY_OPEN).'.*'.$this->protectSym(self::ASSERT_ARRAY_CLOSE).'$#';
		
	    if( preg_match( $rx, $str) == 0){
	        echo '[PARSE_ERROR] ** Array assertion ** Array assertion is not well formed, perhaps she\'s empty ( Tips : ). In : '.(string)$str.' in "'.$this->filepath.'"<br>';
	        $return['error'] =  true;
	    }
	    else{
	        
	        // remove bracket
	        $raw = substr( $str, 1, strlen($str)-2);
	        
	        // prepare array to split, replace array_separator for remove conflict
	        $i = 0;
	        $in = 0;
	        while( $i<strlen($raw))
	        { 
	            if( ($in === 0) && ($raw[$i] == ';') ){
	                $raw[$i] = self::REPLACEMENT_SYMBOL;
	            }
	            
	            if( $raw[$i] == self::ASSERT_ARRAY_OPEN){
	                $in++;
	            }
	            elseif( $raw[$i] == self::ASSERT_ARRAY_CLOSE){
	                $in--;
	            }
	            
	            $i++;
	        }
	
	        $args = preg_split( '#\s*'.$this->protectSym(self::REPLACEMENT_SYMBOL).'\s*#', $raw, -1);
	       
	        for( $i=0 ; $i<count($args) ; $i++)
	        {
	            $data = trim($args[$i]);
	           
	            switch( $i)
	            {
	                // in first position, description of the cell format with an assertion
	                // ==================================================
	            	case 0:
	            	    $return['value']['cell_format'] = $this->parseGlobalAssertion($data);
	            	    if( $return['value']['cell_format']['error'] === true){
	            	        $return['value'] = true;
	            	    }
	            	    break;
	
	            	// in second position, size of cell in octet, let void if undefined or variant
	            	// ==================================================
	        	    case 1:    	
	        	        if( $data == ''){
	        	            $return['value']['cell_size'] = null;
	        	        }        
	                    elseif( $data[0] === self::IDENTIFIER_SYMBOL){
	                        $return['value']['cell_size'] = $this->parseIdentifier($data);
	                        if( $return['value']['cell_size']['error'] === true){
	                            $return['value'] = true;
	                        }
	                    }  
	                    else{
	                        if( $this->isInteger($data) === true){
	                            $return['value']['cell_size'] = $this->parseInteger($data);
	                            if( $return['value']['cell_size']['error'] === true){
	                                $return['value'] = true;
	                            }
	                        }            
	                        else{
	                            $return['error'] = true;
	                            echo '[PARSE_ERROR] ** Interval ** Unknow value type in fourth position. In : '.(string)$str.' in "'.$this->filepath.'"<br>';
	                        }
	                    }         
	        	        break;
	
	        	    // in third position, description of the cell format with an assertion
	        	    // ==================================================
	        	    case 2:       	              	        
	        	        if( $data == ''){
	        	            $return['value']['separator'] = null;
	        	        }   
	        	        else{
	        	            $return['value']['separator'] = $this->parseGlobalAssertion($data);
	        	            if( $return['value']['separator']['error'] === true){
	        	                $return['value'] = true;
	        	            }
	        	        }     
	                    
	        	       break;
	
	        	    // in fourth position, size of cell in octet, let void if undefined or variant
	        	    // ==================================================
	    	        case 3:
	                    if( $data == ''){
	        	            $return['value']['size'] = null;
	        	        }        
	                    elseif( $data[0] === self::IDENTIFIER_SYMBOL){
	                        $return['value']['size'] = $this->parseIdentifier($data);
	                        if( $return['value']['size']['error'] === true){
	                            $return['value'] = true;
	                        }
	                    }  
	                    else{
	                        if( $this->isInteger($data) === true){
	                            $return['value']['size'] = $this->parseInteger($data);
	                            if( $return['value']['size']['error'] === true){
	                                $return['value'] = true;
	                            }
	                        }            
	                        else{
	                            $return['error'] = true;
	                            echo '[PARSE_ERROR] ** Array assertion ** Unknow value type in fourth position. In : '.(string)$str.' in "'.$this->filepath.'"<br>';
	                        }
	                    }  
	    	           break;
	
	    	        // If additionnal arguments 
	    	        // ==================================================
	    	        default:
	    	           echo '[PARSE_WARNING] ** Array assertion ** Unknow additional arguments in declaration of the array assertion. Additionnals arguments are ignored. In : '.(string)$str.' in "'.$this->filepath.'"<br>';
	    	           break;
	            }
	        }
	    }
	
	    return $return;
	}
	
	
	/**
	 * 
	 * @param unknown $str
	 * @param string $filepath
	 * @return Ambigous <multitype:multitype: string boolean number , NULL, multitype:string boolean number multitype:NULL  , multitype:boolean NULL string number >
	 */
	public function parseGlobalAssertion($str, $filepath = '')
	{
	    $this->filepath = $filepath;
	    
		$stack = array(
			'type'=>self::TYPE_ASSERT,
	        'value'=>array(),
	        'value_type'=>'stack',
	        'error'=>false,
	        'position'=>-1
		);
		
		$raw_data = preg_split( '#\s*'.$this->protectSym(self::END_OF_ASSERT).'\s*#', $str, -1);
		
		for( $i=0; $i<count($raw_data) ; $i++)
		{
			$data = trim($raw_data[$i]);
			
			if( $data == ''){
			  continue;    
			}
			
			switch( $data[0])
			{
				case self::ASSERT_FORMAT_OPEN :
					$stack['value'][$i] = $this->parseFormatAssertion($data);
					$stack['value'][$i]['position'] = $i;
					break;
				case self::ASSERT_VALUE_OPEN :
					$stack['value'][$i] = $this->parseValueAssertion($data);
					$stack['value'][$i]['position'] = $i;
					break;
				case self::ASSERT_ARRAY_OPEN :
					$stack['value'][$i] = $this->parseArrayAssertion($data);
					$stack['value'][$i]['position'] = $i;
					break;	
				default :
					echo '[PARSE_ERROR] ** GLOBAL ** Unknow assertion at offset : '.($i+1).' (on : '.(string)$data.') in "'.$this->filepath.'"<br>';
					$stack['error'] = true;
					break;	
			}
	
			if( isset($stack['value'][$i]) && ($stack['value'][$i]['error'] === true)){
			    $stack['error'] = true;
			}
		}
		
		return $stack;
	}
	
}

?>