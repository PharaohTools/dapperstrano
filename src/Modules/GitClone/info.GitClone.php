<?php

Namespace Info;

class GitCloneInfo extends Base {

    public $hidden = false;

    public $name = "GitClone Source Control Clone Functions";

    public function _construct() {
      parent::__construct();
    }

    public function routesAvailable() {
      return array( "GitClone" => array_merge(parent::routesAvailable(), array("clone", "co") ) );
    }

    public function routeAliases() {
      return array("git-clone" => "GitClone", "gitclone" => "GitClone", "git" => "Gitclone");
    }

    public function helpDefinition() {
      $help = <<<"HELPDATA"
  This command is part of Default Modules and handles Checkout Functions.

  clone, co

          - perform a checkout into configured projects folder. If you don't want to specify target dir but do want
          to specify a branch, then enter the text "none" as that parameter.
          example: dapperstrano gitclone co https://github.com/phpengine/yourmum {optional target dir} {optional branch}
          example: dapperstrano gitclone co https://github.com/phpengine/yourmum none {optional branch}

HELPDATA;
      return $help ;
    }

}
