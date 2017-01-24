<?php

namespace FFV;

/**
 *
 * @author gb-michel
 *        
 */
class AssertionController
{
    const CALL_END = 'END'; 
    const CALL_EXIT = 'EXIT';

    
    private $_tokens = array('{RULE_ID}'=>null,'{ASSERT_ID}'=>null);
      
    private $_calltpl = null;
    private $_condition = null;
    
    
    /**
     * @method array Parse and validate format of an ID ( 5.6 )
     */
    private function parseCallIDs( $id_str)
    {
        $tmp_ids = array();
        preg_match( '/^\d+\.\d+$/', $id_str, $tmp_ids);
    
        if( $id_str !== $tmp_ids[0]){
            // Error parent
            return null;
        }
        else{
            $tmp = explode( '.', $id_str);
            return array(
                'parent_id'=>(int)$tmp[0],
                'child_id'=>(int)$tmp[1],
            );
        }
    }
    
    
    
    /**
     * 
     * @param unknown $condition_str
     * @param unknown $go_str
     */
    public function __construct( $condition_str, $go_str)
    {
        if( $condition_str !== ''){
            $this->_condition = LogicalExpression::parse($condition_str);
        }        
        
        if( $go_str == self::CALL_END){
            $this->_calltpl = Module::TPL_CTRL_END;
        }   
        elseif( $go_str == self::CALL_EXIT){
            $this->_calltpl = Module::TPL_CTRL_EXIT;
        }
        else{
            $this->_calltpl = Module::TPL_CTRL_CALL;

            $go = $this->parseCallIDs($go_str);
            $this->_tokens['{RULE_ID}'] = $go['parent_id'];
            $this->_tokens['{ASSERT_ID}'] = $go['child_id'];
        }
    }
    
    
    /**
     * 
     * @return Ambigous <Ambigous, LogicalExpression>
     */
    public function getCondition()
    {
        return $this->_condition;
    }
    
    
    /**
     * 
     */
    public function getCallTemplate()
    {
        return $this->_calltpl;
    }
    
    public function getCallTokens()
    {
        return $this->_tokens;
    }
}

?>