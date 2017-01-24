<?php

class FFVOpe
{
    const LITTLE_ENDIAN = '0';
    const BIG_ENDIAN = '1';
    const UNKNOW_ENDIAN = 'unknow';
    
	private $_ubuffer = null;
    private $_udelta = 0;
    
    //private $_globalsymbols = array();
    private $_fhandler = null;
	private $_result = null;
	
	// convert little to big endian
	private $_pack_integer = array('i','I','l','L','s','S','n','N','v','N');
	private $_current_pack = array(
	    'c' => 'c',
	    'C' => 'C',
	    'i' => 'i',
	    'I' => 'I',
	    's' => 's',
	    'v' => 'n',
	    'l' => 'l',
	    'V' => 'N',
	    'H' => 'H',
	    'h' => 'h',
	    'f' => 'f',
	    'd' => 'd',
	);
	private $_unpack_fn = '_unpackStandard';
	private $_endianess = self::UNKNOW_ENDIAN;
    private $_srv_endianess = self::UNKNOW_ENDIAN;
    private $_fopen = false;	

    /**
     * 
     */
    protected function _end()
    {
    	echo 'Passage ok';
    	$this->_result = true;	
    }
    
    /**
     * 
     */
    protected function _exit()
    {
    	echo 'Erreur'; 
    	$this->_result = false;   	
    }
    
    
    // standard unpack
    protected function _unpackStandard( $pattern_str, $data_bin)
    {
        return unpack( $pattern_str, $data_bin);
    }
    
    
    // unpack with convertion big to little or little to big 
    protected function _unpackEndianTranslater( $pattern_str, $data_bin)
    {      
        if( in_array( $pattern_str[0], $this->_pack_integer)){   
                     
            $u = unpack( $pattern_str, $data_bin);
            $data = dechex($u);
            if (strlen($data) <= 2) {
                return $u;
            }
            $u = unpack("H*", strrev(pack("H*", $data)));
            $f = hexdec($u[1]);
            return array('val'=>$f);
        }
        else{
            return unpack( $pattern_str, $data_bin);
        }
    }
    
    
    /**
     * @method string
     * @param int $size_int Number of bits to unpack
     * @param string $packFormat_str Format of unpack function according with data type which we want extract
     */
    protected function _unpackData( $size_int, $packFormat_str)
    {
    	$bytes_size = intval($size_int/8);
    	
    	if( $this->_udelta > 0){
    		
    		if( $size_int > $this->_udelta){
    			
    			if( $size_int%8 == 0){
            		$pattern = $this->_current_pack[$packFormat_str].'1val';
            		
            		if( $bytes_size-1 == 0){
            			$data = '';
            			$buffer = fread( $this->_fhandler, 1);            			
            		}
            		else{
            			$data = fread( $this->_fhandler, ($bytes_size-1));
            			$buffer = fread( $this->_fhandler, 1);
            		}
    				
            		//
            		$unpack = call_user_func( array($this,$this->_unpack_fn), $pattern, $this->_ubuffer.$data.$buffer);
    				//$unpack = unpack( $pattern, $this->_ubuffer.$data.$buffer);
		        
			        // $this->_udelta invariant
			        $this->_ubuffer = $buffer;  				
    			}
    			else{
    				
    				$pattern = $this->_current_pack[$packFormat_str].'val';
    				
    				// Si la taille modulo 8 est superieure a la taille du segment non verifie du buffer
    				// On doit extraire (Taille/8)+1 octets 
    				if( $size_int%8 > $this->_udelta){
			            $xtract_size = ($bytes_size+1);            
					}
					// Sinon il suffit d'extraire Taille/8 octets
					else{
			           	$xtract_size = $bytes_size;  
					}
					
					// 2 decoupages : d'une part une chaine couverte par l'assertion et d'autre part celle partiellement couverte
					// qui constituera le nouveau buffer.
    				if( $xtract_size-1 == 0){
            			$data = '';
            			$buffer = fread( $this->_fhandler, 1);            			
            		}
            		else{
            			$data = fread( $this->_fhandler, ($xtract_size-1));
            			$buffer = fread( $this->_fhandler, 1);
            		}
            		
            		$unpack = call_user_func( array($this,$this->_unpack_fn), $pattern, $this->_ubuffer.$data.$buffer);
            		//$unpack = unpack( $pattern, $this->_ubuffer.$data.$buffer);
				        
			        $this->_udelta = ($xtract_size*8)-$size_int+$this->_udelta;
			        $this->_ubuffer = $buffer;
    			}
    		}
    		elseif( $size_int == $this->_udelta){
    			$pattern = $this->_current_pack[$packFormat_str].'*val';
    			
    			$unpack = call_user_func( array($this,$this->_unpack_fn), $pattern, $this->_ubuffer);
    			//$unpack = unpack( $pattern, $this->_ubuffer);
		        
		        $this->_udelta = 0;
		        $this->_ubuffer = null;
    		}
    		// else : $size_int < $this->_udelta
    		else{
    			$pattern = $this->_current_pack[$packFormat_str].'1val';
    			
    			$unpack = call_user_func( array($this,$this->_unpack_fn), $pattern, $this->_ubuffer);
    			//$unpack = unpack( $pattern, $this->_ubuffer);
		        
		        $this->_udelta = $this->_udelta - $size_int;
    		}
    	}
    	else{    		
    		if( ($size_int%8) > 0){
	            $pattern = $this->_current_pack[$packFormat_str].'val';
	            	            
	            // data extraction
	            if( $bytes_size-1 <= 0){
        			$data = '';
        			$buffer = fread( $this->_fhandler, 1);            			
        		}
        		else{
        			$data = fread( $this->_fhandler, ($bytes_size-1));
        			$buffer = fread( $this->_fhandler, 1);
        		}

        		$unpack = call_user_func( array($this,$this->_unpack_fn), $pattern, $data.$buffer);
		        //$unpack = unpack( $pattern, $data.$buffer);
		        
		        $this->_udelta = 8 - ($size_int%8);
		        $this->_ubuffer = $buffer;
	        }
	        else{
	            $pattern = $this->_current_pack[$packFormat_str].'*val';
	            
	            // data extraction
		        $data = fread( $this->_fhandler, $bytes_size);
		        
		        $unpack = call_user_func( array($this,$this->_unpack_fn), $pattern, $data);
		        //$unpack = unpack( $pattern, $data);
	        }
    	}
           	
    	$d = array_shift($unpack);
        return $d;
    }
    
    
    
    /**
     * 
     * @param unknown $ruleID_int
     * @param unknown $assertID_int
     * @return NULL
     */
    protected function _callRule( $ruleID_int, $assertID_int)
    {
    	if( !method_exists( $this, 'rule_'.$ruleID_int)){
    		// error 
    		return null;
    	}
    	
    	call_user_func( array($this,'rule_'.$ruleID_int), $assertID_int);
    }
    
    /**
     * Check if e value is inside an interval
     * @param unknown $value_int
     * @param unknown $min_int
     * @param unknown $max_int
     * @param string $not
     * @return boolean
     */
    protected function _checkInterval( $value_int, $min_int, $max_int, $not = false)
    {
        if( ($value_int >= $min_int) && ($value_int <= $min_int)){
            return ($not == true)? false : true ;
        }
        else{
            return ($not == true)? true : false ;
        }
    }
    
    /**
     * Check if e value is inside an interval
     * @param unknown $value_int
     * @param unknown $min_int
     * @param boolean $not
     * @return boolean
     */
    protected function _checkEqual( $value_int, $bound_int, $not = false)
    {
        if( $value_int === $bound_int){
            return ($not == true)? false : true ;
        }
        else{
            return ($not == true)? true : false ;
        }
    }
    
    
    /**
     * 
     * @param unknown $seekpos_int
     * @return boolean
     */
    protected function _init( $seekpos_int, $endianess_const)
    {
        if( $this->_fhandler === false){
            // erreur
            return false;
        }
        
        if( $seekpos_int !== false){
            fseek( $this->_fhandler, $seekpos_int, SEEK_CUR);
        }
        
        $this->_endianess = $endianess_const;
        
        // control
        if( $this->_endianess !== self::UNKNOW_ENDIAN){
            
            if( $this->_srv_endianess == $this->_endianess){
                
                // default : _unpackStandard
                
                if( $this->_endianess == self::LITTLE_ENDIAN){
                    $this->_current_pack['v'] = 'v';
                    $this->_current_pack['V'] = 'V';
                }
                else{
                    // error : endianess not supported                
                }                
            }
            else{
                // unpack with endian translation needed             
                $this->_unpack_fn = '_unpackEndianTranslater';
            }
            
        }   
        else{            
            if( $this->_srv_endianess !== self::UNKNOW_ENDIAN){
                // dynamic and hypothetic unpack
            }
            else{
                // error : insolvable issue
            }           
        }
    }
    
    
    /**
     * 
     * @param string $name_str Name of the variable
     * @param string $type_str Type of the variable
     * @param int $size_str Size according with the type in bbits
     * @param int $length_str Length of the value if type is string
     * @param array $range_arr Range of values allowed
     */
    protected function _opeFormat( &$symbols, $name_str, $pattern_str, $size_int, $rangevalues_arr, $rangeinterval_arr, $length_int = 0)
    {
        if( $length_int > 0 ){
            $size_int = $size_int*8;
        }
        
        
        $data = $this->_unpackData( $size_int, $pattern_str);
        
        echo 'ope by format = '.$data.'<br>';
        
        // Test
        $v = true;
        if( count($rangevalues_arr)>0){
            $t = false;
            foreach( $rangevalues_arr as $rg)
            {
                $t |= $this->_checkEqual( $data, $rg['val'], $rg['not']);
            }
            $v &= $t;
        }
        
        if( count($rangeinterval_arr)>0){
            $t = false;
            foreach( $rangeinterval_arr as $rg)
            {
                $t |= $this->_checkInterval( $data, $rg['min'], $rg['max'], $rg['not']);
            }
            $v &= $t;
        }
        
        if( $length_int > 0){
            $v &= (strlen($data)<$length_int);
        }
        
        // Assign
        if( isset($unpack['val']) && ($name_str !== '')){
        	$symbols[$name_str] = $data;
        }
        
        return $v;
    }
    
    
	/**
	 * 
	 */
    protected function _opeValue( $value_str, $size_int, $pattern_str)
    {
        $data = $this->_unpackData( $size_int, $pattern_str);
         
        echo 'ope by val = '.$data.'<br> reference : '.$value_str.'<br>'; 
        return ( $data == $value_str)? true : false;
    }
    
    /**
     * 
     */
    public function passRules($filename_str)
    {
    	$this->_filename = $filename_str;
    	$this->_fhandler = fopen( $this->_filename, 'rb');
    	if( $this->_fhandler !== false){
    	    $this->_fopen = true;
    	}
    	else{
    	    // erreur
    	    return null;
    	}
    	
    	$starter = 'rule_'.$this->_firstruleID;
    	$next = call_user_func( array($this,'rule_'.$this->_firstruleID), 1);
    	
    	if( is_array($next) && isset($next['rid']) && isset($next['aid'])){
    		do {
    			 $next = call_user_func(array($this,'rule_'.$next['rid']),$next['aid']);
    		}
    		while( is_array($next) && isset($next['rid']) && isset($next['aid']) );
    	}    	
    	 
    	fclose($this->_fhandler);
    	 
    	return $this->_result;
    }
    
    
    
    public function setSrvEndianess( $endian)
    {
        if( $endian == self::LITTLE_ENDIAN){
            $this->_endianess = self::LITTLE_ENDIAN;
        }
        elseif( $endian == self::BIG_ENDIAN){
            $this->_endianess = self::BIG_ENDIAN;      
        }
    }
}

?>