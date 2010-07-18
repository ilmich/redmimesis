<?php

	class RedMimesisDataMapper {
		
		private $_dbHandle;
		
		public function __construct($dbHandle) {
			if (is_null($dbHandle) || !is_object($dbHandle) || !$dbHandle instanceof RedMimesis ) {
				throw new Exception("A valid instance of RedMimesis is required");
			}
			$this->_dbHandle = $dbHandle;
		}
		
		public function save($obj,$id) {
			
			if (!is_string($id) || $id === "") {
				throw new Exception("Id must me a valid and not empty string");
			}
			
			if (!is_object($obj)) {
				throw new Exception("Only object can be saved with data mapper");
			}
									 
			$fields = get_object_vars($obj);
			$baseKeyName = strtolower(get_class($obj)).":".$id.":";
			foreach ($fields as $key => $value) {
				if (!$this->_dbHandle->set($baseKeyName.$key,$value)){
					return false;
				}
			}
			return true;
		
		}
		
		public function load(&$obj,$id) {
		
			if (!is_string($id) || $id === "") {
				throw new Exception("Id must me a valid and not empty string");
			}
			
			if (!is_object($obj)) {
				throw new Exception("Only object can be loaded with data mapper");
			}
											 
			$fields = get_object_vars($obj);			
			$baseKeyName = strtolower(get_class($obj)).":".$id.":";
			
			$arraySearch = array();
			//prepare list of keys to search
			foreach ($fields as $key => $value) {
				$arraySearch[$key] = $baseKeyName.$key;
			}
			$result = $this->_dbHandle->get($arraySearch); //get keys
			
			if (is_null($result)) {
				return false;
			}

			//populate object
			foreach ($arraySearch as $key => $value) {
				if (isset($result[$value])) {
					$obj->$key = $result[$value];
				}
			}
			return true;
				
		}
	}
?>