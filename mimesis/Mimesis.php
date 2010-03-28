<?php
/*
Copyright (c) 2008-2009 Grim Pirate <grimpirate_jrs@yahoo.com>

All rights reserved.

Permission is granted to anyone to use this software for any purpose, including commercial applications, and redistribute it freely in source and binary forms, WITHOUT ALTERATIONS/MODIFICATIONS subject to the following restrictions:

- The origin of this software must not be misrepresented; you must not claim that you wrote the original software. If you use this software in a product, an acknowledgment in the product documentation would be appreciated but is not required.
- The name of the Grim Pirate may not be used to endorse or promote products derived from this software without specific prior written permission.

The above copyright notice and this permission notice shall be included in all copies/redistributions of the Software whether in source or binary form.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT OWNER/HOLDER "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NONINFRINGEMENT ARE DISCLAIMED. IN NO EVENT SHALL THE AUTHOR, COPYRIGHT OWNER/HOLDER, OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

/**
 * Mimesis source file
 *
 * This file contains the code for the Mimesis class
 * @author Grim Pirate <grimpirate_jrs@yahoo.com>
 * @link http://mimesis.110mb.com/
 * @version 2.0n
 * @since 1.0n
 * @package Mimesis
 */

error_reporting(E_ALL);

/**
 * Defines the directory where this script resides
 *
 * @access private
 */

define('MIMESISCLASS_DIR', dirname(realpath(__FILE__)) . DIRECTORY_SEPARATOR);

/**
 * An AND mask used to manipulate binary data
 *
 * @access private
 */

define('M_PMASK', 0x7fffffff);

/**
 * Class required to split arrays into keys and values for storage into database
 *
 * @access private
 */

require_once(MIMESISCLASS_DIR . 'Polarizer.php');

/**
 * Class required to ensure file locking for atomicity of data
 *
 * @access private
 */

require_once(MIMESISCLASS_DIR . 'Mutex.php');

/**
 * Function required for file writing
 *
 * @access private
 */

require_once(MIMESISCLASS_DIR . "file_place_contents.php");

/**
 * Function required for file writing/reading
 *
 * @access private
 */

require_once(MIMESISCLASS_DIR . "file_cull_contents.php");

/**
 * The Mimesis class contains all the methods necessary for manipulation of the flat file database.
 * @package Mimesis
 */

class Mimesis{
	/**
     * URI of a table
     *
     * @access private
	 * @var string
     */
	var $table = null;

	/**
     * URI of a table's structural file
     *
     * @access private
	 * @var string
     */
	var $struct = null;

	/**
     * The mutex mechanism variable
     *
     * @access private
     * @var Mutex
     */
	var $mutex = null;

	/**
	 * The constructor sets the table and structural file uris and their existence
	 *
	 * @param string $cwd a uri denoting the directory where the database tables are to be created/stored
	 * @param string $table the name of the table
	 * @param string $struct the name of the structural file
	 */
	function Mimesis($cwd, $table, $struct){
		$this->struct = $cwd . DIRECTORY_SEPARATOR . $struct . '.php';
		$this->table = $cwd . DIRECTORY_SEPARATOR . $table . '.php';
	}

	/**
	 * A method that returns the table of interest
	 *
	 * @param boolean $path return table path or just the table name (name returned by default)
	 * @return string
	 */
	function table($path = null){
		if(isset($path)) return $this->table;
		return substr(basename($this->table), 0, -4);
	}

	/**
	 * A method that returns the structure of interest
	 *
	 * @param boolean $path return structure path or just the structure name (name returned by default)
	 * @return string
	 */
	function struct($path = null){
		if(isset($path)) return $this->struct;
		return substr(basename($this->struct), 0, -4);
	}

	/**
	 * A method that locks a table
	 *
	 * @param integer $polling specifies the sleep time (seconds) for the lock to wait in order to reacquire the lock if it fails.
	 * @return boolean TRUE on success FALSE on failure
	 */
	function lock($polling = 1){
		// For atomicity we have to lock the table
		$mutex = new Mutex($this->table);

		if(!$mutex->acquireLock($polling))
			return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);
			
		$this->mutex = $mutex;

		return true;
	}

	/**
	 * A method that releases the lock on a table
	 *
	 * @return boolean TRUE on success FALSE on failure
	 */
	function release(){
		if(!$this->mutex->releaseLock())
			return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);

		$this->mutex = null;
		
		return true;
	}
	
	/**
	 * A method that retrieves rows from a table.
	 *
	 * @param string $search regular expression search pattern or case-sensitive row label
	 * @param boolean $preg whether to use parameter $search as a regular expression or a case-sensitive string
	 * @return array table row(s) on success or FALSE on failure
	 */
	function getRow($search, $preg = true){
		// Parameters
		if(!is_string($search) && !is_int($search))
			return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);
		if(!is_bool($preg)) $preg = false;
		
		// Code
		if(false === $tableStruct = file_get_contents($this->struct))
			return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);
		$tableStruct = substr($tableStruct, 8, -4);
		$tableStruct = explode(P_SSEP, $tableStruct);
		$tableStruct[0] = explode(P_FSEP, substr($tableStruct[0], 0, -2));
		
		$tableStruct[1] = desanitize($tableStruct[1]);
		$tableStruct[2] = desanitize($tableStruct[2]);

		$ret = array();
		if($preg){
			$tableStruct[0] = array_map('desanitize', $tableStruct[0]);
			$tableStruct[0] = array_map('unserialize', $tableStruct[0]);
			foreach($tableStruct[0] as $key => $rowLabel){
				$key *= 4;
				if(preg_match($search, $rowLabel)){
					if(false === $values = file_cull_contents($this->table, (ord($tableStruct[1][$key]) << 24) + (ord($tableStruct[1][$key + 1]) << 16) + (ord($tableStruct[1][$key + 2]) << 8) + ord($tableStruct[1][$key + 3]), (ord($tableStruct[2][$key]) << 24) + (ord($tableStruct[1][$key + 1]) << 16) + (ord($tableStruct[1][$key + 2]) << 8) + ord($tableStruct[1][$key + 3])))
						return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);
					$polarizer = new Polarizer($tableStruct[3], substr($values, 0, -2));
					if(false === $polarizer = $polarizer->getArr())
						return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);
					$ret[$rowLabel] = $polarizer;
				}
			}
		}else{
			$tableStruct[0] = array_flip($tableStruct[0]);
			$key = sanitize(serialize($search));
			if(array_key_exists($key, $tableStruct[0])){
				$key = $tableStruct[0][$key];
				$key *= 4;
				if(false === $values = file_cull_contents($this->table, (ord($tableStruct[1][$key]) << 24) + (ord($tableStruct[1][$key + 1]) << 16) + (ord($tableStruct[1][$key + 2]) << 8) + ord($tableStruct[1][$key + 3]), (ord($tableStruct[2][$key]) << 24) + (ord($tableStruct[2][$key + 1]) << 16) + (ord($tableStruct[2][$key + 2]) << 8) + ord($tableStruct[2][$key + 3])))
					return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);
				$polarizer = new Polarizer($tableStruct[3], substr($values, 0, -2));
				if(false === $polarizer = $polarizer->getArr())
					return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);
				$ret[$search] = $polarizer;
			}
		}
		if(empty($ret)) return false;
		return $ret;
	}
	
	/**
	 * A method retrieves all rows in a table
	 *
	 * @return array an array of tabular rows or FALSE on failure
	 */
	function query(){
		// Code
		if(false === $tableStruct = file_get_contents($this->struct))
			return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);
		$tableStruct = substr($tableStruct, 8, -4);
		if(false === $rows = file_get_contents($this->table))
			return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);

		$tableStruct = explode(P_SSEP, $tableStruct);
		$columns = $tableStruct[3];
		$tableStruct[0] = explode(P_FSEP, substr($tableStruct[0], 0, -2));
		
		$tableStruct[0] = array_map('desanitize', $tableStruct[0]);
		$tableStruct[0] = array_map('unserialize', $tableStruct[0]);
		$tableStruct[1] = desanitize($tableStruct[1]);
		$tableStruct[2] = desanitize($tableStruct[2]);

		$modStruct = array();
		foreach($tableStruct[0] as $key => $value){
			$key *= 4;
			$polarizer = new Polarizer($tableStruct[3], substr($rows, (ord($tableStruct[1][$key]) << 24) + (ord($tableStruct[1][$key + 1]) << 16) + (ord($tableStruct[1][$key + 2]) << 8) + ord($tableStruct[1][$key + 3]), (ord($tableStruct[2][$key]) << 24) + (ord($tableStruct[2][$key + 1]) << 16) + (ord($tableStruct[2][$key + 2]) << 8) + ord($tableStruct[2][$key + 3])));
			if(false === $polarizer = $polarizer->getArr())
				return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);
			$modStruct[$value] = $polarizer;
		}
		
		return $modStruct;
	}
	
	/**
	 * A method that returns the historical number of entries and the unique number of entries within the table
	 *
	 * @return array an array whose first value is the historical count and the second value is the unique count
	 */
	function entries(){
		// Code
		if(false === $tableStruct = file_cull_contents($this->struct, -28, 24, SEEK_END))
			return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);
		$tableStruct = explode(P_SSEP, $tableStruct);
		$tableStruct = end($tableStruct);
		$tableStruct = unpack('N*', desanitize($tableStruct));
		return array(
			'history' => $tableStruct[2] + $tableStruct[3], 
			'unique' => $tableStruct[2]
		);
	}

	/**
	 * A method that inserts rows into a table (if the table does not exist it attempts to create it)
	 *
	 * @param array $data an array of tabular rows to be inserted
	 * @param boolean $atomic whether or not file modifications should be atomic
	 * @return boolean TRUE on success FALSE on failure
	 */
	function insertRow($data, $atomic = true){
		// Parameters
		if(!is_array($data) || empty($data))
			return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);
		if(!is_bool($atomic)) $atomic = true;

		// Atomicity
		if($atomic)
			if(!is_object($this->mutex))
				return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);

		// Code
		$structOut = null;
		$tableOut = '';
		$offset = 0;
		$length = 0;

		if(false !== $structOut = @file_get_contents($this->struct)){
			$structOut = substr($structOut, 8, -4);
		}else{
			$length = reset($data);
			$offset = key($data);
	
			$length = new Polarizer($length);
			$tableOut .= $length->getValues() . P_SSEP;
			$length = $length->getKeys();
			
			$structOut = strlen($tableOut);
	
			$structOut = sanitize(serialize($offset)) . P_FSEP . P_SSEP . "\x00\x00\x00\x08" . P_SSEP . sanitize(pack('N', $structOut & M_PMASK)) . P_SSEP . $length . P_SSEP . sanitize(pack('N*', (8 + $structOut) & M_PMASK)) . "\x00\x00\x00\x01\x00\x00\x00\x00";
			unset($data[$offset]);
		}

		$structOut = explode(P_SSEP, $structOut);
		$structOut[0] = explode(P_FSEP, substr($structOut[0], 0, -2));
		$structOut[0] = array_flip($structOut[0]);
		
		$structOut[1] = desanitize($structOut[1]);
		$structOut[2] = desanitize($structOut[2]);
		
		$structOut[4] = unpack('N*', desanitize($structOut[4]));
		
		foreach($data as $rowLabel => $rowData){
			$rowLabel = sanitize(serialize($rowLabel));
			$polarizer = new Polarizer($rowData);
			$polarizer = $polarizer->getValues() . P_SSEP;
			$length = strlen($polarizer);
			
			if(array_key_exists($rowLabel, $structOut[0])){
				$structOut[4][3]++;
				$key = $structOut[0][$rowLabel];
			}else{
				$key = $structOut[4][2];
				$structOut[4][2]++;
				$structOut[0][$rowLabel] = $key;
			}
			$key *= 4;
			$temp = pack('N', $structOut[4][1] & M_PMASK);
			$structOut[1][$key] = $temp[0];
			$structOut[1][$key + 1] = $temp[1];
			$structOut[1][$key + 2] = $temp[2];
			$structOut[1][$key + 3] = $temp[3];
			$temp = pack('N', $length & M_PMASK);
			$structOut[2][$key] = $temp[0];
			$structOut[2][$key + 1] = $temp[1];
			$structOut[2][$key + 2] = $temp[2];
			$structOut[2][$key + 3] = $temp[3];
			$structOut[4][1] += $length;
			$tableOut .= $polarizer;
		}
		
		$structOut[0] = array_flip($structOut[0]);
		$structOut[0] = implode(P_FSEP, $structOut[0]) . P_FSEP;
		
		$structOut[1] = sanitize($structOut[1]);
		$structOut[2] = sanitize($structOut[2]);
		$structOut[4] = sanitize(pack('N*', $structOut[4][1] & M_PMASK, $structOut[4][2] & M_PMASK, $structOut[4][3] & M_PMASK));

		$structOut = '<?php /*' . implode(P_SSEP, $structOut) . '*/?>';

		if(false === file_place_contents($this->struct, $structOut))
			return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);

		$tableOut .= '*/?>';

		if(false === file_cull_contents($this->table, -4, null, SEEK_END, $tableOut)){
			$tableOut = '<?php /*' . $tableOut;
			if(false === file_place_contents($this->table, $tableOut))
				return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);
		}

		return true;
	}
	
	/**
	 * A method that deletes rows within a table based on a search
	 *
	 * @param string $row regular expression search pattern or case-sensitive row label
	 * @param boolean $preg whether to use parameter $row as a regular expression or a case-sensitive string
	 * @param boolean $atomic whether or not file modifications should be atomic
	 * @return boolean TRUE on success FALSE on failure
	 */
	function deleteRow($row, $preg = true, $atomic = true){
		// Parameters
		if(!is_string($row) && !is_int($row))
			return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);
		if(!is_bool($preg)) $preg = false;
		if(!is_bool($atomic)) $atomic = true;

		// Atomicity
		if($atomic)
			if(!is_object($this->mutex))
				return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);

		// Code
		if(false === $tableStruct = file_get_contents($this->struct))
			return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);
		$tableStruct = substr($tableStruct, 8, -4);
		$tableStruct = explode(P_SSEP, $tableStruct);
		$tableStruct[0] = explode(P_FSEP, substr($tableStruct[0], 0, -2));
		$tableStruct[1] = desanitize($tableStruct[1]);
		$tableStruct[2] = desanitize($tableStruct[2]);
		$tableStruct[4] = unpack('N*', desanitize($tableStruct[4]));
		
		if($preg){
			foreach($tableStruct[0] as $key => $value){
				if(preg_match($row, unserialize(desanitize($value)))){
					unset($tableStruct[0][$key]);
					$key *= 4;
					$tableStruct[1] = substr_replace($tableStruct[1], '', $key, 4);
					$tableStruct[2] = substr_replace($tableStruct[2], '', $key, 4);
					$tableStruct[4][3]++;
					$tableStruct[4][2]--;
				}
			}
		}else{
			$tableStruct[0] = array_flip($tableStruct[0]);
			$row = sanitize(serialize($row));
			if(array_key_exists($row, $tableStruct[0])){
				$key = $tableStruct[0][$row];
				unset($tableStruct[0][$row]);
				$key *= 4;
				$tableStruct[1] = substr_replace($tableStruct[1], '', $key, 4);
				$tableStruct[2] = substr_replace($tableStruct[2], '', $key, 4);
				$tableStruct[4][3]++;
				$tableStruct[4][2]--;
			}
			$tableStruct[0] = array_flip($tableStruct[0]);
		}

		if(empty($tableStruct[0])){
			if(!unlink($this->table) || !unlink($this->struct))
				return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);
		}else{
			$tableStruct[0] = implode(P_FSEP, $tableStruct[0]) . P_FSEP;
			$tableStruct[1] = sanitize($tableStruct[1]);
			$tableStruct[2] = sanitize($tableStruct[2]);
			$tableStruct[4] = sanitize(pack('N*', $tableStruct[4][1] & M_PMASK, $tableStruct[4][2] & M_PMASK, $tableStruct[4][3] & M_PMASK));
			
			$tableStruct = '<?php /*' . implode(P_SSEP, $tableStruct) . '*/?>';

			if(false === file_place_contents($this->struct, $tableStruct))
				return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);
		}
		return true;
	}
	
	/**
	 * A method that refreshes a table by removing its row history
	 *
	 * @param boolean $atomic whether or not file modifications should be atomic
	 * @return boolean TRUE on success FALSE on failure
	 */
	function refresh($atomic = true){
		// Parameters
		if(!is_bool($atomic)) $atomic = true;

		// Atomicity
		if($atomic)
			if(!is_object($this->mutex))
				return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);

		// Code
		if(false === $rows = file_get_contents($this->table))
			return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);
		if(false === $tableStruct = file_get_contents($this->struct))
			return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);
		$tableStruct = substr($tableStruct, 8, -4);
		$tableStruct = explode(P_SSEP, $tableStruct);
		$tableStruct[1] = desanitize($tableStruct[1]);
		$tableStruct[2] = desanitize($tableStruct[2]);
		$tableStruct[4] = desanitize($tableStruct[4]);
		
		$tableOut = '<?php /*';
		
		$offset = 8;
		$length = strlen($tableStruct[1]);
		for($i = 0; $i < $length; $i += 4){
			$tableOut .= substr($rows, (ord($tableStruct[1][$i]) << 24) + (ord($tableStruct[1][$i + 1]) << 16) + (ord($tableStruct[1][$i + 2]) << 8) + ord($tableStruct[1][$i + 3]), (ord($tableStruct[2][$i]) << 24) + (ord($tableStruct[2][$i + 1]) << 16) + (ord($tableStruct[2][$i + 2]) << 8) + ord($tableStruct[2][$i + 3]));
			$temp = pack('N', $offset & M_PMASK);
			$tableStruct[1][$i] = $temp[0];
			$tableStruct[1][$i + 1] = $temp[1];
			$tableStruct[1][$i + 2] = $temp[2];
			$tableStruct[1][$i + 3] = $temp[3];
			$offset += (ord($tableStruct[2][$i]) << 24) + (ord($tableStruct[2][$i + 1]) << 16) + (ord($tableStruct[2][$i + 2]) << 8) + ord($tableStruct[2][$i + 3]);
		}
		
		$tableOut .= '*/?>';
		
		$tableStruct[1] = sanitize($tableStruct[1]);
		$tableStruct[2] = sanitize($tableStruct[2]);
		
		$temp = pack('N', $offset & M_PMASK);
		$tableStruct[4][0] = $temp[0];
		$tableStruct[4][1] = $temp[1];
		$tableStruct[4][2] = $temp[2];
		$tableStruct[4][3] = $temp[3];
		$tableStruct[4][8] = "\x00";
		$tableStruct[4][9] = "\x00";
		$tableStruct[4][10] = "\x00";
		$tableStruct[4][11] = "\x00";
		$tableStruct[4] = sanitize($tableStruct[4]);
		
		$tableStruct = '<?php /*' . implode(P_SSEP, $tableStruct) . '*/?>';
		
		if(false === file_place_contents($this->struct, $tableStruct))
			return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);
		if(false === file_place_contents($this->table, $tableOut))
			return !trigger_error('[' . basename(__FILE__) . '] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);

		return true;
	}
}
?>