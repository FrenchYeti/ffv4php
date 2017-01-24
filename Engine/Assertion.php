<?php

namespace FFV; 

class Assertion
{  
    const CTRL_TRUE = 'true';
    const CTRL_FALSE = 'false';
    const CTRL_ONRESULT = 'RESULT';
    
    const EQUAL_TRUE = 'TRUE';
    const EQUAL_FALSE = 'FALSE';
    
    const CTRL_GO_END = 'END';
    const CTRL_GO_EXIT = 'EXIT';
    
    const LITTLE_ENDIAN = '0';
    const BIG_ENDIAN = '1';
    const UNKNOW_ENDIAN = 'unknow';
    
    const CURRENT_POS = 'pos';    
    
    private $_id = null;
    private $_msg = null;
    
    private $_ctrl = array(
    	'true'=>array(),
        'false'=>array()
    );
    private $_ctrlDefault = array(
        'true'=>null,
        'false'=>null
    );
    
    
    private $_struct_body = array();
    
    private $_stack = array();

    private $_struct_attribute = array(
        'length' => null,
        'endianess' => null,
        'start' => null,
        'stop' => null
    );
    
    private $_tokens_model = array(
        '{ASSERT_ID}'=>null,
        '{ASSERT_OPE}'=>null,
        '{ASSERT_CTRL_TRUE}'=>null,
        '{ASSERT_CTRL_FALSE}'=>null,
    );

    private $_filepath = null;
    private $_controllers = array();
    
    
    
    public function __construct( $id, $filepath_str)
    {
        $this->_id = (int) $id; 
        $this->_filepath = $filepath_str;
    }
    
    /**
     * @version 1.0
     */
    public function getID()
    {
        return $this->_id ;   
    }
    
    /**
     * @version 1.0
     */
    public function setMessage( $msg_str)
    {
        $this->_msg = $msg_str;
    }
    
    /**
     * @version 1.0
     */
    public function setEndianess( $type_const)
    {
        if( ($type_const == self::LITTLE_ENDIAN) 
            || ($type_const == self::BIG_ENDIAN)){
            $this->_struct_attribute['endianess'] = $type_const;
        }
        else{
            echo '[PARSE_ERROR] ** Assertion endianess ** Unknow endianess in assert definition : '.$type_const.' <br>';
        }
    }
    
    /**
     * @method void Add controller on TRUE event of the assertion 
     * @param AssertionController $control_obj 
     * @author GBM
     * @version 1.0
     * @since 25/11/2013
     */
    public function addControlOnTRUE( $condition_str ,$call_str)
    {
        if(is_null($condition_str)){
            $this->_ctrlDefault['true'] = new AssertionController('',$call_str);
        }
        else{
            $this->_ctrl['true'][] = new AssertionController($condition_str,$call_str);
        }
        
    }
    
    
    /**
     * @method void Add controller on FALSE event of the assertion 
     * @param AssertionController $control_obj 
     * @author GBM
     * @version 1.0
     * @since 25/11/2013
     */
    public function addControlOnFALSE( $condition_str ,$call_str)
    {
        if( is_null($condition_str)){
            $this->_ctrlDefault['false'] = new AssertionController('',$call_str);
        }
        else{
            $this->_ctrl['false'][] = new AssertionController($condition_str,$call_str);
        }
    }
    
    
    
    /*
    public function addController( $valuename_str, $equal_str, $tpl_const, $arg_mixed = null)
    {
    	if( !isset($this->_controllers[$valuename_str])){
    		$this->_controllers[$valuename_str] = array();
    	}
    	$this->_controllers[$valuename_str][$equal_str] = array('tpl'=>$tpl_const,'arg'=>$arg_mixed);
    }
    */
    
    /**
     * @version 1.0
     */
    public function setStructAttr( $name_str, $value_str)
    {
        $this->_struct_attribute[$name_str] = $value_str;
    }
    
    
    // DEPRECATED
    public function setCtrlEvent( $type_const, $tpl_const, $arg_mixed = null)
    {
        if( ($type_const !== self::CTRL_FALSE) && ($type_const !== self::CTRL_TRUE)){
            echo '[PARSER_ERROR] ** Assertion controller ** ';
        }
        else{
            $this->_ctrl[$type_const] = array('tpl'=>$tpl_const,'arg'=>$arg_mixed);
        }       
    }

    /**
     * @version 1.0
     */
    public function parseAssert( $rawassert_str)
    {
        $parser = AssertionParser::getInstance();
        
        $this->_stack = $parser->parseGlobalAssertion($rawassert_str,$this->_filepath);           
    }
    
    /**
     * @version 1.0
     */
    public function getStructAttr( $name_str)
    {
        return $this->_struct_attribute[$name_str];
    }

    
    /**
     * 
     * @return multitype:NULL
     */
    public function getControlsOnTRUE()
    {
        return $this->_ctrl['true'];
    } 
    
    public function getDefaultControlOnTRUE()
    {
        return $this->_ctrlDefault['true'];
    }
    
    /**
     * 
     * @return multitype:NULL
     */
    public function getControlsOnFALSE()
    {
        return $this->_ctrl['false'];
    }
    
    public function getDefaultControlOnFALSE()
    {
        return $this->_ctrlDefault['false'];
    }
    
    /**
     * @version 1.0
     */
    public function getStack()
    {
        return $this->_stack;
    }
    
}

?>