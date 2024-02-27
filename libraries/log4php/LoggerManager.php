<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/** Classes to avoid logging */

class LoggerManager {
	static function getlogger($name = 'ROOT') {
		$configinfo = LoggerPropertyConfigurator::getInstance()->getConfigInfo($name);
		return new Logger($name, $configinfo);
	}
}

/**
 * Core logging class.
 */
class Logger {
	private $name = false;
	private $appender = false;
	private $configinfo = false;
	public $starttime;

	/**
	 * Writing log file information could cost in-terms of performance.
	 * Enable logging based on the levels here explicitly
	 */
	private $enableLogLevel =  array(
		'ERROR' => false,
		'FATAL' => false,
		'INFO'  => false,
		'WARN'  => false,
		'DEBUG' => false,
	);

	function __construct($name, $configinfo = false) {
		$this->name = $name;
		$this->configinfo = $configinfo;
		// get time of start of current request from $_SERVER, assume "now" alternatively
		$this->starttime = $_SERVER["REQUEST_TIME_FLOAT"] ? $_SERVER["REQUEST_TIME_FLOAT"] : microtime(true);
		/** For migration log-level we need debug turned-on */
		if(strtoupper($name) == 'MIGRATION') {
			$this->enableLogLevel['DEBUG'] = true;
		}
	}

	function emit($level, $message) {
		if(!$this->appender) {
			$filename = 'logs/vtigercrm.log';
			if($this->configinfo && isset($this->configinfo['appender']['File'])) {
				$filename = $this->configinfo['appender']['File'];
			}
			$this->appender = new LoggerAppenderFile($filename, 0777);
		}
		$mypid = @getmypid();

		$this->appender->emit("$level [$mypid] $this->name - ", $message);
	}

	function info($message) {
		if($this->isLevelEnabled('INFO')) {
			$this->emit('INFO', $message);
		}
	}

	function debug($message) {
		if($this->isDebugEnabled()) {
			$this->emit('DEBUG', $message);
		}
	}

	function warn($message) {
		if($this->isLevelEnabled('WARN')) {
			$this->emit('WARN', $message);
		}
	}

	function fatal($message) {
		if($this->isLevelEnabled('FATAL')) {
			$this->emit('FATAL', $message);
		}
	}

	function error($message) {
		if($this->isLevelEnabled('ERROR')) {
			$this->emit('ERROR', $message);
		}
	}

	function isLevelEnabled($level) {
		if($this->enableLogLevel[$level] && $this->configinfo) {
			return (strtoupper($this->configinfo['level']) == $level);
		}
		return false;
	}

	function isDebugEnabled() {
		return $this->isLevelEnabled('DEBUG');
	}
	
	// Utility methods for realtime logging/debugging/timing to netcat listener on $stream
	// Start listening netcat using 'ncat -k -l 127.0.0.1 53280' (needs version of netcat that supports -k)
	// $message = the message to send (any type that can be serialized to string, customize below if needed)
	// $dontdie = true ignores unsuccessful connection attempt to server
	//		      false will halt a logging script when netcat exits, useful for killing long-running, headless scripts, so OBVIOUSLY NOT FOR PRODUCTION USE
	
	private $stream = "tcp://127.0.0.1:53280";	// any socket with netcat server listening

    // write time-stamp, time-delta, PID of process and message
	public function rt($message, $dontdie = false) {
		$streamsocket = $this->opensocket($dontdie);
		fwrite($streamsocket, sprintf("\e[32m[%s] ∆tᵣ %0.4fs, PID%s: \e[97m %s\n\n",date("H:i:s"),microtime(true)-$this->starttime,getmypid(),print_r($message,true)));
		fclose($streamsocket);
	}

	// send (shortened) backtrace to rt logger
	public function rtbt($dontdie = false) {
		global $root_directory;
		$bt = debug_backtrace();
		$t = $bt[0];
		$msg = "Backtrace from ".str_replace($root_directory,"",$bt[0]["file"]).", line {$bt[0]["line"]}:";
		for ($i=1;$i<count($bt);$i++) {
			$msg .= "\n -> {$i}: {$bt[$i]["class"]}::{$bt[$i]["function"]}\033[50G".str_replace($root_directory,"",$bt[$i]["file"]).", line {$bt[$i]["line"]}";
		}
		$this->rt($msg,$dontdie);
	}

	// send ANSI "clear screen, cursor home" to rt logger
    public function rtclr($dontdie = false) {
        $streamsocket = $this->opensocket($dontdie);
        fwrite($streamsocket,"\033[2J\033[H");
		fclose($streamsocket);
    }

	// returns socket to $stream, dies if connection fails (optionally, but BY DEFAULT)
	private function opensocket($dontdie = false) {
		$socket = stream_socket_client($this->stream, $errno, $errstr);
		if (!$socket && !$dontdie) {
			die("({$errno}: {$errstr}) log receiver not listening, script execution halted. Start netcat listener on {$this->stream}.");
		}
		return $socket;
	}
}

/**
 * Log message appender to file.
 */
class LoggerAppenderFile {

	private $filename;
	private $chmod;

	function __construct($filename, $chmod = 0222) {
		$this->filename = $filename;
		$this->chmod    = $chmod;
	}

	function emit($prefix, $message) {
		if($this->chmod != 0777 && file_exists($this->filename)) {
			if(is_readable($this->filename)) {
				@chmod($this->filename, $this->chmod);
			}
		}
		$fh = @fopen($this->filename, 'a');
		if($fh) {
			@fwrite($fh, date('m/d/Y H:i:s') . " $prefix $message\n");
			@fclose($fh);
		}
	}
}
