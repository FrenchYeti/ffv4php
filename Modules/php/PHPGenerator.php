<?php

use \FFV\AssertionParser as AssertionParser;
use \FFV\LogicalExpression as LogicalExpression;

/**
 * 1 Generator instance per module
 * @author gb-michel
 *
 */
class PHPGenerator 
{
    public $crlf = "\r\n";
    
    
    
    /**
     * This array contains association of alias of FFVL Variable type,
     * each key is an alias and each value, the associate type 
     *  
     * It's a <b>private</b> property
     * 
     * @var Array $_var_format_alias Array of variable type alias
     */
    private $_var_format_alias = array(
        'short'=>'int16',
        'ushort'=>'uint16',
        'long'=>'int32',
        'ulong'=>'uint32'	
    );
    
    
    /**
     * This array associate each type to his size in bits
     *  
     * It's a <b>private</b> property
     * 
     * @var Array $_var_format_size Array of type and size in bits of FFVL Variable
     */
    private $_var_format_size = array(
    	'int4' => 4,
    	'uint4' => 4,
    	'int8' => 8,
        'uint8' => 8,
    	'int16' => 16,
    	'uint16' => 16,
    	'int32' => 32,
    	'uint32' => 32,
    	'hexh' => 4,
    	'hexl' => 4,
    	'float' => 0,
    	'double' => 0,
    );
    
    private $_base_format = array(
        'int4' => 10,
        'uint4' => 10,
        'int8' => 10,
        'uint8' => 10,
        'int16' => 10,
        'uint16' => 10,
        'int32' => 10,
        'uint32' => 10,
        'hexh' => 16,
        'hexl' => 16,
        'float' => 10,
        'double' => 10,
    );
    
    /** 
     * It's a <b>private</b> property
     * 
     * @var Array $_pack_type Array which associate each FFV variable type to character will use with unpack method 
     */
    private $_pack_type = array(
        'int4' => 'i',
        'uint4' => 'I',
        'int8' => 'c',
        'uint8' => 'C',
        'int16' => 's',
        'uint16' => 'v',
        'int32' => 'l',
        'uint32' => 'V',
        'hexh' => 'H',
        'hexl' => 'h',
        'float' => 'f',
        'double' => 'd',
    );
    
    private $_little2big_endian = array(
        'v'=>'n',
        'V'=>'N'
    );
    
    private $_base_preproc = array();
    
    private $_tmp_symbols = array();
    private $_tmp_stack = array();

	private $_indent = null;
	private $_indent_last = null;
	private $_indent_base = null;
	
	private $_tpl_ctrl;
	
	/**
	 * Persistence of data between two init();
	 * @var unknown
	 */
	private $symtables = array(); 
	private $currentVarname = '';
	
	private $ctrl;
	private $_compiled_tpl = '';
	private $n = 0;
	private $root = 1;
	private $assert;
	
	
	/**
	 * 
	 */
	public function __construct( $indent = "\t")
	{
	    $this->_indent_base = $indent;
	    $this->_indent = "";
		$this->_base_preproc = array(
			AssertionParser::PREPROC_BIN => 2,
			AssertionParser::PREPROC_OCT => 8,
			AssertionParser::PREPROC_HEX => 16
		);
	}
	
	
	public function init( $assert_obj)
	{
	   $this->n = 0;    
	   $this->assert = $assert_obj;
	   $this->currentVarname = null;
	   $this->symtables = array();
	}
	
	
	
	public function setCtrlTemplate( $ctrltemplate)
	{
		$this->_tpl_ctrl = $ctrltemplate;
	}
	
	/*
	public function setControllers( $ctrl_arr)
	{
		//var_dump( 'ok', $ctrl_arr);
		$this->ctrl = $ctrl_arr;
	}
	*/
	
	public function setIndentBase( $indent)
	{
	    $this->_indent_base = $indent;
	}
	
	public function setIndent( $indent)
	{
	   $this->_indent_last = "";
	   $this->_indent = $indent;    
	}
	
	
    /**
     * 
     * @param unknown $identifier_str
     * @param unknown $local_symtable_name_str
     * @param unknown $local_symtable_arr
     * @param unknown $global_symtable_arr
     */
    public function prepareIdentifierCall( $identifier_str, $local_symtable_name_str, $local_symtable_arr,  $global_symtable_arr)
    {
        // Check if identifier is in local symbols table
        if( isset($local_symtable_arr[$identifier_str])){
            $sym = $local_symtable_arr[$identifier_str];
            $symtable_name = $local_symtable_name_str;
        }
        // Check if identifier is in global symbols table
        elseif( isset($global_symtable_arr[$identifier_str])){
            $sym = $global_symtable_arr[$identifier_str];
            $symtable_name = $sym['symtable_name'];
        }
        // Error message
        else{
            echo '[GENERATOR_ERROR] ** Identifier ** Unknow identifier called : @'.$identifier_str.'<br>';
            exit;
        }
        
        return array(
            'call'=>$symtable_name.'[\''.$identifier_str.'\']',
            'symbol'=>$sym
        );
    }
    
    
    /**
     * 
     * @param unknown $preproc_arr
     */
    public function preparePreproc( $preproc_arr)
    {
        switch( $preproc_arr['value_type'])
        {
        	case AssertionParser::PREPROC_BIN :
                $base = 2;
        	    $size = strlen($preproc_arr['value']);
        	    break;
        	case AssertionParser::PREPROC_HEX :
        	    $base = 16;
        	    $size = strlen($preproc_arr['value'])*4;
        	    break;
        	case AssertionParser::PREPROC_OCT :
                $base = 8;
        	    $size = strlen($preproc_arr['value'])*3;
        	    break;
        	default: 
                echo '[SYNTAX_ERROR] ** Preproc ** Unknow preproc type : '.$preproc_arr['value_type'].' in '.$preproc_arr['value'].'<br>';
        	    break;
        }
        
        $value = base_convert( $preproc_arr['v_value'], $base, 16);
        $nb_bits = strlen( base_convert( $preproc_arr['v_value'], $base, 2));

    }
    
    
    /**
     * @method string Prepare usage of an expression ( concatened or not ) in range context 
     * @param array $stack_arr
     * @param string $local_symtable_name_str
     * @param array $local_symtable_arr
     * @param array $global_symtable_arr
     * @return string <string, unknown>
     */
    public function prepareExpressionInRange( $stack_arr, $local_symtable_name_str, $local_symtable_arr,  $global_symtable_arr)
    {
        $value = '';
        
        foreach( $stack_arr as $expr)
        {
            switch( $expr['type'])
            {
            	case AssertionParser::TYPE_IDENTIFIER :
            	    $symbol = $this->prepareIdentifierCall( $expr['value'], $local_symtable_name_str, $local_symtable_arr,  $global_symtable_arr); 
            	    (strlen($value)>0)? $start = '".' : $start = '';          	    
            	    
            	    $value .= $start.'base_convert('.$symbol['value'].','.$this->_base_format[$symbol['symbol']['format']['value']].',16)."'; 
            	    break;
            	    
            	case AssertionParser::TYPE_STRING_EXPRESSION :
            	    $value .= bin2hex($expr['value']);
            	    break;
            	    
            	case AssertionParser::TYPE_PREPROC_EXPRESSION :
            	    if( $expr['value_type'] == AssertionParser::PREPROC_HEX){
            	        $value .= $expr['value'];
            	    }
            	    else{
            	        $value .= base_convert( $expr['value'], $this->_base_preproc[$expr['value_type']], 16);
            	    }
            	    break;
            }
        }
        
        $len = strlen($value);
        if( substr( $value, $len-2, 2) == '."'){
            return substr( $value, 0, $len-2);
        }
        else{
            return $value;
        }
    }


    /**
     * @method string Generate source code of assertion by value
     * @param array $stack_arr Stack of the expression
     * @param array $symbols_arr 
     * @param string $symbolID_int
     * @param string $varname_str
     * @return string
     */
    public function makeAssertByValueOpe( $stack_arr, $local_symtable_name_str, $local_symtable_arr,  $global_symtable_arr, $varname_str = 'v')
    {
        $expression = $stack_arr['value'];       
        $size = count($expression['value']);
        $tmp = '';
        
        
        for( $i=0 ; $i<$size ; $i++ )
        {
            $elem = $expression['value'][$i];
            switch( $elem['type'])
            {
            	case AssertionParser::TYPE_STRING_EXPRESSION :            	    
            	    $val = bin2hex($elem['value']);
            	    $nb_bits = strlen($elem['value'])*8;
            	    $pattern = 'H'; //( $nb_bits%8 > 0)? 'H' : 'H*';
            	    $tmp .= $this->_indent.'$'.$varname_str.' &= $this->_opeValue( "'.$val.'", '.$nb_bits.', "'.$pattern.'" );'.$this->crlf;
            	    break;
            	    
            	case AssertionParser::TYPE_PREPROC_EXPRESSION :  

            	    if( !isset($this->_base_preproc[$elem['value_type']])){
            	        echo '[SYNTAX_ERROR] ** Preproc ** Unknow preproc type : '.$elem['value_type'].' in '.$elem['value'].'<br>';
            	        exit;
            	    }
            	    
            	    if( $elem['value_type'] !== AssertionParser::PREPROC_HEX){
            	        $base = $this->_base_preproc[$elem['value_type']];
            	        $value = base_convert( $elem['value'], $base, 16);
            	        $nb_bits = strlen( base_convert( $elem['value'], $base, 2));
            	    }
            	    else{
            	        $value = $elem['value'];
            	        $nb_bits = strlen($value)*4;
            	    }
            	    
            	    $pattern = 'H';//( $nb_bits%8 > 0)? 'H' : 'H*';
            	    $tmp .= $this->_indent.'$'.$varname_str.' &= $this->_opeValue( "'.$value.'", '.$nb_bits.', "'.$pattern.'" );'.$this->crlf;
            	    break;
            	    
            	case AssertionParser::TYPE_IDENTIFIER :

            	    // prepareIdentifierCall( identifier_name, local_symbole_table_name_str, local_symbole_table_array, global_symbole_table_array)
            	    // Include detection of unknow identifier error
            	    $ident = $this->prepareIdentifierCall( $elem['value'], $local_symtable_name_str, $local_symtable_arr, $global_symtable_arr);
           	    
            	    //
            	    if( $elem['position'] < $ident['symbol']['position']){
            	    	echo '[GENERATOR_ERROR] ** Value undefined ** Value of @'.$elem['value'].' is undefined. <br>';
            	    	exit;
            	    }

            	    //
            	    $bsize = $this->_var_format_size[$ident['symbol']['value']['format']['value']];
            	    $pattern = $this->_pack_type[$ident['symbol']['value']['format']['value']];          	    
            	    
            	    $tmp .= $this->_indent.'$'.$varname_str.' &= $this->_opeValue( '.$ident['call'].', '.$bsize.', "'.$pattern.'" );'.$this->crlf;
            	    break;
            }    
        } 
        
        return $tmp;
    }
    
    
    
    /**
     * 
     * @param unknown $stack_arr
     * @param unknown $local_symtable_name_str
     * @param unknown $local_symtable_arr
     * @param unknown $global_symbols_arr
     * @param string $varname_str
     */
    public function makeAssertByFormatOpe( $stack_arr, $local_symtable_name_str, $local_symtable_arr, $global_symbols_arr, $varname_str = 'v')
    {
        $tmp = '';
        
        // ============ prepare identifier =============
        $ident = $stack_arr['value']['identifier']['value'];
        if( $ident !== AssertionParser::ANONYMOUS_NAME){
            $name = $ident;
        }
        else{
            $name= '';
        }
        
        // ============ prepare format =================
        // get format to pass to unpack function 
        $formatPattern = $this->_pack_type[$stack_arr['value']['format']['value']];
        $formatSize = $this->_var_format_size[$stack_arr['value']['format']['value']]; 
        
        // ============ prepare length =================
        if( $stack_arr['value']['length']['type'] == AssertionParser::TYPE_IDENTIFIER){
            $ident = $this->prepareIdentifierCall( $stack_arr['value']['length']['value'], $local_symtable_name_str, $local_symtable_arr, $global_symbols_arr);
            $length = $ident['call'];
        }
        elseif( $stack_arr['value']['length']['type'] == AssertionParser::TYPE_INTEGER){
            $length = (int) $stack_arr['value']['length']['value'];
        }
        else{
            $length = '0';
        }
        
        
        // ============ prepare range =================
        $range = $stack_arr['value']['range']['value'];
       
        if( count($range) == 0){
            $argInterval = 'array()';
            $argValue = 'array()';
        }
        else{
            $argInterval = '$rInter';
            $argValue = '$rVal';
            
            $iInt = 0;
            $iVal = 0;
            
            $rangeInterval = 'array(';
            $rangeValue = 'array(';   
            foreach( $range as $val)
            {
                if($val['type'] == AssertionParser::TYPE_NEGATION){
                    $not = "'not'=>true";
                    $val = $val['value'];
                }
                else{
                    $not = "'not'=>false";
                }
            	           
                switch($val['type'])
                {
                	case AssertionParser::TYPE_INTERVAL :
                	    $rangeInterval .= 'array("min"=>'.$val['min']['value'].',"max"=>'.$val['max']['value'].','.$not.'),';
                	    $iInt++;
                	    break;
                	case AssertionParser::TYPE_INTEGER :
                	    $rangeValue .= 'array("val"=>'.(int)$val['value'].','.$not.'),';
                	    $iVal++;
                	    break;
                	case AssertionParser::TYPE_EXPRESSION :
                	    $exp = $this->prepareExpressionInRange($val['value'], $local_symtable_name_str, $local_symtable_arr, $global_symbols_arr);
                	    $rangeValue .= 'array("val"=>"'.$exp.'",'.$not.'),';
                	    $iVal++;
                	    break;
                }
            }
            
            if( $iInt == 0){
                $argInterval = 'array()';
                $c = '';
            }
            elseif( $iInt <= 2){
                $argInterval = substr( $rangeInterval, 0, strlen($rangeInterval)-1).')';
                $c = $this->crlf;
            }
            else{
                $tmp .= $this->crlf.$this->_indent.$argInterval.' = '.substr( $rangeInterval, 0, strlen($rangeInterval)-1).');'.$this->crlf;
                $c = '';
            }
            
            if( $iVal == 0){
                $argValue = 'array()';
            }
            elseif( $iVal <= 2){
                $argValue = substr( $rangeValue, 0, strlen($rangeValue)-1).')';
            }
            else{
                $tmp .= $c.$this->_indent.$argValue.' = '.substr( $rangeValue, 0, strlen($rangeValue)-1).');'.$this->crlf;
            }
        }
        
        $tmp .= $this->_indent.'$'.$varname_str.' &= $this->_opeFormat(  '.$local_symtable_name_str.', "'.$name.'", "'.$formatPattern.'", '.$formatSize.', '.$argValue.', '.$argInterval.', '.$length.');';
        
        return $tmp;
    }
    
    
    /**
     * v0.1 : cell size and array size not implemented
     * 
     * @param unknown $stack_arr
     * @param unknown $local_symtable_name_str
     * @param unknown $local_symtable_arr
     * @param unknown $global_symbols_arr
     * @param string $varname_str
     */
    public function makeAssertByArrayOpe( $stack_arr, $local_symtable_name_str, $local_symtable_arr, $global_symbols_arr, $varname_str = 'v')
    {      
        $elems = $stack_arr['value'];          
                
        //var_dump($global_symbols_arr);
        
        $i = $this->n+1;
        
        // size call and test
        if( !is_null($elems['size'])){
            $tmp_arraysize = '|| ( $_arraySize'.$i.' <= ';
            if( $elems['size']['type'] == AssertionParser::TYPE_IDENTIFIER){
                $sym = $this->prepareIdentifierCall( $elems['size']['value'], $local_symtable_name_str, $local_symtable_arr,  $global_symbols_arr);
                $size = $sym['call'];
                $tmp_arraysize .= $size.')';
            }
            else{
            	$size = $elems['size']['value'];
                $tmp_arraysize .= $elems['size']['value'].')'; 
            }
        }
        else{
        	$size = null;
            $tmp_arraysize = '';
        }
        
        $global_symbols_arr[$local_symtable_name_str] = $local_symtable_arr;
        
        
        $tmp = $this->_indent.'$_arraySize'.$i.' = 0;'.$this->crlf;
        $tmp .= $this->_indent.'$_EOA'.$i.' = true;'.$this->crlf;
        $tmp .= $this->_indent.'$v'.$i.' = true;'.$this->crlf;
    	$tmp .= $this->_indent.'while( (($_EOA'.$i.' !== false) '.$tmp_arraysize.') && ($v'.$i.' == true) )'.$this->crlf;
    	$tmp .= $this->_indent.'{'.$this->crlf;
    	
    	$this->_indent_last = $this->_indent;
    	$this->_indent = $this->_indent.$this->_indent_base;

    	// Make block of cell's assertion
    	$tmp .= $this->_indent.'$c'.$i.' = true;'.$this->crlf;
    	$cell_body = $this->makeAssertBlock( $elems['cell_format'], $global_symbols_arr, 'c'.$i);
    	$tmp .= $cell_body;
    	
    	if( is_null($size)){
    		// unsupported user cases
    	}
    	else{
    		$tmp .= $this->_indent.'$v'.$i.' &= $c'.$i.';'.$this->crlf;
    		$tmp .= $this->_indent.'if( $c'.$i.'== false){ $_EOA'.$i.' = false; }else{ $_arraySize'.$i.'++;  }'.$this->crlf;
    	}
    	
    	// Make block of separator's assertion
    	if( !is_null($elems['separator'])){   	    
    	    $tmp .= $this->_indent.'$s'.$i.' = true;'.$this->crlf;
    	    $separator_body = $this->makeAssertBlock( $elems['separator'], $global_symbols_arr, 's'.$i);
    	    $tmp .= $separator_body;
    	     
    	    $tmp .= $this->_indent.'$v'.$i.' &= $c3;'.$this->crlf;
    	    if( $size !== null){
    	    	$tmp .= $this->_indent.'if( $_arraySize'.$i.' < '.$size.'){ $v'.$i.' &= $s'.$i.'; }'.$this->crlf;
    	    }    	    
    		$tmp .= $this->_indent.'if( $s'.$i.'== false){ $_EOA'.$i.' = false; }else{ $c'.$i.' &= $s'.$i.'; }'.$this->crlf;
    	}
    	   	
    	$this->_indent = $this->_indent_last ;
    	
    	$tmp .= $this->_indent.'}'.$this->crlf;    
    	$tmp .= $this->_indent.'$v'.($i-1).' &= $v'.$i.';'.$this->crlf;	
    	
    	return $tmp;
    }
    
    
    
    /**
     * 
     * @param unknown $opestack_arr
     * @param unknown $global_symbols_arr
     * @param string $varname_str
     * @return string
     */
    public function makeAssertBlock( $opestack_arr = null, $global_symbols_arr = array(), $varname_str = 'v1')
    {     
        if( is_null($opestack_arr)){
            $opestack_arr = $this->assert->getStack();
        } 
        
        $body = '';
        
    	// Make new local symbols table and add symbols from global symbols array
    	$this->n = $this->n+1;
    	$local_symtable_name = '$symbol_'.$this->n;
    	$local_symtable = $this->prepareLocalSymtable($opestack_arr['value']);
    	$global_symtable = array();   	
    
    	foreach( $global_symbols_arr as $table_name=>$gbl_symbols)
    	{
    	    $tmp_gbl = array();
    		foreach( $gbl_symbols as $name=>$value)
    		{
    			$tmp = $value;
    			$tmp['symtable_name'] = $table_name;
    			$tmp_gbl[$name] = $tmp;
    		}
    		
    		$global_symtable[$table_name] = $tmp_gbl;
    	} 

    	$body .= $this->_indent.$local_symtable_name.' = array();'.$this->crlf.$this->crlf;
    	
    	
    	/*
    	 * Make block
    	 */
    	$stack_size = count($opestack_arr['value']);
    	for( $i=0 ; $i<$stack_size ; $i++ )
    	{
    		$assert = $opestack_arr['value'][$i];
    		switch( $assert['type'])
    		{
    			case AssertionParser::TYPE_ASSERT_VALUE :
    				$block = $this->makeAssertByValueOpe( $assert, $local_symtable_name, $local_symtable, $global_symtable, $varname_str);
    				break;
    			case AssertionParser::TYPE_ASSERT_FORMAT :
    				$block = $this->makeAssertByFormatOpe( $assert, $local_symtable_name, $local_symtable, $global_symtable, $varname_str);
    				break;
    			case AssertionParser::TYPE_ASSERT_ARRAY :
    				
    				$block = $this->makeAssertByArrayOpe( $assert, $local_symtable_name, $local_symtable, $global_symtable, $varname_str);
    				break;
    		}
    		$body .= $block.$this->crlf;
    	}
    	
    	if( $this->n == $this->root){
    		//$body .= $this->makeControlBlock($local_symtable_name, $local_symtable, $global_symtable, $varname_str);
    		$this->symtables = array(
    			'localname'=>$local_symtable_name,
    		    'local'=>$local_symtable,
    		    'global'=>$global_symtable
    		);
    		$this->currentVarname = $varname_str;
    	}
    	
    	$this->n--;
        return $body;
    }
    
    
    /**
     * @method string Make condition string as : (($_symbol0['width'] == 1) && ($_symbol0['height'] == 1))
     * @param LogicalExpression $condition
     * 
     * @return string Condition as must be write in PHP
     * @version 0.9
     */
	public function makeCondition($condition_obj,$local_symtable_name_str,$local_symtable_arr,$global_symbols_arr)
	{
		$ope = array('and'=>'&&','or'=>'||','='=>'==','>='=>'>=','<='=>'<=','!='=>'!=','>'=>'>','<'=>'<');
		$str = '(';
		
		// make left part
		if($condition_obj->getLeft() instanceof LogicalExpression){
			$str .= $this->makeCondition($condition_obj->getLeft(),$local_symtable_name_str,$local_symtable_arr,$global_symbols_arr);
		}
		else{
			$part = $condition_obj->getLeft();
			switch($part['type'])
			{
				case AssertionParser::TYPE_IDENTIFIER:
					$sym = $this->prepareIdentifierCall($part['value'], $local_symtable_name_str, $local_symtable_arr, $global_symbols_arr);
					$str .= $sym['call'];
					break;
				case AssertionParser::TYPE_INTEGER:
					$str .= (int)$part['value'];
					break;
				case AssertionParser::TYPE_EXPRESSION:
					$str .= $this->prepareExpressionInRange($part['value'], $local_symtable_name_str, $local_symtable_arr, $global_symbols_arr);
					break;
			}
		}
		
		// make operator
		$str .= $ope[$condition_obj->getOperator()];
		
		// make rigth part
		if($condition_obj->getRight() instanceof LogicalExpression){
			$str .= $this->makeCondition($condition_obj->getRight(),$local_symtable_name_str,$local_symtable_arr,$global_symbols_arr);
		}
		else{
			$part = $condition_obj->getRight();
			switch($part['type'])
			{
				case AssertionParser::TYPE_IDENTIFIER:
					$sym = $this->prepareIdentifierCall($part['value'], $local_symtable_name_str, $local_symtable_arr, $global_symbols_arr);
					$str .= $sym['call'];
					break;
				case AssertionParser::TYPE_INTEGER:
					$str .= (int)$part['value'];
					break;
				case AssertionParser::TYPE_EXPRESSION:
					$str .= $this->prepareExpressionInRange($part['value'], $local_symtable_name_str, $local_symtable_arr, $global_symbols_arr);
					break;
			}
		}
		
		return $str.')';
	}
    
    
	/**
	 *
	 * @param unknown $local_symtable_name_str
	 * @param unknown $local_symtable_arr
	 * @param unknown $global_symbols_arr
	 * @param unknown $varname_str
	 * @return string
	 */
	public function makeControlBlock()
	{
	    // restaure data
	    $varname_str = $this->currentVarname;
	    $local_symtable_name_str = $this->symtables['localname'];
	    $local_symtable_arr = $this->symtables['local'];
	    $global_symbols_arr = $this->symtables['global'];
	    
	    // make block
	    $body = $this->_indent.'if( $'.$varname_str.' === true){'.$this->crlf;
        
	    $ctrlsTRUE = $this->assert->getControlsOnTRUE(); 
        foreach( $ctrlsTRUE as $ctrl)
        {
            $condition = $this->makeCondition($ctrl->getCondition(),$local_symtable_name_str,$local_symtable_arr,$global_symbols_arr);
            $tokens = $ctrl->getCallTokens();

            $call = str_replace(array_keys($tokens),array_values($tokens),$this->_tpl_ctrl[$ctrl->getCallTemplate()]);
            
            $body .= $this->_indent.$this->_indent_base.'if'.$condition.'{'.$this->crlf;
            $body .= $this->_indent.$this->_indent_base.$this->_indent_base.$call.$this->crlf;
            $body .= $this->_indent.$this->_indent_base.'}'.$this->crlf;
        }
        $defTrue = $this->assert->getDefaultControlOnTRUE();
        $defTokens = $defTrue->getCallTokens();
        $defCall = str_replace(array_keys($defTokens),array_values($defTokens),$this->_tpl_ctrl[$defTrue->getCallTemplate()]);
        $body .= $this->_indent.$this->_indent_base.$defCall.$this->crlf;

        
        $body .= $this->_indent.'}'.$this->crlf;
        $body .= $this->_indent.'else{'.$this->crlf;
        
        $ctrlsFALSE = $this->assert->getControlsOnFALSE();
        foreach( $ctrlsFALSE as $ctrl)
        {
            $condition = $this->makeCondition($ctrl->getCondition(),$local_symtable_name_str,$local_symtable_arr,$global_symbols_arr);
            $tokens = $ctrl->getCallTokens();
        
            $call = str_replace(array_keys($tokens),array_values($tokens),$this->_tpl_ctrl[$ctrl->getCallTemplate()]);
        
            $body .= $this->_indent.$this->_indent_base.'if'.$condition.'{'.$this->crlf;
            $body .= $this->_indent.$this->_indent_base.$this->_indent_base.$call.$this->crlf;
            $body .= $this->_indent.$this->_indent_base.'}'.$this->crlf;
        }
        $defFalse = $this->assert->getDefaultControlOnFALSE();
        $defTokens = $defFalse->getCallTokens();
        $defFalseCall = str_replace(array_keys($defTokens),array_values($defTokens),$this->_tpl_ctrl[$defFalse->getCallTemplate()]);
        $body .= $this->_indent.$this->_indent_base.$defFalseCall.$this->crlf;
        
        
        $body .= $this->_indent.'}'.$this->crlf;
        
	     
	    return $body;
	}
	
	
	/**
	 * 
	 * @param unknown $local_symtable_name_str
	 * @param unknown $local_symtable_arr
	 * @param unknown $global_symbols_arr
	 * @param unknown $varname_str
	 * @return string
	 */
    public function OLD_makeControlBlock($local_symtable_name_str,$local_symtable_arr,$global_symbols_arr,$varname_str)
    {
    	$body = '';
    	foreach( $this->ctrl as $value=>$ctrl){
    	
    		if( $value == Assertion::CTRL_ONRESULT){
    			$val_call = '$'.$varname_str;
    		}
    		else{
    			$sym = $this->prepareIdentifierCall($value,$local_symtable_name_str,$local_symtable_arr,$global_symbols_arr);
    			$val_call = $sym['call'];
    		}	
    		
    		$indent2 = $this->_indent.$this->_indent_base;
    		foreach( $ctrl as $equal=>$args){
    			$call = array();
    			isset($args['arg']['parent_id'])? $call[0]=$args['arg']['parent_id'] : $call[0] = ''; 
    			isset($args['arg']['child_id'])? $call[1]=$args['arg']['child_id'] : $call[1] = ''; 
    			
    			if($equal == Assertion::EQUAL_TRUE){
    				$equal = 'true';
    			}
    			elseif($equal == Assertion::EQUAL_TRUE){
    				$equal = 'false';
    			}
    			
    			$body .= $this->_indent.'if( '.$val_call.' == '.$equal.'){'.$this->crlf;
    			$body .= $indent2.str_replace(array('{RULE_ID}','{ASSERT_ID}'),$call,$this->tpl_ctrl[$args['tpl']]).$this->crlf;
    			$body .= $this->_indent.'}'.$this->crlf;
    		}
    	}
    	
    	return $body;
    }
    
    /**
     * @method 
     * @param unknown $assert_arr
     */
    public function prepareLocalSymtable( $assert_arr)
    {
        $symtable = array();
        
        foreach( $assert_arr as $i=>$assert)
        {
            if( $assert['type'] == AssertionParser::TYPE_ASSERT_FORMAT){
                $name = $assert['value']['identifier']['value'];
                if( $name !== AssertionParser::ANONYMOUS_NAME){
                    $symtable[$name] = $assert;
                }               
            }
        }
        
        return $symtable;
    }
}

?>