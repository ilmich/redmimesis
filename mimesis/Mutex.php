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
 * Mutex source file
 *
 * This file contains the code for the Mutex class
 * @author Grim Pirate <grimpirate_jrs@yahoo.com>
 * @link http://mimesis.110mb.com/
 * @version 1.04
 * @since 1.0n
 * @package Mimesis
 */

error_reporting(E_ALL);

/**
 * Defines the directory where this script resides
 *
 * @access private
 */

define('MUTEXCLASS_DIR', substr(realpath(__FILE__), 0, -1 * strlen(basename(__FILE__))));

/**
 * Defines the timeout seconds for a lock
 *
 * @access private
 */
define('MUTE_OUT', 60);		// 60 is assigned because typically PHP scripts have an maximum execution time of 30 sec.

/**
 * The Mutex class creates a temporary file in order to ensure that exclusive locks are acquired whenever a file is accessed.
 * @package Mimesis
 */

class Mutex {
	/**
     * URI of the file to be locked
     *
     * @access private
     * @var string
     */
	var $filename;
	
	/**
	 * File pointer for the lock
	 *
	 * @access private
	 * @var resource
	 */
	var $fp;

	/**
	 * The constructor sets up all the parameters to create the lock. If $override is set to true the constructor will override the set max_execution_time constant and proceed to set its own time limit to the script (specified by $timeout). The lock's timeout will always be at least twice that of the max_execution_time.
	 *
	 * @param string $filename file to be locked
	 */
	function Mutex($filename){
		/**
		 * Parameter passing error handling
		 */

		if(!is_string($filename))
			trigger_error('[Mutex.php] &lt; ' . __LINE__ . ' &gt;', E_USER_ERROR);

		/**
		 * Code section
		 */

		// Append a '.lck' extension to filename for the locking mechanism
		$this->filename = $filename . '.lck';
	}

	/**
	 * A method that sets the lock on a file
	 *
	 * @param integer $polling specifies the sleep time (seconds) for the lock to wait in order to reacquire the lock if it fails.
	 * @return boolean TRUE on success FALSE on failure
	 */
	function acquireLock($polling = 1){
		/**
		 * Parameter passing error handling
		 */

		if(!is_int($polling) || $polling < 1) $polling = 1;

		/**
		 * Code section
		 */
		
		// Create the locked file, use 'x' to detect a preexisting lock
		while(false === $this->fp = @fopen($this->filename, "xb"))
			sleep($polling);

		// If unable to write the timeout fail lock
		if(!@fwrite($this->fp, (time() + MUTE_OUT)))
			return !trigger_error('[Mutex.php] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);

		// Successful lock
		return true;
	}

	/**
	 * A method that releases the lock on a file
	 *
	 * @return boolean TRUE on success FALSE on failure
	 */
	function releaseLock(){
		/**
		 * Code section
		 */

		// If file close is unsuccessful fail release
		if(!fclose($this->fp))
			return !trigger_error('[Mutex.php] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);
		// Delete the file with the extension '.lck'
		if(!@unlink($this->filename))
			return !trigger_error('[Mutex.php] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);
		// Successful release
		return true;
	}

	/**
	 * A method that returns the timeout value set to a lock
	 *
	 * @return integer seconds on success FALSE on failure
	 */
	function lockTime(){
		/**
		 * Code section
		 */

		// Retrieve the contents of the lock file
		if(false === $timeout = file_get_contents($this->filename))
			return !trigger_error('[Mutex.php] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);
		// Return the timeout value
		return intval($timeout);
	}
}
?>