<?php

namespace FFV;


/**
 * 1 Generator instance per module
 * @author gb-michel
 *
 */

class Generator implements GeneratorInterface
{
    private $_compiled_tpl = '';
    
	public $_rs = array();
	
	
	
	/**
	 * 
	 */
	public function __construct( $rs_array)
	{
	    $this->_rs = $rs_array;
	}    
    
    
    public function makeAssertion( $assert)
    {
        $body = '';
        
        
    }
    
    /* *
     * Create new package for a specific langage
     * Include : 
     *  - make package directory
     *  - make rules directory within the package directory
     *  - make DROID extract file and include it in package dir
     *  - copy API file to package directory
     *  
     * @param $langage_str
     * @return string
     */
    /**
     * 
     * @param string $langage_str
     * @return string|Ambigous <NULL, string>
     */
    public function makeRulesetFile()
    {
        var_dump($this->_rs);
        //$this->_module->makePackageStructure();
    }
    
    
    /**
     * 
     */
    public function createRulesetFile( $name_str)
    {
        
    }
    
    
    /**
     * 
     * @param unknown $ruleset_obj
     */
    public function writeRulesetFile( $ruleset_obj)
    {
        $body = '';
        $main_tpl = $this->_module->getMainTemplate();
        $main_tokens = $ruleset_obj->getTokens();

        $main = str_replace( $main_tokens['tokens'], $main_tokens['values'], $main_tpl);
        $this->_compiled_tpl = $main;
        
        foreach( $ruleset_obj->getRules() as $id=>$rule)
        {          
            $fn_tpl = $this->_module->getFunctionTemplate();          
            $rule_tokens = $rule->getTokens();
            
            $cases = ''; 
            foreach( $rule->getAsserts() as $ida=>$assert)
            {
                
            }
            
            $body .= str_replace( $rule_tokens['tokens'], $rule_tokens['values'], $fn_tpl)."\r\n\r\n";
        }
        
        $cpl = str_replace( '{RS_BODY}', "\r\n".$body."\r\n", $this->_compiled_tpl);
        $this->_compiled_tpl = $cpl;
        
        echo $cpl;
    }

    
    
    
    /**
     * This method is building the body of the method associate to a rule in the rules set directory of a specific file format.
     * There is an semantic analysis, who is followed the object files generation.
     *
     * $opestack_arr Stack of object to translate
     * $symbolID_int is the ID of the symbols table if we are making the body of a sub assertion, and we don't want conflict with global symbols table.
     * $varname_str is the name of the variable containing the results assertion. In sub-assertion, for example, in cell of en array, we must know result independtly of the current parent assertion result.
     * $charset_str's value MUST be a value of the $charset_str array's keys.
     *
     * @method string Build body of a method of type rule
     * @param array $opestack_arr Stack of object to translate
     * @param array $symbols_arr
     * @param boolean $useLocalSymbolsTable_bool
     * @param int $symbolID_int ID of the symbols table, defaults set to null.
     * @param string $varname_str The name of the variable containing results of the assertion
     * @param string $charset_str The name of the charset to use when encontering char type in a variable or a static element
     */
    public function composeOpe( $opestack_arr, $symbols_arr, $useLocalSymbolsTable_bool = true, $symbolID_int = null, $varname_str = 'v', $charset_str = 'ASCII')
    {
        if( $useLocalSymbolsTable_bool === true){
            if( is_null($symbolID_int)){
                 
                $symbolsTName = 'symbols';
                $ret = '';
            }
            else{
                $symbolsTName = 'symbols_'.$symbolID_int.'';
                 
                $ret = '$'.$symbolsTName.' = array(';
                foreach( $symbols_arr as $name=>$value){
                    if( is_int($value) or is_float($value)){
                        $ret .= '"'.$name.'"=>'.$value.','.$this->_CRLF;
                    }
                    elseif( is_string($value)){
                        $ret .= '"'.$name.'"=>"'.$value.'",'.$this->_CRLF;
                    }
                    elseif( is_null($value)){
                        $ret .= '"'.$name.'"=>null,'.$this->_CRLF;
                    }
                    else{
                        // debug error: additionnals checks needed
                    }
                }
                $ret .= ');'.$this->_CRLF.$this->_CRLF;
            }
        }
        else{
            $symbolsTName = 'symbols';
            $ret = '';
        }
         
        $nb_bytes = $this->_charset_bytes_size[$charset_str];
    
        foreach( $opestack_arr as $ope){
    
            switch( $ope['type'])
            {
                // static ope generation
            	case self::TYPE_STATIC :
            	    // Calc length of value in bytes, depend of charset, default ASCII
            	    $size = $nb_bytes*$ope['v_length']*8;
    
            	    $ret .= '$'.$varname_str.' &= $this->_opeStandard( "'.$ope['v_value'].'", '.$size.', "H*");'.$this->_CRLF;
            	    break;
    
            	    // preproc ope generation
            	case self::TYPE_PREPROC :
            	    // Calc length of value in bytes, depend of charset, default ASCII
            	    $value = base_convert( $ope['v_value'], $ope['v_base'], 16);
            	    $nb_bits = strlen( base_convert( $ope['v_value'], $ope['v_base'], 2));
            	    $pattern = ( $nb_bits%8 > 0)? 'H' : 'H*';
    
            	    $ret .= '$'.$varname_str.' &= $this->_opeStandard( "'.$value.'", '.$nb_bits.', "'.$pattern.'");'.$this->_CRLF;
            	    break;
    
            	    // Array generation, cell format and separator are sub-requests
            	case self::TYPE_ARRAY :
            	    // recurisivite
            	    // make cell ope
            	    // Tape dans la table des symboles de l'assertion en lecture
            	    // et tape dans la table locale des symboles en ecriture
            	    $tmp_cell = $this->composeAssertOpe( $ope['v_cell']['stack'], $ope['v_cell']['symbols'], true, $this->_symbolsTableUniqid, 'cell'.$this->_symbolsTableUniqid);
            	    $this->_symbolsTableUniqid++;
    
            	    $tmp_separator = $this->composeAssertOpe( $ope['v_separator']['stack'], $ope['v_separator']['symbols'], true, $this->_symbolsTableUniqid, 'sep'.$this->_symbolsTableUniqid);
            	    $this->_symbolsTableUniqid++;
    
            	    $ret .= '$_arraySize'.($this->_symbolsTableUniqid - 2).' = 0;';
            	    $ret .= '$_EOA = false; '."\r\n".'while( $_EOA === false){'.$this->_CRLF;
    
            	    $ret .= $tmp_cell;
    
            	    $ret .= 'if( $cell'.($this->_symbolsTableUniqid - 2).' == false){ $_EOA = true; }else{ $_arraySize'.($this->_symbolsTableUniqid - 2).'++; }';
    
            	    $ret .= $this->_CRLF.$this->_CRLF.$tmp_separator;
    
            	    $ret .= 'if( $sep'.($this->_symbolsTableUniqid - 1).' == false){ $_EOA = true; }';
    
            	    $ret .= $this->_CRLF.'}'.$this->_CRLF;
    
            	    if( !is_null($ope['v_length'])){
            	        switch( $ope['v_length']['type'])
            	        {
            	        	case 'int':
            	        	    $ret .= '$v &= ($_arraySize'.($this->_symbolsTableUniqid - 2).' == '.$ope['v_length']['value'].')? true : false;'.$this->_CRLF;
            	        	    break;
            	        	case 'ref':
            	        	    $ret .= '$v &= ($_arraySize'.($this->_symbolsTableUniqid - 2).' == $'.$symbolsTName.'["'.$ope['v_length']['value'].'"],)? true : false;'.$this->_CRLF;
            	        	    break;
            	        	default:
            	        	    // error
            	        	    break;
            	        }
            	    }
    
            	    break;
    
            	    // generation of basics validation based on basic type with or not name
            	case self::TYPE_VAR :
    
            	    // Prepare range validation
            	    if( count($ope['v_values_range'])>0){
            	         
            	        // Prepare array of allowed values
            	        if( !is_null($ope['v_values_range']['list'])){
    
            	            $list = 'array('.$ope['v_values_range']['list'];
            	             
            	            foreach( $ope['v_values_range']['list_ref'] as $reference){
            	                $list .= '$'.$symbolsTName.'["'.$reference.'"],';
            	            }
            	            $list .= substr($list,0,strlen($list)-1).')';
            	        }
            	        else{
            	            $list = 'null';
            	        }
            	         
            	        // Prepare check range function
            	        if( !is_null($ope['v_values_range']['range'])){
            	            $range = 'function($val){ $r=true; ';
            	            foreach( $ope['v_values_range']['range'] as $range_val){
    
            	                // *** SEMANTIC ANALYSIS ***
            	                if( isset($range_val['min_ref'])){
            	                    $min = '$'.$symbolsTName.'["'.$range_val['min_ref'].'"] ';
            	                }
            	                elseif( isset($range_val['min_val'])){
            	                    $min = $range_val['min_val'];
            	                }
            	                else{
            	                    // semantic error : unknow min value
            	                }
    
            	                if( isset($range_val['max_ref'])){
            	                    $max = '$'.$symbolsTName.'["'.$range_val['max_ref'].'"] ';
            	                }
            	                elseif( isset($range_val['max_val'])){
            	                    $max = $range_val['max_val'];
            	                }
            	                else{
            	                    // semantic error : unknow max value
            	                }
    
            	                // add verif  et ref
            	                $range .= '$r &= (($val>='.$min.') && ($val<='.$max.')); ';
            	            }
            	            $range .= '}';
            	        }
            	        else{
            	            $range = 'null';
            	        }
            	         
            	         
            	        // Prepare array of disallowed value
            	        if( !is_null($ope['v_values_range']['not'])){
            	            $not = 'array(';
            	            foreach( $ope['v_values_range']['not']['vals'] as $not_val){
            	                $not .= $not_val.',';
            	            }
            	            foreach( $ope['v_values_range']['not']['refs'] as $not_ref){
            	                $not .= '$'.$symbolsTName.'["'.$not_ref.'"],';
            	            }
            	            $not = substr( $not, 0, strlen($not)-1).')';
            	        }
            	        else{
            	            $not = 'null';
            	        }
            	    }
            	    	
            	    	
            	    if( isset($ope['v_length']['max'])){
            	        if( is_numeric($ope['v_length']['max'])){
            	            $len_max = (int)$ope['v_length']['max'];
            	        }
            	        else{
            	            if( in_array( $ope['v_length']['max'], $symbols_arr)){
            	                $len_max = '$'.$symbolsTName.'["'.$ope['v_length']['max'].'"],';
            	            }
            	            else{
            	                // error max length
            	            }
            	        }
            	    }
    
            	    $pack_format = $this->_pack_type[$ope['v_type']];
            	    	
            	    $ret .= '$'.$varname_str.' &= $this->_opeVarInit( $'.$symbolsTName.', "'.$ope['v_value'].'","'.$pack_format.'","'.$ope['v_size'].'",'.$list.','.$range.','.$not.');'.$this->_CRLF;
            	    break;
            }
        }
    }
    
    
    
    /**
     * This method is building the body of the method associate to a rule in the rules set directory of a specific file format.
	 * There is an semantic analysis, who is followed the object files generation.   
     * 
     * $opestack_arr Stack of object to translate
     * $symbolID_int is the ID of the symbols table if we are making the body of a sub assertion, and we don't want conflict with global symbols table. 
     * $varname_str is the name of the variable containing the results assertion. In sub-assertion, for example, in cell of en array, we must know result independtly of the current parent assertion result. 
     * $charset_str's value MUST be a value of the $charset_str array's keys.
     * 
     * @method string Build body of a method of type rule
     * @param array $opestack_arr Stack of object to translate
     * @param array $symbols_arr 
     * @param boolean $useLocalSymbolsTable_bool 
     * @param int $symbolID_int ID of the symbols table, defaults set to null. 
     * @param string $varname_str The name of the variable containing results of the assertion  
     * @param string $charset_str The name of the charset to use when encontering char type in a variable or a static element
     */
    public function composeAssertOpe( $opestack_arr, $symbols_arr, $useLocalSymbolsTable_bool = true, $symbolID_int = null, $varname_str = 'v', $charset_str = 'ASCII')
    {
    	if( $useLocalSymbolsTable_bool === true){
    		if( is_null($symbolID_int)){
    			
    			$symbolsTName = 'symbols';
    			$ret = '';
    		}
    		else{
    			$symbolsTName = 'symbols_'.$symbolID_int.'';
    			
    			$ret = '$'.$symbolsTName.' = array(';
				foreach( $symbols_arr as $name=>$value){
					if( is_int($value) or is_float($value)){
						$ret .= '"'.$name.'"=>'.$value.','.$this->_CRLF;
					}
					elseif( is_string($value)){
						$ret .= '"'.$name.'"=>"'.$value.'",'.$this->_CRLF;
					}
					elseif( is_null($value)){
						$ret .= '"'.$name.'"=>null,'.$this->_CRLF;
					}
					else{
						// debug error: additionnals checks needed 
					}				
				}
				$ret .= ');'.$this->_CRLF.$this->_CRLF;
    		}
    	}
    	else{
    		$symbolsTName = 'symbols';
    		$ret = '';
    	}
    	
    	$nb_bytes = $this->_charset_bytes_size[$charset_str];
    	    	
    	foreach( $opestack_arr as $ope){
    		
    		switch( $ope['type'])
    		{
    		    // static ope generation
    			case self::TYPE_STATIC :
    				// Calc length of value in bytes, depend of charset, default ASCII
    				$size = $nb_bytes*$ope['v_length']*8;
    				  				
    				$ret .= '$'.$varname_str.' &= $this->_opeStandard( "'.$ope['v_value'].'", '.$size.', "H*");'.$this->_CRLF;
    				break;	

    			// preproc ope generation 
    			case self::TYPE_PREPROC :
    				// Calc length of value in bytes, depend of charset, default ASCII
    				$value = base_convert( $ope['v_value'], $ope['v_base'], 16);      								
    				$nb_bits = strlen( base_convert( $ope['v_value'], $ope['v_base'], 2));   				
    				$pattern = ( $nb_bits%8 > 0)? 'H' : 'H*';
    				
    				$ret .= '$'.$varname_str.' &= $this->_opeStandard( "'.$value.'", '.$nb_bits.', "'.$pattern.'");'.$this->_CRLF;
    				break;	
    				
    			// Array generation, cell format and separator are sub-requests
    			case self::TYPE_ARRAY :
	    			// recurisivite
	    			// make cell ope
	    			// Tape dans la table des symboles de l'assertion en lecture
	    			// et tape dans la table locale des symboles en ecriture	    			
	    			$tmp_cell = $this->composeAssertOpe( $ope['v_cell']['stack'], $ope['v_cell']['symbols'], true, $this->_symbolsTableUniqid, 'cell'.$this->_symbolsTableUniqid);
	    			$this->_symbolsTableUniqid++;
	    			
	    			$tmp_separator = $this->composeAssertOpe( $ope['v_separator']['stack'], $ope['v_separator']['symbols'], true, $this->_symbolsTableUniqid, 'sep'.$this->_symbolsTableUniqid);
	    			$this->_symbolsTableUniqid++;
	    			
	    			$ret .= '$_arraySize'.($this->_symbolsTableUniqid - 2).' = 0;';
	    			$ret .= '$_EOA = false; '."\r\n".'while( $_EOA === false){'.$this->_CRLF;
	    			
	    			$ret .= $tmp_cell;
	    			
	    			$ret .= 'if( $cell'.($this->_symbolsTableUniqid - 2).' == false){ $_EOA = true; }else{ $_arraySize'.($this->_symbolsTableUniqid - 2).'++; }';
	    			    
	    			$ret .= $this->_CRLF.$this->_CRLF.$tmp_separator;
	    			
	    			$ret .= 'if( $sep'.($this->_symbolsTableUniqid - 1).' == false){ $_EOA = true; }';
	    			
	    			$ret .= $this->_CRLF.'}'.$this->_CRLF;
	    			
	    			if( !is_null($ope['v_length'])){
	    			    switch( $ope['v_length']['type'])
	    			    {
	    			    	case 'int':
	    			    	    $ret .= '$v &= ($_arraySize'.($this->_symbolsTableUniqid - 2).' == '.$ope['v_length']['value'].')? true : false;'.$this->_CRLF;
	    			    	    break;
	    			    	case 'ref':
	    			    	    $ret .= '$v &= ($_arraySize'.($this->_symbolsTableUniqid - 2).' == $'.$symbolsTName.'["'.$ope['v_length']['value'].'"],)? true : false;'.$this->_CRLF;
	    			    	    break;
	    			    	default:
	    			    	    // error
	    			    	    break;
	    			    }
	    			}
	    			
    				break;	

    			// generation of basics validation based on basic type with or not name
    			case self::TYPE_VAR :

    			    // Prepare range validation
    			    if( count($ope['v_values_range'])>0){
    			        
    			        // Prepare array of allowed values
    			        if( !is_null($ope['v_values_range']['list'])){
    			              			            
    			            $list = 'array('.$ope['v_values_range']['list'];
    			            
    			            foreach( $ope['v_values_range']['list_ref'] as $reference){
    			                $list .= '$'.$symbolsTName.'["'.$reference.'"],';
    			            }
    			            $list .= substr($list,0,strlen($list)-1).')';
    			        }
    			        else{
    			            $list = 'null';
    			        }
    			        
    			        // Prepare check range function
    			        if( !is_null($ope['v_values_range']['range'])){
    			             $range = 'function($val){ $r=true; ';
    			             foreach( $ope['v_values_range']['range'] as $range_val){
    			                 
    			                 // *** SEMANTIC ANALYSIS ***
    			                 if( isset($range_val['min_ref'])){
    			                     $min = '$'.$symbolsTName.'["'.$range_val['min_ref'].'"] ';
    			                 }
    			                 elseif( isset($range_val['min_val'])){
    			                     $min = $range_val['min_val'];
    			                 }
    			                 else{
    			                     // semantic error : unknow min value
    			                 }
    			                 
    			                 if( isset($range_val['max_ref'])){
    			                     $max = '$'.$symbolsTName.'["'.$range_val['max_ref'].'"] ';
    			                 }
    			                 elseif( isset($range_val['max_val'])){
    			                     $max = $range_val['max_val'];
    			                 }
    			                 else{
    			                     // semantic error : unknow max value
    			                 }
    			                 
    			                 // add verif  et ref
    			                 $range .= '$r &= (($val>='.$min.') && ($val<='.$max.')); ';
    			             }
    			             $range .= '}';
    			        }
    			        else{
    			            $range = 'null';
    			        }
    			        
    			        
    			        // Prepare array of disallowed value
    			        if( !is_null($ope['v_values_range']['not'])){
    			            $not = 'array(';
    			            foreach( $ope['v_values_range']['not']['vals'] as $not_val){
    			                $not .= $not_val.',';
    			            }
    			            foreach( $ope['v_values_range']['not']['refs'] as $not_ref){
    			                $not .= '$'.$symbolsTName.'["'.$not_ref.'"],';
    			            }
    			            $not = substr( $not, 0, strlen($not)-1).')';
    			        }
    			        else{
    			            $not = 'null';
    			        } 
    			    }
    			    
    			    
					if( isset($ope['v_length']['max'])){
						if( is_numeric($ope['v_length']['max'])){
							$len_max = (int)$ope['v_length']['max'];
						}
						else{
							if( in_array( $ope['v_length']['max'], $symbols_arr)){
								$len_max = '$'.$symbolsTName.'["'.$ope['v_length']['max'].'"],';
							}
							else{
							    // error max length
							}
						}
					}

					$pack_format = $this->_pack_type[$ope['v_type']];
					
    				$ret .= '$'.$varname_str.' &= $this->_opeVarInit( $'.$symbolsTName.', "'.$ope['v_value'].'","'.$pack_format.'","'.$ope['v_size'].'",'.$list.','.$range.','.$not.');'.$this->_CRLF;
    				break;	
    		}
    	}
    }
    
    
    /**
     * 
     * @param unknown $stack_arr
     * @param unknown $ruleID_int
     * @param unknown $rule_arr
     * @return string
     */
    public function composeTranslateRuleMethod( $stack_arr, $ruleID_int, $rule_arr)
    {	
    	// Generate Doc and declaration of the function
        $doc = $this->composeRuleMethodDoc($rule_arr);
        $doc .= 'public function rule_'.$ruleID_int.'( $assertID_int)'.$this->_CRLF.'{'.$this->_CRLF;

		// Generate body of the function
		$doc .= "\t".'$v = true;'.$this->_CRLF."\t".'switch( $assertID_int){'.$this->_CRLF;	
		foreach( $rule_arr as $assert){
			$doc .= "\t\tcase {$assert['id']} :".$this->_CRLF;
			
			// Generate translation of assert structure
			// ----------------------------------------
			
			// Parse structure description in assert
    		$parsed_data = $this->parseCmd( $assert['struct']['cmd']);
    		  		
    		// Translate structure description to set of basics functions
			$doc .= $this->composeAssertOpe( $parsed_data['stack'], $parsed_data['symbols']);
			
			// Add controller
			$doc .= "\t\t".'if( $v === true){'."\r\n";
			$doc .= "\t\t\t".$stack_arr[$ruleID_int][$assert['id']]['true'].$this->_CRLF."}".$this->_CRLF."else{".$this->_CRLF;
			$doc .= "\t\t\t".$stack_arr[$ruleID_int][$assert['id']]['false'].$this->_CRLF."}".$this->_CRLF."\t\t\tbreak;".$this->_CRLF;
		}
        
        $doc .= "\t}".$this->_CRLF."}".$this->_CRLF.$this->_CRLF.$this->_CRLF;
        
        return $doc;
    }
    
    
    /**
     * @method void Create final librairie for a specified type and fill this.
     * @param
     */
    public function composeTranslateRuleFile( $ruleslist_arr, $extension_str)
    {
    	// Make call stack of rules
        $call_stack = $this->makeCallStack();
        
        // Create file
        $fh = fopen( $this->_currenttranslaterulesdir.'/FFV_'.$extension_str.'.inc.php', 'w+');
        if( $fh === false){
            echo "[FATAL_ERROR] :  Cannot create translate RS file<br>";
            return false;
        }
        
        // Write header : php tag and class declaration
        if( fwrite( $fh, '<?php '.$this->_CRLF.FFV_LICENSE.$this->_CRLF) == false) return $this->error_event();    
        
        if( fwrite( $fh, "class FFV_$extension_str extend FFVOpe".$this->_CRLF."{".$this->_CRLF) == false) return $this->error_event();
        
        if( fwrite( $fh, "\t".'private $_firstrule = \'rule_1\';'.$this->_CRLF) == false) return $this->error_event();
        
        // Make and write class body : all method
        foreach( $call_stack as $rule_id=>$rule){
        	
        	$method = $this->composeTranslateRuleMethod( $rule);
        	if( fwrite( $fh, $method) == false) return $this->error_event();
        }      
        
        // Write end of class and php tag, close file
        if( fwrite( $fh, "} ".$this->_CRLF." ?>") == false) return $this->error_event();
    	fclose( $fh);
    }
}

?>