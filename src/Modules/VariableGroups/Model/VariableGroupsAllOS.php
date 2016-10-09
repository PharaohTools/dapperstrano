<?php

Namespace Model;

class VariableGroupsAllOS extends Base {

    // Compatibility
    public $os = array("any") ;
    public $linuxType = array("any") ;
    public $distros = array("any") ;
    public $versions = array("any") ;
    public $architectures = array("any") ;

    // Model Group
    public $modelGroup = array("any") ;

    public function __construct($params) {
        parent::__construct($params);
    }

    public function getVariables() {
        $vg = $this->getVariableGroups() ;
        $variables = array() ;
        if (is_array($vg) && count($vg)>0) {
            foreach ($vg as $group) {
                $set = $this->loadVariableSet($group) ;
                $variables = array_merge($variables, $set) ; } }
        return $variables ;
    }

    protected function getVariableGroups() {
        $vg = null ;
        if (isset($this->params["vargroups"])) { $vg = $this->params["vargroups"] ; }
        if (isset($this->params["vars"])) { $vg = $this->params["vars"] ; }
        if (isset($this->params["variables"])) { $vg = $this->params["variables"] ; }
        if (!is_null($vg)) { return explode(",", $vg) ; }
        return $vg ;
    }

    protected function loadVariableSet($set) {
        $set = $this->findVariableFilePath($set) ;
        if (file_exists($set)) {
            $ext = pathinfo($set, PATHINFO_EXTENSION);
            switch ($ext) {
                case "php" :
                    require $set ;
                    break;
                default:
                    break; } }
        if (isset($variables) &&(is_array($variables))) { return $variables ; }
        return array();
    }

    protected function findVariableFilePath($set) {
        if (file_exists($set)) {
            return $set ; }
        else if (file_exists(getcwd().DS.$set)) { return getcwd().DS.$set ; }
        return null;
    }

}