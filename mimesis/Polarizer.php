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
 * Polarizer source file
 *
 * This file contains the code for the Polarizer class
 * @author Grim Pirate <grimpirate_jrs@yahoo.com>
 * @link http://mimesis.110mb.com/
 * @version 1.04
 * @since 1.0n
 * @package Mimesis
 */

error_reporting(E_ALL);

/**
 * Defines the delimeter character used to sanitize and separate polarized data
 *
 * @access private
 */
define('P_DLM', "\x1a");	// ASCII 26

/**
 * Defines the string that causes vulnerability issues
 *
 * @access private
 */
define('P_VUL', "\x2a\x2f");	// Refers to multi-line comment */

/**
 * Defines the substitution string for the delimeter character
 *
 * @access private
 */
define('P_SUB', "\x1a0");	// Substitute for ASCII char 26

/**
 * Defines the substitution string for sanitizing polarized data
 *
 * @access private
 */
define('P_HAZ', "\x1a2");	// Substitute for multi-line

/**
 * Defines the field separation string for polarized data
 *
 * @access private
 */
define('P_FSEP', "\x1a1");	// Field Separator

/**
 * Defines the section separation string for polarized data
 *
 * @access private
 */
define('P_SSEP', "\x1a3");	// Section Separator

/**
 * Needed for sanitizing data
 *
 * @access private
 */
require_once('sanitize.php');

/**
 * Needed for desanitizing data
 *
 * @access private
 */
require_once('desanitize.php');

/**
 * The Polarizer class takes an array and decomposes its key=>value pairs into a serialized key string and a serialized value string. It also recombines said strings into the original array.
 * @package Mimesis
 */
class Polarizer {
	/**
     * A private variable that holds the array
     *
     * @access private
     * @var array
     */
	var $arr;
	
	/**
     * A private variable that holds the serialized key(s)
     *
     * @access private
     * @var string
     */
	var $keys;
	
	/**
     * A private variable that holds the serialized value(s)
     *
     * @access private
     * @var string
     */
	var $values;

	/**
	 * The constructor determines the procedure to follow on how to parse the serialized input strings dependent on one or two parameters being passed to it. If one parameter is passed then it assumes a serialized array and will thus split the serialized array string into two strings of keys and values respectively. If two parameters are passed then the constructor assumes that it is being given serialized keys and serialized values and therefore recombines them into a serialized array.
	 *
	 * @param mixed $keys array or serialized key(s)
	 * @param string $values serialized value(s)
	 */
	function Polarizer($keys, $values = null){
		// Split the array into polarized strings
		if(is_array($keys)){
			$this->arr = $keys;
			$this->keys = '';
			$this->values = '';
			foreach($keys as $k => $v){
				$this->keys .= sanitize(serialize($k)) . P_FSEP;
				$this->values .= sanitize(serialize($v)) . P_FSEP;
			}
		// Join two polarized strings
		}else{
			$this->keys = $keys;
			$this->values = $values;
			$limit = '0';
			$output = ':{';
			while(false !== $temp = strpos($keys, P_FSEP)){
				$limit = bcadd($limit, '1');
				$output .= substr($keys, 0, $temp);
				$keys = substr($keys, $temp + 2);
				$temp = strpos($values, P_FSEP);
				$output .= substr($values, 0, $temp);
				$values = substr($values, $temp + 2);
			}
			$output .= '}';
			if(false === $this->arr = unserialize(desanitize('a:' . $limit . $output))){
				$this->arr = false;
				$this->keys = false;
				$this->values = false;
			}
		}
	}

	/**
	 * A method that returns serialized keys from a serialized array
	 *
	 * @return string serialized key(s) or FALSE on failure
	 */
	function getKeys(){
		return $this->keys;
	}

	/**
	 * A method that returns serialized values from a serialized array
	 *
	 * @return string serialized value(s) or FALSE on failure
	 */
	function getValues(){
		return $this->values;
	}

	/**
	 * A method that returns an array from serialized keys and serialized values
	 *
	 * @return array array or FALSE on failure
	 */
	function getArr(){
		return $this->arr;
	}
}
?>