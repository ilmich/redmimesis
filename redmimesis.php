<?php

	require_once "mimesis/Mimesis.php";

	class RedMimesis {
		
		private $_db;
		
		public function __construct($dataDir, $tableName, $tsName) {
			$this->_db = new Mimesis($dataDir,$tableName,$tsName);	
		}
		
		public function lock($polling=1) {
			return $this->_db->lock($polling);
		}
		
		public function release() {
			return $this->_db->release();			
		}
		
		public function dump_db() {
			echo "<pre>";
			var_dump($this->_db->query());
			echo "</pre>";
		}
		
		public function exists($key) {
			return !($this->_db->getRow($key,false) === false);
		}
		
		public function query() {
			return @$this->_db->query();
		}
		
		public function entries() {
			return @$this->_db->entries();
		}
		
		public function refresh() {
			return @$this->_db->refresh();
		}
		
		//REDIS style api
		public function set($key, $value) {			
			$data = array($value);
			@$this->_db->insertRow(array($key => $data),false);				
		}	
		
		public function get($key) {
			$data = @$this->_db->getRow($key,false);
			if (!$data === false) {
				$arr =  array_shift($data);				
				return $arr[0];				
			}
			return null;	
		}	
		
		public function searchKeys($key) {
			
			$rows = $this->_db->getRow($key,true);
			if (empty($rows)) {
				return false;
			}		
			
			return array_keys($rows);
		}
		
		public function del($keys) {
			
			$count = 0;
			
			if (!is_array($keys)) {
				$keys = array($keys);
			}	
			
			foreach ($keys as $key) {
				if ($this->exists($key)) {
					if ($this->_db->deleteRow($key,false,false)) {
						$count++;
					}
				}	
			}
			
			return $count;
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
			
			if (!is_array($keys)) {
				$keys = array($keys);
			}
			
			foreach($keys as $key) {
				$resultset[$key] = $this->get($key);
			}
			
			return $resultset;
		}
		
		public function setKeys($keyValues) {
			
			$count = 0;
			
			if (!is_array($keyValues)) {
				throw new Exception("The parameter keyValues must be an array");
			}		
			
			foreach ($keyValues as $key => $value) {
				$this->set($key,$value);
				$count++;
			}
			
			return $count;
		}
		

		
	}	
	
?>