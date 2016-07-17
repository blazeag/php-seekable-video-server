<?php

/*****************************************************************************
 * Simple PHP Seekable Video Server Class
 *****************************************************************************
 * Based on "PHP Resumable Download Server" by Thomas Thomassen
 * http://www.thomthom.net/blog/2007/09/php-resumable-download-server/
 *****************************************************************************
 * (c) 2016 Andrea Gardoni <andrea.gardonitwentyfour@gmail.com> minus 24
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 2.1 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program;
 * if not, see <http://www.gnu.org/licenses/lgpl-2.1.html>.
 *****************************************************************************/

class SeekableVideo
{
	private $file;
	public $mime_type;
	public $output_filename;
	
	
	
	// Range download management function
	// -------------------------------------------------
	private function range_download($file, $mime_type, $output_filename)
	{
		// Open file as binary read
		$fp = @fopen($file, 'rb');
	
		$size = filesize($file);		// File size
		$length = $size;			// Content length
		$start = 0;			// Start byte
		$end = $size - 1; 		// End byte
	
		header("Accept-Ranges: 0-{$length}");
	
		if (isset($_SERVER['HTTP_RANGE']))
		{
	
			$c_start = $start;
			$c_end   = $end;
	
			// Extract the range string
			list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
	
			// Make sure the client hasn't sent us a multibyte range
			if (strpos($range, ',') !== false)
			{
				header("HTTP/1.1 416 Requested Range Not Satisfiable", true, 416);
				header("Content-Range: bytes {$start}-{$end}/{$size}");
				
				echo 'HTTP/1.1 416 Requested Range Not Satisfiable';
	
				exit;
			}
	
			// If no start byte is specified
			if ($range[0] == '-')
			{
				// The n-number of the last bytes is requested
				$c_start = $size - substr($range, 1);
			}
	
			// If both start byte and end byte are specified
			else
			{
				$range  = explode('-', $range);
				$c_start = $range[0];
				$c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
			}
	
			// Security check, end byte must be within file size
			$c_end = ($c_end > $end) ? $end : $c_end;
	
			// Validate the requested range and return an error if it's not correct.
			if
			(
				$c_start > $c_end
				|| $c_start > ($size - 1)
				|| $c_end >= $size
			)
			{
				header("HTTP/1.1 416 Requested Range Not Satisfiable", true, 416);
				header("Content-Range: bytes {$start}-{$end}/{$size}");
				
				echo 'HTTP/1.1 416 Requested Range Not Satisfiable';
	
				exit;
			}
	
			$start  = $c_start;
			$end    = $c_end;
			$length = $end - $start + 1; // Calculate new content length
	
			fseek($fp, $start);
	
			// Output mime type
			header("Content-Type: {$mime_type}");
			header("HTTP/1.1 206 Partial Content");
		}
	
		// Notify the client the byte range we'll be outputting
		header("Content-Range: bytes {$start}-{$end}/{$size}");
		header("Content-Length: {$length}");
	
		// Start buffered download
		$buffer = 1024 * 8;
	
		while
		(
			! feof($fp)
			&& ($p = ftell($fp)) <= $end
		)
		{
			if (($p + $buffer) > $end)
			{
				// In case we're only outputtin a chunk, make sure we don't
				// read past the length
				$buffer = $end - $p + 1;
			}
	
			// Reset time limit for big files
			set_time_limit(0);
	
			// Output read block
			echo fread($fp, $buffer);
	
			// Free up memory. Otherwise large files will trigger PHP's memory limit.
			flush();
		}
	
		// Close file
		fclose($fp);
	}
	
	
	
	// Try to presume mime type from file extension
	// -------------------------------------------------
	private function presume_mime_type($ext)
	{
		switch ($ext)
		{
			case 'ogv': return "video/ogg"; break;
			case 'webm': return "video/webm"; break;
			case 'mp4': case 'm4v': return "video/mp4"; break;
			
			// No last resort, give up with error
			default:
				header("HTTP/1.1 500 Internal Server Error", true, 500);
				echo "HTTP/1.1 500 Internal Server Error";
				exit;
			break;
		}
	}
	
	
	
	// Check if file exists and it is not empty
	// Can only return true or exit with error
	// -------------------------------------------------
	private function check_file($file)
	{
		// File found
		if (is_file($file))
		{
			// Non-empty file
			if (filesize($file) > 0)
			{
				return true;
			}
			
			// Empty file
			else
			{
				header("HTTP/1.1 204 No Content", true, 204);
				echo "HTTP/1.1 204 No Content";
				exit;
			}
		}
		
		// File not found
		else
		{
			header("HTTP/1.1 404 Not Found", true, 404);
			echo "HTTP/1.1 404 Not Found";
			exit;
		}
	}
	
	
	
	// Check if range or full download is requested and begin
	// -------------------------------------------------
	public function begin_stream()
	{
		$mime_type = $this->mime_type;
		$output_filename = $this->output_filename;
		
		
		
		// If no mime type is specified, try to presume it
		if ($mime_type === null)
		{
			$mime_type = $this->presume_mime_type(substr($this->file, strrpos($this->file, '.')));
		}
		
		// If video file exists and it is not null, begin download
		if ($this->check_file($this->file))
		{
			// If no filename is specified, use original one
			if ($output_filename === null)
			{
				$output_filename = strrpos($this->file, '/');
			}
			
			// Download video from a specific point to end
			if (isset($_SERVER['HTTP_RANGE']))
			{
				$this->range_download($this->file, $mime_type, $output_filename);
			}
			
			// Download the whole video from start to end
			else
			{
				// Get file size
				$filesize = filesize($this->file);
				
				// Output mime type
				header("Content-Type: {$mime_type}");
				header("Content-Length: {$filesize}");
				header("Content-Disposition: filename=\"{$output_filename}\"");
			
				readfile($this->file);
			}			
		}
		
	}
	
	

	// Constructor
	// -------------------------------------------------
	function __construct($file, $mime_type = null, $output_filename = null)
	{
		$this->file = $file;
		$this->mime_type = $mime_type;
		$this->output_filename = $output_filename;
	}
	
}

?>
