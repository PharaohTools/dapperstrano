<?php

namespace Model;

class SshKeyStoreAnyOS extends BaseLinuxApp
{

    // Compatibility
    public $os = array("any") ;
    public $linuxType = array("any") ;
    public $distros = array("any") ;
    public $versions = array("any") ;
    public $architectures = array("any") ;

    // Model Group
    public $modelGroup = array("Default") ;

    protected $key ;
    protected $userName ;
    protected $userHomeDir ;
    protected $searchLocations;
    protected $actionsToMethods = array("find" => "findKey") ;

    public function __construct($params)
    {
        parent::__construct($params);
        $this->autopilotDefiner = "SshKeyStore";
        $this->programDataFolder = "";
        $this->programNameMachine = "sshkeystore"; // command and app dir name
        $this->programNameFriendly = "SshKey Store"; // 12 chars
        $this->programNameInstaller = "Ssh Key Store";
        $this->initialize();
    }

    protected function askPublicKeyInstall()
    {
        $doPubKeyInstall = (isset($this->params["yes"]) && $this->params["yes"]==true) ?
            true : $this->askWhetherToInstallLinuxAppToScreen();
        if ($doPubKeyInstall == true) {
            return $this->installPublicKey();
        }
        return false ;
    }

    protected function askWhetherToInstallPublicKeyToScreen()
    {
        if (isset($this->params["yes"]) && $this->params["yes"]==true) {
            return true ;
        }
        $question = "Find SSH Public Key?";
        return self::askYesOrNo($question);
    }

    public function findKey()
    {

        $this->setKeyName() ;
        $this->setSearchLocations() ;
        $this->setPreferredLocation() ;
        return $this->doLocationSearch() ;

        // if isset prefer, put that array entry first
        // foreach location check for keyfile. if found, return it
    }

    protected function setSearchLocations()
    {
        if (isset($this->params["locations"])) {
            $searchLocations = explode(",", $this->params["locations"]) ;
        } else {
            $searchLocations = array("user", "ptbuild", "otheruser", "root", "specify") ;
        }
        $this->searchLocations = $searchLocations ;
    }

    protected function setPreferredLocation()
    {
        if (isset($this->params["prefer"])) {
            foreach ($this->searchLocations as &$loc) {
                if ($this->params["prefer"] == $loc) {
                    unset($loc) ;
                    $this->searchLocations = array_merge(array($this->params["prefer"]), $this->searchLocations) ;
                    return ;
                }
            }
        }
    }

    protected function setKeyName()
    {
        if (isset($this->params["key"])) {
            if (substr($this->params["key"], 0, 4) == 'KS::') {
                $this->params["key"] = substr($this->params["key"], 4) ;
            }
            $this->key = $this->params["key"];
        } else {
            $question = "Enter SSH Key Name:";
            $this->key = self::askForInput($question, true);
        }
    }

    protected function setUserHome()
    {
        if (isset($this->userName)) {
            // @todo a check if the user exists
            // @todo there is no user module
            $userFactory = new \Model\User() ;
            $user = $userFactory->getModel($this->params);
            $user->setUser($this->userName) ;
            $this->userHomeDir = $user->getHome();
        } elseif (isset($this->params["user-home"])) {
            $this->userHomeDir = $this->params["user-home"];
        } elseif (isset($this->params["guess"])) {
            // this wont work on windows, user module should be doing this
            $whoami = self::executeAndLoad("whoami") ;
            $whoami = str_replace("\n", "", $whoami) ;
            $whoami = str_replace("\r", "", $whoami) ;
            $comm = 'eval echo "~'.$whoami.'"' ;
            $this->userHomeDir = $this->executeAndLoad($comm) ;
            $this->userHomeDir = str_replace("\n", "", $this->userHomeDir) ;
            $this->userHomeDir = str_replace("\r", "", $this->userHomeDir) ;
        } else {
            $question = "Enter User home directory:";
            $this->userHomeDir = self::askForInput($question, true);
        }
    }

    protected function doLocationSearch()
    {
        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params);
        $logging->log("Trying keystore keys", $this->getModuleName());
        foreach ($this->searchLocations as &$loc) {
            switch ($loc) {
                case "user":
                    $this->setUserHome() ;
                    $kp = $this->userHomeDir.DS.".ssh".DS.$this->key ;
                    if (file_exists($kp)) {
                        $logging->log("User key found at $kp", $this->getModuleName());
                        return $kp ;
                    } else {
                        $logging->log("User key not found at $kp", $this->getModuleName());
                    }
                    break ;
                case "ptbuild":
                    $this->setUserHome() ;
                    $kp = PFILESDIR.trim(PTBCOMM).DS."keys".DS.$this->key ;
                    if (file_exists($kp)) {
                        $logging->log("PTBuild User key found at $kp", $this->getModuleName());
                        return $kp ;
                    } else {
                        $logging->log("PTBuild key not found at $kp", $this->getModuleName());
                    }
                    break ;
                case "otheruser":
                    $this->userName = (isset($this->params["otheruser"])) ? $this->params["otheruser"] : null ;
                    $this->setUserHome() ;
                    $kp = $this->userHomeDir.DS.".ssh".DS.$this->key ;
                    if (file_exists($kp)) {
                        $logging->log("Other User key found at $kp", $this->getModuleName());
                        return $kp ;
                    } else {
                        $logging->log("Other User key not found at $kp", $this->getModuleName());
                    }
                    break ;
                case "root":
                    $kp = "/root/.ssh".DS.$this->key ;
                    if (file_exists($kp)) {
                        $logging->log("Root key found at $kp", $this->getModuleName());
                        return $kp ;
                    } else {
                        $logging->log("Root key not found at $kp", $this->getModuleName());
                    }
                    break ;
                case "specify":
                    break ;
            }
        }
        return null;
    }
}
