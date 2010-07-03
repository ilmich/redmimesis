<?php

	define("REDMIMESIS_VERSION","0.2.0");

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
					
			$fileName = basename($tableName);			
			if (is_null($tsName)) {
				$tsName = $fileName."_ts";
			}			

			if (!array_key_exists($tableName,self::$_tables)) {
				$db = new RedMimesis($dataDir,$fileName,$tsName);
				
				if (file_exists($db->table(true)) && !is_writable($db->table(true))) {
					throw new Exception("The data file $tableName must be writable");
				}		
						
				self::$_tables[$tableName] = $db;
			}			
			return self::$_tables[$tableName];
			
		}		
		
		public function dump_db() {
			
			echo "<pre>";
			var_dump($this->query());
			echo "</pre>";
			
		}		
		
		public function exists($key) {

			//check table
			if (!file_exists($this->table(true))) {
				return false;
			}
			return !($this->getRow($key,false) === false);
			
		}		
		
		//REDIS style api		
		public function set($key, $value) {
								
			$data = $this->_transformInputValue($value);			
			return $this->insertRow(array($key => $data),false);
			
		}		
		
		public function get($key) {
			
			if (!is_array($key) || count($key) == 1) {
				
				//check table
				if (!file_exists($this->table(true))) {
					return null;
				}
				
				$data = $this->getRow($key,false);
				if ($data === false) {
					return null;
				}
				return $this->_transformOutputValue($data[$key]);
			} 
			
			$preg = $this->_keysToPreg($key);		
			return $this->searchKeys($preg);	
							
		}		
	
		public function del($keys) {

			//check table
			if (!file_exists($this->table(true))) {
				return false;
			}
			
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
			if (is_null($value)) {
				return false;
			}
			
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
			$value += $incr;
			$this->set($key,$value);
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
		
		public function rrange($key,$offset,$lenght, $reverse=true) {
			
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
 

		public function searchKeys($preg) {
			
			//check table
			if (!file_exists($this->table(true))) {
				return null;
			}
			
			$data = $this->getRow($preg,true);
			
			if ($data === false) {
				return null;
			}
			
			$resultset = array();						
			
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