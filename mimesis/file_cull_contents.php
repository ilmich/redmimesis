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
 * file_cull_contents source file
 *
 * This file contains the code for the file_cull_contents function
 *
 * @author Grim Pirate <grimpirate_jrs@yahoo.com>
 * @link http://mimesis.110mb.com/
 * @version 1.1
 * @since 1.0n
 * @package Mimesis
 */

error_reporting(E_ALL);

/**
 * A function that reads/writes content to a file
 *
 * @param string $filename the file to be read/written
 * @param integer $offset the offset from where to begin the read/write operation
 * @param integer $bytes the number of bytes to be read
 * @param integer $whence the location from where to compute offset for fseek
 * @param string $data the data to be written
 * @return mixed the number of bytes written, the bytes read, or FALSE on failure
 */
function file_cull_contents($filename, $offset = 0, $bytes = null, $whence = SEEK_SET, $data = null){
	if(!isset($bytes)){
		if(false === $handle = @fopen($filename, 'r+b')) return false;
		if(-1 === fseek($handle, $offset, $whence)) return false;
		if(false === $data = @fwrite($handle, $data)) return false;
		if(!fclose($handle)) return false;
		return $data;
	}else{
		if(false === $handle = @fopen($filename, 'rb')) return false;
		if(-1 === fseek($handle, $offset, $whence)) return false;
		if(false === $data = @fread($handle, $bytes)) return false;
		if(!fclose($handle)) return false;
		return $data;
	}
}
?>