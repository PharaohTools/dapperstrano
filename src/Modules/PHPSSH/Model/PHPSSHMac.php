<?php

namespace Model;

class PHPSSHMac extends PHPSSHUbuntu
{

    // Compatibility
    public $os = array("Darwin") ;
    public $linuxType = array("any") ;
    public $distros = array("any") ;
    public $versions = array("any") ;
    public $architectures = array("any") ;

    // Model Group
    public $modelGroup = array("Default") ;

    public function __construct($params)
    {
        parent::__construct($params);
        $this->installCommands = $this->getInstallCommands() ;
        $this->uninstallCommands = $this->getUninstallCommands() ;
        $this->initialize();
    }

    protected function getInstallCommands()
    {
        $php_vers = $this->getPHPVersion() ;
        $ret = array(
            array("method"=> array("object" => $this, "method" => "ensureMacPorts", "params" => array()) ),
            array("method"=> array("object" => $this, "method" => "packageAdd", "params" => array("MacPorts", array("php{$php_vers}-ssh2"))) ),
            array("method"=> array("object" => $this, "method" => "ensurePHPIniFile", "params" => array()) ),
            array("method"=> array("object" => $this, "method" => "addPHPIniExtension", "params" => array()) ) );
        return $ret ;
    }

    protected function getUninstallCommands()
    {
        $php_vers = $this->getPHPVersion() ;
        $ret = array(
            array("method"=> array("object" => $this, "method" => "packageRemove", "params" => array("MacPorts", array("php{$php_vers}-ssh2"))) ),
            array("method"=> array("object" => $this, "method" => "removePHPIniExtension", "params" => array()) ),
        );
        return $ret ;
    }

    protected function getPHPVersion()
    {
        $text = $this->executeAndLoad("php -v") ;
        $lines = explode("\n", $text) ;
        foreach ($lines as $line) {
            if (substr($line, 0, 5) == "PHP 5") {
                $phpline = $line ;
                break ;
            }
        }
//        var_dump(substr($phpline, 0, 5), substr($phpline, 4, 3)) ;
        $php_version_string = substr($phpline, 4, 3) ;
        $digits_only = str_replace(".", "", $php_version_string) ;
        return $digits_only ;
    }

    public function ensurePHPIniFile()
    {
        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params);
        $logging->log("Checking PHP Ini File existence", $this->getModuleName()) ;
        $iniFileLocation = '/private/etc/php.ini' ;
        if (file_exists($iniFileLocation)) {
            $logging->log("Found php ini file", $this->getModuleName()) ;
            $content = file_get_contents($iniFileLocation) ;
            if ($content == "") {
                $logging->log("PHP INI file is empty. Creating from Default", $this->getModuleName()) ;
                $res = $this->recreatePHPIniFile() ;
                return $res ;
            } else {
                $logging->log("PHP INI file is usable", $this->getModuleName()) ;
                return true ;
            }
        } else {
            $logging->log("Unable to find php ini file, Creating from default", $this->getModuleName()) ;
            $res = $this->recreatePHPIniFile() ;
            return $res ;
        }
    }

    public function recreatePHPIniFile()
    {
        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params);
        $logging->log("Copying PHP Ini File from default", $this->getModuleName()) ;
        $iniFileLocation = '/private/etc/php.ini.default' ;
        if (file_exists($iniFileLocation)) {
            $logging->log("Found default php ini file", $this->getModuleName()) ;
            $copyFactory = new \Model\Copy() ;
            $params = $this->params ;
            $params["source"] = '/private/etc/php.ini.default' ;
            $params["target"] = '/private/etc/php.ini' ;
            $copy = $copyFactory->getModel($params) ;
            $res = $copy->performCopyPut() ;
            $logging->log("Default INI File Copied to Standard location", $this->getModuleName()) ;
            return $res ;
        } else {
            $logging->log("Unable to find default php ini file, cannot continue", $this->getModuleName()) ;
            return false ;
        }
    }

    public function addPHPIniExtension()
    {
        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params);
        $logging->log("Removing any old extension line from PHP Ini", $this->getModuleName()) ;
        $iniFileLocation = '/private/etc/php.ini' ;
        $params1 = $params2 = $this->params ;
        $params1["file"] = $iniFileLocation ;
        $params1["search"] = 'extension=/opt/local/lib/php55/extensions/no-debug-non-zts-20121212/ssh2.so' ;
        $fileFactory = new \Model\File();
        $file1 = $fileFactory->getModel($params1) ;
        $file1->performShouldNotHaveLine() ;
        $logging->log("Adding extension line from PHP Ini.", $this->getModuleName()) ;
        $params2["file"] = $iniFileLocation ;
        $params2["after-line"] = '[PHP]' ;
        $php_vers = $this->getPHPVersion() ;
        $params2["search"] = "extension=/opt/local/lib/php{$php_vers}/extensions/no-debug-non-zts-20121212/ssh2.so" ;
        $file2 = $fileFactory->getModel($params2) ;
        $file2->performShouldHaveLine() ;
    }

    public function removePHPIniExtension()
    {
        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params);
        $logging->log("Removing extension line from PHP Ini", $this->getModuleName()) ;
        $iniFileLocation = '/private/etc/php.ini' ;
        $params1 = $params2 = $this->params ;
        $params1["file"] = $iniFileLocation ;
        $php_vers = $this->getPHPVersion() ;
        $params1["search"] = "extension=/opt/local/lib/php{$php_vers}/extensions/no-debug-non-zts-20121212/ssh2.so" ;
        $fileFactory = new \Model\File();
        $file1 = $fileFactory->getModel($params1) ;
        $file1->performShouldNotHaveLine() ;
    }

    public function ensureMacPorts()
    {
        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params);
        $logging->log("Ensuring Mac Ports Dependency", $this->getModuleName()) ;
        $mcpFactory = new \Model\MacPorts() ;
        $mcp = $mcpFactory->getModel($this->params) ;
        $stat = $mcp->askStatus() ;
        if ($stat == true) {
            $res[] = true ;
        } else {
            $res[] = $mcp->ensureInstalled() ;
        }
        return in_array(false, $res)==false ;
    }

    public function askStatus()
    {
        $modsTextCmd = SUDOPREFIX.'php -m';
        $modsText = $this->executeAndLoad($modsTextCmd) ;
        $modsToCheck = array("ssh2") ;
        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params);
        $passing = true ;
        foreach ($modsToCheck as $modToCheck) {
            if (!strstr($modsText, $modToCheck)) {
                $logging->log("PHP Module {$modToCheck} does not exist.", $this->getModuleName()) ;
                $passing = false ;
            }
        }
        return $passing ;
    }
}
