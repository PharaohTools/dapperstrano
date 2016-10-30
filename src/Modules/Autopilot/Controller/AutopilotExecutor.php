<?php

namespace Controller ;

use Core\View;

class AutopilotExecutor extends Base
{

    public function executeAuto($pageVars, $autopilot, $test = false)
    {

        $thisModel = $this->getModelAndCheckDependencies("Autopilot", $pageVars) ;
        // if we don't have an object, its an array of errors
        if (is_array($thisModel)) {
            return $this->failDependencies($pageVars, $this->content, $thisModel) ;
        }
        $this->content["package-friendly"] = ($test) ? "Autopilot Test Suite" : "Autopilot" ;

        $this->registeredModels = $autopilot->steps ;
        $res1 = $this->checkForRegisteredModels($thisModel->params);
        if ($res1 !== true) {
            $this->content["result"] = false ;
            return array ("type"=>"view", "view"=>"autopilot", "pageVars"=>$this->content);
        }

        $res2 = ($test) ?
            $this->executeMyTestsAutopilot($autopilot, $thisModel->params):
            $this->executeMyRegisteredModelsAutopilot($autopilot, $thisModel->params, $thisModel);

        $this->content["result"] = $res2 ;
        return array ("type"=>"view", "view"=>"autopilot", "pageVars"=>$this->content);
    }

    protected function executeMyRegisteredModelsAutopilot($autoPilot, $autopilotParams, $thisModel)
    {
        $dataFromThis = array();
        if (isset($autoPilot->steps) && is_array($autoPilot->steps) && count($autoPilot->steps)>0) {
            $steps = $this->orderSteps($autoPilot->steps);
//            var_dump("after order:", $steps) ;
            $steps = $this->expandLoops($steps);
//            var_dump("after expand:", $steps) ;

            $counter = 0 ;
            foreach ($steps as $modelArray) {
                $logFactory = new \Model\Logging() ;
                $logging = $logFactory->getModel($thisModel->params) ;
                $autoFactory = new \Model\Autopilot() ;
                $autoModel = $autoFactory->getModel($thisModel->params, "Default") ;

                $name_or_mod = $this->getNameOrMod($modelArray, $autoModel) ;

                $label = (isset($name_or_mod["step-name"])) ? "Label: {$name_or_mod["step-name"]}" : "" ;
                if (strlen($label) > 0) {
                    $logging->log("{$label}", "Autopilot") ;
                }
                $module = (isset($name_or_mod["module"])) ? "Module: {$name_or_mod["module"]}" : "" ;
                if (strlen($module) > 0) {
                    $logging->log("{$module}", "Autopilot") ;
                }

                $should_run = $this->onlyRunWhen($modelArray, $autoModel) ;
                if ($should_run["should_run"] != true) {
                    $controlKeys = array_keys($modelArray) ;
                    $control = $controlKeys[0] ;
                    $actionKeys = array_keys($modelArray[$controlKeys[0]]) ;
                    $action = $actionKeys[0] ;

                    $step_out["params"]["route"]["control"] = $control ;
                    $step_out["params"]["route"]["action"] = $action ;
                    $step_out["status"] = true ;
                    $step_out["out"] = "No need to run this step" ;
                } else {
                    $step_out = $this->executeStep($modelArray, $autopilotParams) ;
                }

                if (isset($name_or_mod["step-name"]) || isset($name_or_mod["module"])) {
                    echo "\n" ;
                }

                if (isset($step_out["status"]) && $step_out["status"]==false) {
                    $step_out["error"] = "Received exit code: ".\Core\BootStrap::getExitCode();
                    $dataFromThis[] = $step_out ;
                    return $dataFromThis ;
                }

                $dataFromThis[] = $step_out ;

                $counter ++ ;
            }
        } else {
            \Core\BootStrap::setExitCode(1);
            $step = array() ;
            $step["out"] = "No Steps defined in autopilot";
            $step["status"] = false ;
            $step["error"] = "Received exit code: 1 " ;
            $dataFromThis[] = $step ;
        }
        return $dataFromThis ;
    }

    protected function onlyRunWhen($current_params, $autoModel)
    {
//        echo "Only running when" ;
        $mod_ray_is = array_keys($current_params) ;
        $mod_is = $mod_ray_is[0] ;
        $act_ray_is = array_keys($current_params[$mod_is]) ;
        $act_is = $act_ray_is[0] ;
        if (isset($current_params[$mod_is][$act_is]["when"]) || isset($current_params[$mod_is][$act_is]["not-when"]) || isset($current_params[$mod_is][$act_is]["not_when"])) {
            $logFactory = new \Model\Logging() ;
            $logging = $logFactory->getModel(array(), "Default") ;
            $name_or_mod = $this->getNameOrMod($current_params, $autoModel) ;
            $module = (isset($name_or_mod["module"])) ? " Module: {$name_or_mod["module"]}" : "" ;
            $name_text = (isset($name_or_mod["step-name"])) ? " Name: {$name_or_mod["step-name"]}" : "" ;
            $logging->log("When Condition found for Step {$module}{$name_text}", "Autopilot") ;
            if (isset($current_params[$mod_is][$act_is]["when"])) {
                $when_result = $autoModel->transformParameterValue($current_params[$mod_is][$act_is]["when"]) ;
                $when_text = ($when_result == true) ? "Do Run" : "Don't Run" ;
            } else {
                if (isset($current_params[$mod_is][$act_is]["not_when"])) {
                    $current_params[$mod_is][$act_is]["not-when"] = $current_params[$mod_is][$act_is]["not_when"] ;
                }
                $when_result = $autoModel->transformParameterValue($current_params[$mod_is][$act_is]["not-when"]) ;
                $when_text = ($when_result == false) ? "Do Run" : "Don't Run" ;
                $when_result = !$when_result ;
            }
            $logging->log("When Condition evaluated to {$when_text}", "Autopilot") ;
            $return_stat["should_run"] = $when_result ;
        } else {
            $return_stat["should_run"] = true ;
        }
        return $return_stat ;
    }

    protected function getNameOrMod($stepDetails, $autoModel)
    {
        $name_or_mod = array() ;
        $currentControls = array_keys($stepDetails) ;
        $currentControl = $currentControls[0] ;
        $currentActions = array_keys($stepDetails[$currentControl]) ;
        $currentAction = $currentActions[0] ;
        $modParams = $stepDetails[$currentControl][$currentAction] ;
        $name_or_mod["module"] = $currentControl ;
        if (isset($modParams["step-name"])) {
            $name_or_mod["step-name"] = $autoModel->transformParameterValue($modParams["step-name"]) ;
        }
        if (isset($modParams["label"])) {
            $name_or_mod["step-name"] = $autoModel->transformParameterValue($modParams["label"]) ;
        }
        return $name_or_mod ;
    }

    protected function orderSteps($steps)
    {
        $new_steps = array() ;
        // add pre
        foreach ($steps as $step) {
            if ($this->isPreRequisite($step)) {
                $new_steps[] = $step ;
            }
        }
        // add run
        foreach ($steps as $step) {
            if (!$this->isPreRequisite($step) && !$this->isPostRequisite($step)) {
                $new_steps[] = $step ;
            }
        }
        // add post
        foreach ($steps as $step) {
            if ($this->isPostRequisite($step)) {
                $new_steps[] = $step ;
            }
        }
        return $new_steps ;
    }
    protected function isPreRequisite($step)
    {
        if (isset($step["pre"]) && $step["pre"] == true) {
            return true ;
        }
        if (isset($step["prerequisite"]) && $step["prerequisite"] == true) {
            return true ;
        }
        return false ;
    }

    protected function isPostRequisite($step)
    {
        if (isset($step["post"]) && $step["post"] == true) {
            return true ;
        }
        if (isset($step["postrequisite"]) && $step["postrequisite"] == true) {
            return true ;
        }
        if (isset($step["handler"]) && $step["handler"] == true) {
            return true ;
        }
        return false ;
    }

    protected function expandLoops($steps)
    {
        $new_steps = array() ;
        foreach ($steps as $step) {
            $loopExpanded = $this->getLoopRay($step) ;
            $new_steps = array_merge($new_steps, $loopExpanded) ;
        }
        return $new_steps ;
    }

    protected function executeStep($modelArray, $autopilotParams)
    {

        $currentControls = array_keys($modelArray) ;
        $currentControl = $currentControls[0] ;
        $currentActions = array_keys($modelArray[$currentControl]) ;
        $currentAction = $currentActions[0] ;
        $modParams = $modelArray[$currentControl][$currentAction] ;
        $modParams["layout"] = "blank" ;

        unset($autopilotParams["af"]) ;
        unset($autopilotParams["autopilot-file"]) ;

        $modParams = array_merge($modParams, $autopilotParams) ;
//        if ($currentControl == "User") { var_dump("mp", $modParams) ; }
//
        $modParams = $this->formatParams($modParams) ;
        $params = array() ;
        $params["route"] =
            array(
                "extraParams" => $modParams ,
                "control" => $currentControl ,
                "action" => $currentAction ) ;
        $step = array() ;
        $step["out"] = $this->executeControl($currentControl, $params);
        $step["status"] = true ;
        $step["params"] = $params;

        if (\Core\BootStrap::getExitCode() !== 0) {
            $step["status"] = false ;
            $step["error"] = "Received exit code: ".\Core\BootStrap::getExitCode();
            return $step ;
        }

        return $step ;
    }

    protected function getLoopRay($modelArray)
    {
        $newParams = array();
        $currentControls = array_keys($modelArray) ;
        $currentControl = $currentControls[0] ;
        $currentActions = array_keys($modelArray[$currentControl]) ;
        $currentAction = $currentActions[0] ;
        $modParams = $modelArray[$currentControl][$currentAction] ;
        foreach ($modParams as $origParamKey => $origParamVal) {
            $res = $this->findLoopInParameterValue($origParamVal) ;
            if ($res !== false) {
                $logFactory = new \Model\Logging() ;
                $logging = $logFactory->getModel(array(), "Default") ;
                $logging->log("Found loop for parameter {$origParamKey}", "Autopilot") ;
                $liRay = $this->getArrayOfLoopItems($modParams) ;
                foreach ($liRay as $loop_iteration) {
                    $logging->log("Adding loop with value {$loop_iteration}", "Autopilot") ;
                    $tempParams = $modParams ;
                    $tempParams[$origParamKey] = $this->swapLoopPlaceholder($origParamVal, $loop_iteration) ;
                    $newParams[][$currentControl][$currentAction] = $tempParams ;
                }
            }
        }
        if (count($newParams)>0) {
//            var_dump("np", $newParams) ;
            return $newParams ;
//            return $newParams ;
        } ;
//            var_dump("ma", array($modelArray)) ;
//        return $modelArray ;
        return array($modelArray) ;
    }

    protected function getArrayOfLoopItems($modParams)
    {
        if (isset($modParams["loop"])) {
            $litems =  explode(",", $modParams["loop"]) ;
//            var_dump("li", $litems) ;
            return $litems ;
        }
        $logFactory = new \Model\Logging() ;
        $logging = $logFactory->getModel(array(), "Default") ;
        $logging->log("Empty array of Loop items specified", "Autopilot", LOG_FAILURE_EXIT_CODE) ;
        return array() ;
    }

    public function findLoopInParameterValue($paramValue)
    {
        if (is_array($paramValue)) {
            var_dump($paramValue) ;
        }
        if ((strpos($paramValue, '{{ loop }}') !== false) || (strpos($paramValue, '{{loop}}') !== false)) {
            return true ;
        }
        return false ;
    }

    public function swapLoopPlaceholder($paramValue, $newVal)
    {
        $paramValue = str_replace('{{ loop }}', $newVal, $paramValue) ;
        $paramValue = str_replace('{{loop}}', $newVal, $paramValue) ;
        return $paramValue ;
    }

    protected function executeMyTestsAutopilot($autoPilot, $autopilotParams)
    {
        $dataFromThis = array() ;
        if (isset($autoPilot->tests) && is_array($autoPilot->tests) && count($autoPilot->tests)>0) {
            foreach ($autoPilot->tests as $modelArray) {
                $currentControls = array_keys($modelArray) ;
                $currentControl = $currentControls[0] ;
                $currentActions = array_keys($modelArray[$currentControl]) ;
                $currentAction = $currentActions[0] ;
                $modParams = $modelArray[$currentControl][$currentAction] ;
                $of = array("output-format" => "AUTO") ;
                $modParams = $this->formatParams(array_merge($modParams, $autopilotParams, $of)) ;
                $params = array() ;
                $params["route"] = array(
                    "extraParams" => $modParams ,
                    "control" => $currentControl ,
                    "action" => $currentAction ) ;
//                $dataFromThis .= $this->executeControl($currentControl, $params);
                if (\Core\BootStrap::getExitCode() !== 0) {
                        $dataFromThis .= "Received exit code: ".\Core\BootStrap::getExitCode();
                        break ;
                }
                $step = array() ;
                $step["out"] = $this->executeControl($currentControl, $params);
                $step["status"] = true ;
                $step["params"] = $params;
                if (\Core\BootStrap::getExitCode() !== 0) {
                    $step["status"] = false ;
                    $step["error"] = "Received exit code: ".\Core\BootStrap::getExitCode();
                    $dataFromThis[] = $step ;
                    return $dataFromThis ;
                }
                $dataFromThis[] = $step ;
            }
        } else {
            \Core\BootStrap::setExitCode(1);
            $step = array() ;
            $step["out"] = "No Tests defined in autopilot";
            $step["status"] = false ;
            $step["error"] = "Received exit code: 1 " ;
            $dataFromThis[] = $step ;
        }
        return $dataFromThis ;
    }

    protected function formatParams($params)
    {
//        var_dump("pars:", $params) ;
//        $currentControls = array_keys($params) ;
//        $currentControl = $currentControls[0] ;
//        $currentActions = array_keys($params) ;
//        $currentAction = $currentActions[0] ;
//        $modParams = $params[$currentAction] ;
//        var_dump("mp:", $params) ;
        $newParams = array();
        foreach ($params as $origParamKey => $origParamVal) {
//            var_dump('fp:',  $origParamKey , $origParamVal) ;
//            if (!is_array($origParamVal)) {
                $newParams[] = '--'.$origParamKey.'='.$origParamVal ;
//        }
//            else {
//                $a = $origParamVal;
//                $r=array();
//                array_walk($a, create_function('$b, $c', 'global $r; $r[]="$c:$b";'));
//                $newParamVal = implode(', ', $r);
//                $curp ='--'.$origParamKey.'='.$newParamVal ;
//                $newParams[] =  $curp ;
//                var_dump($curp); }
        }
        $newParams[] = '--yes' ;
        $newParams[] = "--hide-title=yes";
        $newParams[] = "--hide-completion=yes";
        return $newParams ;
    }

    public function executeControl($controlToExecute, $pageVars = null)
    {
        $control = new \Core\Control();
        $controlResult = $control->executeControl($controlToExecute, $pageVars);
//        var_dump("xc: ",  $controlResult) ;
        if ($controlResult["type"]=="view") {
            return $this->executeView($controlResult["view"], $controlResult["pageVars"]);
        } elseif ($controlResult["type"]=="control") {
            $this->executeControl($controlResult["control"], $controlResult["pageVars"]);
        }
    }

    public function executeView($view, array $viewVars)
    {
        $viewObject = new View();
        $templateData = $viewObject->loadTemplate($view, $viewVars) ;


//        var_dump('td:', $templateData) ;

//        @todo this should parse layouts properly but doesnt. so, templates only for autos for now
//        if ($view == "parallaxCli") {
//            var_dump("tdata: ", $templateData) ;
//            die() ;
//        }
//        $data = $viewObject->loadLayout ( "blank", $templateData, $viewVars) ;
        return $templateData ;
    }
}
