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
        $this->setVHostTemplate();
    }

    /* Steps */
    private function setSteps() {

	    $this->steps =
	      array(
              array ( "CheckoutGit" => array(
                  "gitDeletorExecute" => true,
                  "gitDeletorCustomFolder" => "****dap_proj_cont_dir****"
              ), ) ,
              array ( "HostEditor" => array(
                  "hostEditorDeletionExecute" => true,
                  "hostEditorDeletionIP" => "****dap_apache_vhost_ip****",
                  "hostEditorDeletionURI" => "****dap_apache_vhost_url****.local",
              ) , ) ,
              array ( "VHostEditor" => array(
                  "virtualHostEditorDeletionExecute" => "boolean",
                  "virtualHostEditorDeletionDirectory" => "/etc/apache2/sites-available",
                  "virtualHostEditorDeletionTarget" => "****dap_apache_vhost_url****",
                  "virtualHostEditorDeletionVHostDisable" => false,
                  "virtualHostEditorDeletionSymLinkDirectory" => "/etc/apache2/sites-enabled",
                  "virtualHostEditorDeletionApacheCommand" => "apache2",
              ) , ) ,
	      );

	}

}