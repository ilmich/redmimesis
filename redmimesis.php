<?php

	define("REDMIMESIS_VERSION","0.3.0");

	require_once "mimesis/Mimesis.php";	

	class RedMimesis extends Mimesis{		
		
		private $_sets = array();
		private $_deletes = array();
		private $_transaction = false;
		private $_lock = false;		
		
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

		public function lock($polling = 1){
			if (!$this->_lock) {
				$this->_lock = parent::lock($polling);	
			}						
			return $this->_lock;
			
		}
		
		public function release(){
			if($this->_lock) {	
				$this->_lock = !parent::release();
			}
			return !$this->_lock;
			
		}
		
		public function begin() {
			$this->_transaction = true;
			$this->lock(); //lock table
		}
		
		public function commit() {
			
			$this->_transaction = false;
			
			if (count($this->_sets) > 0) {			
				if (!$this->setKeys($this->_sets)){			
					$this->release();
					return false;
				}
			}
			
			if (count($this->_deletes) > 0) {								
				if (!$this->del($this->_deletes)){					
					$this->release();
					return false;
				}
			}
			
			$this->release();	
		}
		
		public function rollback() {
			
			$this->_transaction = false;
			$this->_sets = array();
			$this->_deletes = array();
			
			$this->release();
		}
		
		public function isInTransaction() {
			return $this->_transaction;
		}
		
		public function query() {
			
			//check table
			if (!file_exists($this->table(true))) {
				return false;
			}
			return parent::query();
		}
		
		public function refresh() {
			//check table
			if (file_exists($this->table(true))) {
				return parent::refresh(false);
			}
			
			return true;
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

			if ($this->isInTransaction()) {
				$this->_sets[$key] = $value;
				return true;
			}
			
			$data = $this->_transformInputValue($value);
			return $this->insertRow(array($key => $data),false);
			
		}		
		
		public function get($key) {			
			
			if (!is_array($key)) {				
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
			
			if ($this->isInTransaction()) {
				if (!is_array($keys)) {
					$keys = array($keys);
				}			
				
				$this->_deletes = array_merge($this->_deletes,$keys);				
				return count($keys);
			}
			
			//check table
			if (!file_exists($this->table(true))) {
				return false;
			}			
			
			if (!is_array($keys)) {
				$this->deleteRow($keys,false,false);
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
			
			if ($this->isInTransaction()) {
				$this->_sets = array_merge($this->_sets,$data);
				return true;
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
			
			if (!is_array($keys)) {
				return "/".$keys."/";	
			}
						
			return "/^(".implode("|",$keys).")$/";			
			
		}
		
	}	
	
?>