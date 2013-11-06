<?php

/*************************************
*      Generated Autopilot file      *
*     ---------------------------    *
*Autopilot Generated By Dapperstrano *
*     ---------------------------    *
*************************************/

Namespace Core ;

class AutoPilotConfigured extends AutoPilot {

    public $steps ;

    public function __construct() {
	      $this->setSteps();
    }

    /* Steps */
    private function setSteps() {

	    $this->steps =
	      array(
          array ( "Version" => array(
                    "versionExecute" => true,
                    "versionAppRootDirectory" => "****dap_proj_cont_dir****",
                    "versionArrayPointToRollback" => "1",
                    "versionLimit" => "****dap_version_num_revisions****",
          ) , ) ,
              array ( "ApacheControl" => array(
                  "apacheCtlRestartExecute" => true,
              ) , ) ,
	      );

	  }


}
