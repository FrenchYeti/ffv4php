<?php

namespace FFV;

/**
 *
 * @author gb-michel
 *        
 */
interface GeneratorInterface
{
    
    public function makeRulesetFile();
    
    public function createRulesetFile($name_str);
    
    public function writeRulesetFile($ruleset_obj);
}

?>