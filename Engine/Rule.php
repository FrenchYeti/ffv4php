<?php

namespace FFV;

class Rule
{
    private $_id = null;
    
    private $_asserts = array();
    
    private $_tokens = array(
        '{RULE_REF_ID}'=>null,
        '{RULE_REF_TYPE}'=>null,
        '{RULE_REF_SPECID}'=>null,
        '{RULE_REF_SPECTITLE}'=>null,
        '{RULE_REF_COMMENT}'=>null,
        '{RULE_INFO_AUTHORS}'=>null,
        '{RULE_INFO_SINCE}'=>null,
        '{RULE_INFO_VERSION}'=>null,
        '{RULE_INFO_DESCRIPTION}'=>null,
    );

    private $_tokens_data = array(
    	'tokens'=>array(),
        'values'=>array()
    );
    
 
    
    
    /**
     * Return tokens used in search/replace context
     *
     * @return multitype:multitype:unknown
     */
    private function makeTokens()
    {
        $tokens = array();
        $values = array();
        $i = 0;
    
        foreach( $this->_tokens as $tok=>$val)
        {
            $tokens[$i] = $tok;
            $values[$i] = $val;
            $i++;
        }
    
        $this->_tokens_data = array('tokens'=>$tokens,'values'=>$values);
    }
    
    
    /**
     * Parse un fichier de regle et retourne les donnees dans un tableau
     * @method void Parse les regles
     * @author GB Michel
     * @version 1.0
     * @since 09/06/2013
     */
    private function parseRuleFile( $ruleFile_str)
    {        
        $rf = new DOMDocument();
        $rf->load( $ruleFile_str);
               
        // Check if rule file is valid
        if( $rf->schemaValidate( FFE_RULESETS_DIRNAME.'/rulesSchema.xsd') === false){
            // Ajouter aux logs que le fichier $file n'as pas ete charge, car non conforme au schema
            return false;
        }
    
        // Parse rule file
        $xp = new DOMXPath( $rf);
        foreach( $rf->getElementsByTagName('rule') as $ruleNode){
    
            $data = array();
            $data['reference'] = array();

            $data['reference']['reftype'] = $xp->evaluate( 'reference/reftype', $ruleNode)->item(0)->nodeValue;
            $data['reference']['refid'] = $xp->evaluate( 'reference/refid', $ruleNode)->item(0)->nodeValue;
            $data['reference']['specid'] = $xp->evaluate( 'reference/specid', $ruleNode)->item(0)->nodeValue;
            $data['reference']['spectitle'] = $xp->evaluate( 'reference/spectitle', $ruleNode)->item(0)->nodeValue;
            $data['reference']['comment'] = $xp->evaluate( 'reference/comment', $ruleNode)->item(0)->nodeValue;
             
            // add to rule tokens
            $this->addMetaToken( 'RULE_REF_TYPE', $data['reference']['reftype']);
            $this->addMetaToken( 'RULE_REF_ID', $data['reference']['refid']);
            $this->addMetaToken( 'RULE_REF_SPECID', $data['reference']['specid']);
            $this->addMetaToken( 'RULE_REF_SPECTITLE', $data['reference']['spectitle']);
            $this->addMetaToken( 'RULE_REF_COMMENT', $data['reference']['comment']);
    
            // Data in info tag
            $data['info'] = array();
    
            $nodeList = $xp->evaluate( 'info/since', $ruleNode);
            if( $nodeList->length > 0){
                $this->addMetaToken( 'RULE_INFO_SINCE', $nodeList->item(0)->nodeValue);
            }
    
            $nodeList = $xp->evaluate( 'info/version', $ruleNode);
            if( $nodeList->length > 0){
                $this->addMetaToken( 'RULE_INFO_VERSION', $nodeList->item(0)->nodeValue);
            }
    
            $nodeList = $xp->evaluate( 'info/description', $ruleNode);
            if( $nodeList->length > 0){
                $this->addMetaToken( 'RULE_INFO_DESCRIPTION', $nodeList->item(0)->nodeValue);
            }
    
            $data['info']['author'] = array();
            $authors = '';
            foreach( $rf->getElementsByTagName('author') as $rn){
                $data['info']['author'][] = $rn->nodeValue;
                $authors .= $rn->nodeValue;
    
            }
            $this->addMetaToken( 'RULE_INFO_AUTHORS', $authors);
    
    
            // Get rule ID ( ID of set of assert) is an attribute of asserts element
            $tmp_id = $xp->evaluate( 'asserts/ids', $ruleNode)->item(0)->nodeValue;
            if( is_numeric( $tmp_id)){
                $this->_id = (int) $tmp_id;
                $this->addMetaToken( 'RULE_ID', (int) $tmp_id);
                unset($tmp_id);
            }
            else{
                // error
            }
    
            $data['asserts'] = array();
            foreach( $rf->getElementsByTagName('assert') as $rn){
                                 
                // get ID of assert
                $tmp_ids = $xp->evaluate( 'id', $rn)->item(0)->nodeValue;
                if( is_numeric( $tmp_ids)){
                    $sid = (int) $tmp_ids;
                    unset($tmp_ids);
                }
                else{
                    // error
                }
                
                $assert = new Assertion( $sid, $ruleFile_str);
    
                // start attribute
                $nodeList = $xp->evaluate( 'struct/@start', $rn);
                if( $nodeList->length > 0){
                	$start = $nodeList->item(0)->nodeValue;
                	if( $start !== Assertion::CURRENT_POS){
                		$assert->setStructAttr( 'start', (int)$start);
                	}    
                	else{
                	    $assert->setStructAttr( 'start', 'false');
                	}               
                }
                else{
                    $assert->setStructAttr( 'start', 'false');
                }
    
                // length attribute
                $nodeList = $xp->evaluate( 'struct/@length', $rn);
                if( $nodeList->length > 0){
                    $assert->setStructAttr( 'length', $nodeList->item(0)->nodeValue);
                }
    
                // endianess attribute
                $nodeList = $xp->evaluate( 'struct/@endianess', $rn);
                if( $nodeList->length > 0){
                    $end = $nodeList->item(0)->nodeValue;
                    if( $end == 'little'){
                        $assert->setEndianess(Assertion::LITTLE_ENDIAN);
                    }
                    elseif( $end == 'big'){
                        $assert->setEndianess(Assertion::BIG_ENDIAN);
                    }
                    else{
                        $assert->setEndianess(Assertion::UNKNOW_ENDIAN);
                    }
                }
                else{
                    echo '[PARSE_ERROR] ** Assertion declaration ** Endianess undefined <br>';
                }
    
                // struct attribute
                $nodeList = $xp->evaluate( 'struct/@stop', $rn);
                if( $nodeList->length > 0){
                    $assert->setStructAttr( 'stop', $nodeList->item(0)->nodeValue);
                }
    

                $nodeList = $xp->evaluate( 'message', $rn);
                if( $nodeList->length > 0){
                    $assert->setMessage( $nodeList->item(0)->nodeValue);
                }
                
                
                $assert->parseAssert( $xp->evaluate( 'struct', $rn)->item(0)->nodeValue);
                 
                 
                 
                // !!!!!!!!!!!!!!!!!!!!!!! NEW !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!              
                $ctrls_true = $xp->evaluate('controllers/control-true/if',$rn);               
                if( $ctrls_true->length > 0){
                    
                    foreach( $ctrls_true as $if)
                    {
                        $raw_condition = $if->attributes->getNamedItem('condition')->nodeValue;
                        $assert->addControlOnTRUE($raw_condition,$if->nodeValue);
                    }
                }
                $ctrls_true_default = $xp->evaluate('controllers/control-true/default',$rn)->item(0);
                $assert->addControlOnTRUE(null,$ctrls_true_default->nodeValue);

                
                $ctrls_false = $xp->evaluate('controllers/control-false/if',$rn);                
                if( $ctrls_false->length > 0){
                
                    foreach( $ctrls_false as $if)
                    {
                        $raw_condition = $if->attributes->getNamedItem('condition')->nodeValue;
                        $assert->addControlOnFALSE($raw_condition,$if->nodeValue);
                    }
                }
                $ctrls_false_default = $xp->evaluate('controllers/control-false/default',$rn)->item(0);
                $assert->addControlOnFALSE(null,$ctrls_false_default->nodeValue);              
                // =================== END NEW ==================================

                
                
                if( in_array( $assert->getID(), array_keys($this->_asserts))){
                    // Error on index
                }
                else{
                    $this->_asserts[$assert->getID()] = $assert;
                }
                 
            }
    
            // tri des cles
            ksort( $this->_asserts, SORT_NUMERIC);           
        }
        
        $this->makeTokens();
    }
    
    /**
     * 
     */
    public function __construct( $filepath_str)
    {
    	$this->parseRuleFile($filepath_str);
    }
    
    
    /**
     * Return rule's ID as defined in Rule file
     * 
     * 
     */
    public function getID()
    {
         return $this->_id;
    }
    
    
    /**
     * 
     */
    public function addMetaToken( $tokenname_str, $value_str)
    {        
        $this->_tokens['{'.$tokenname_str.'}'] = $value_str;
    }
    
    
    public function getTokens()
    {
        return $this->_tokens_data['tokens'];
    }
    
    public function getTokensData()
    {
        return $this->_tokens_data['values'];
    }
    
    
    /**
     * 
     */
    public function addAssert( $assert_obj)
    {
        $this->_asserts[$assert_obj->getID()] = $assert_obj;
    }
    
    
    /**
     * 
     * @return array
     */
    public function getAsserts()
    {
        return $this->_asserts;
    }
    
    
    /**
     * 
     * @param int $id_int ID of assert
     * @return array Pased data of a assertion
     */
    public function getAssertByID( $id_int)
    {
        return $this->_asserts[$id_int];
    }
    
}

?>