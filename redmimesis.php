<?php

	define("REDMIMESIS_VERSION","0,1,0");

	require_once "mimesis/Mimesis.php";

	class RedMimesis extends Mimesis{		
		
		private static $_tables = array();
		
		private function __construct($dataDir,$tableName, $tsName) {
			
			parent::__construct($dataDir,$tableName, $tsName);
			
		}		
		
		public static function connect($tableName,$tsName = null) {
			
			$dataDir = dirname($tableName);			
			if (!is_writable($dataDir)) {
				throw new Exception("The directory $dataDir not exists or isn't writable");
			}			
			if (file_exists($tableName) && !is_writable($tableName)) {
				throw new Exception("The data file $tableName must be writable");
			}			
			$fileName = basename($tableName);			
			if (is_null($tsName)) {
				$tsName = $fileName."_ts";
			}			
			if (!array_key_exists($tableName,self::$_tables)) {
				self::$_tables[$tableName] = new RedMimesis($dataDir,$fileName,$tsName);
			}			
			return self::$_tables[$tableName];
			
		}		
		
		public function dump_db() {
			
			echo "<pre>";
			var_dump($this->query());
			echo "</pre>";
			
		}		
		
		public function exists($key) {
						
			return !($this->getRow($key,false) === false);
			
		}		
		
		public function refresh() {
			
			return parent::refresh(false);
			
		}
		
		//REDIS style api		
		public function set($key, $value) {
								
			$data = $this->_transformInputValue($value);			
			return $this->insertRow(array($key => $data),false);
			
		}		
		
		public function get($key) {
									
			$data = $this->getRow($key,false);			
			if (!$data === false) {
				return $this->_transformOutputValue($data[$key]);						
			}
			return null;
				
		}		
	
		public function del($keys) {
						
			$count = 0;			
			
			if (!is_array($keys) || count($keys) == 1) {
				$this->deleteRow($key,false,false);
				return 1;
			}			
			
			$preg = $this->_keysToPreg($keys);			
			$this->deleteRow($preg,true,false);
			return count($keys);
			
		}
		
		public function type($key) {
			
			$value = $this->get($key);
			if (is_object($value)) {
				return get_class($value);
			}
			return gettype($value);
				
		}		
		
		public function incr($key) {
						
			return $this->incrby($key,1);
			
		}		
		
		public function decr($key) {
						
			return $this->incrby($key,-1);
			
		}		
		
		public function decrby($key,$decr) {
						
			return $this->incrby($key,-$decr);
			
		}
		
		public function incrby($key,$incr) {
			
			$value = $this->get($key);
			if (is_null($value) || !is_numeric($value)) {
				$value = 0;				
			}
			$this->set($key,$value+$incr);
			return $value;
			
		}
		
		public function lpush($key,$value) {
			
			$data = $this->get($key);			
			if (is_null($data) || !is_array($data)) {
				$data = array();
			}			
			array_unshift($data,$value);			
			$this->set($key,$data);
						
		}
		
		public function lpop($key) {
						
			$data = $this->get($key);			
			if (is_null($data)) {
				return null;
			}			
			$obj = array_shift($data);
			$this->set($key,$data);
			
		}		
		
		public function lrange($key,$offset,$lenght) {
			
			$data = $this->get($key); 
			if (is_null($data)) {
					return null;
			}
			return array_slice($data,$offset,$lenght);
						
		}
		
		public function rrange($key,$offset,$lenght,$reverse=true) {
			
			$data = $this->get($key);			
			if (is_null($data)) {
					return null;
			}						
			$slice = array_slice($data,-($offset+$lenght),$lenght);			
			if ($reverse) {
				$slice = array_reverse($slice);
			}			
			return $slice;
						
		}
		
		// Other useful functions
		function getKeys($keys) {
			
			$resultset = array();			
			if (!is_array($keys) || count($keys) == 1) {
				return $this->get($keys);
			}			
			$preg = $this->_keysToPreg($keys);		
			$data = $this->getRow($preg,true);			
			if ($data === false) {
				return null;
			}	
			foreach($data as $key => $value) {				
				$resultset[$key] = $this->_transformOutputValue($value);
			}
						
			return $resultset;
			
		}
 
		public function setKeys($keyValues) {
			
			$count = 0;
			$data = array();
			
			if (!is_array($keyValues)) {
				throw new Exception("The parameter keyValues must be an array");
			}			
			//transform input array
			foreach ($keyValues as $key => $value) {
				$data[$key] = $this->_transformInputValue($value);
			}			
			if (!$this->insertRow($data,false)) {
				return false;
			}			
			return count(array_keys($data));
			
		}
				
		public function lremove($key,$values) {
			
			$data = $this->get($key);		
			if (is_null($data)) {
				return false;
			}			
			if (!is_array($values)) {
				$values = array($values);
			}
			$data = array_diff($data,$values);
			$this->set($key,$data);
			return true;
			
		}		
		
		private function _transformInputValue($value) {
						
			return array($value);
			
		}		
		
		private function _transformOutputValue($value) {
			
			return array_shift($value);			
				
		}
		
		private function _keysToPreg($keys) {
			
			if (!is_array($keys) || count ($keys) == 1) {
				return "/".$keys."/";	
			}			
			return "/".implode("|",$keys)."/";
			
		}
		
	}	
	
?>