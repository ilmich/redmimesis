<?php
/*
Copyright (c) 2008-2010 Grim Pirate <grimpirate_jrs@yahoo.com>

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
 * @version 1.3
 * @since 1.0n
 * @package Mimesis
 */

error_reporting(E_ALL);

/**
 * The Mutex class creates a temporary directory in order to ensure that exclusive locks are acquired whenever a file is accessed.
 * @package Mimesis
 */

class Mutex {
	/**
     * URI of the directory to be locked
     *
     * @access private
     * @var string
     */
	var $dirname;
	
	/**
	 * The length of time the locking method should be allowed to execute in seconds
	 * @access private
	 * @var integer
	 */
	var $timeout;

	/**
	 * The constructor sets up all the parameters to create the lock (always infinite).
	 *
	 * @param string $dirname file to be locked
	 */
	function Mutex($dirname){
		/**
		 * Parameter passing error handling
		 */

		if(!is_string($dirname))
			trigger_error('[Mutex.php] &lt; ' . __LINE__ . ' &gt;', E_USER_ERROR);

		/**
		 * Code section
		 */

		// Append a '.lck' extension to filename for the locking mechanism
		$this->dirname = $dirname . '.lck';
		
		// Determine maximum number of times to reacquire lock
		$this->timeout = 1 + @intval(ini_get('max_execution_time'));
	}

	/**
	 * A method that sets the lock on a file
	 *
	 * @param integer $polling specifies the sleep time (seconds) for the lock to wait in order to reacquire the lock if it fails.
	 * @return boolean TRUE on success or FALSE on failure
	 */
	function acquireLock($polling = 1){
		/**
		 * Parameter passing error handling
		 */

		if(!is_int($polling) || $polling < 1) $polling = 1;

		/**
		 * Code section
		 */
		
		// Create the directory and hang in the case of a preexisting lock
		for($i = 0; $i < $this->timeout; $i++){
			if(@mkdir($this->dirname))
				return TRUE;
			sleep($polling);
		}

		// Unsuccessful lock
		return FALSE;
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

		// Delete the directory with the extension '.lck'
		if(!@rmdir($this->dirname))
			return !trigger_error('[Mutex.php] &lt; ' . __LINE__ . ' &gt;', E_USER_WARNING);
		// Successful release
		return true;
	}
}
?>