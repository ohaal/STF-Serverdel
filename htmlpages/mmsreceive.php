<?php
require_once ('mms.php');
require_once ('config.php');
define('SEP', DIRECTORY_SEPARATOR);

class MMSReceiveHandler {
	
	private $config;
	
	public function __construct() {
		include ("config.php");
		$this->config = $config;
	}
	
	public function handleMms($phonenumber, $subject, $data) {
		// Check if the user sent in any message in the subject - strip keyword from MMS gateway
		$title = explode(' ', $subject, 2);
		$message = '';
		if (count($title) > 1) {
			$message = $title[1];
		}
		
		// Put data in a zip in a unique folder - Folder structure: mmsfolder/phonenumber/timestamp
		$timestamp = time();
		$savepath = $this->config['mms_folder'].SEP.$phonenumber.SEP.$timestamp.SEP;
		$this->create_dirs($savepath);
		$savepath = realpath($savepath);
		if ($savepath === false || !is_dir($savepath)) {
			error_log('Could not create folder structure for saving MMS data. Permission problem?', 0);
			return false;
		}
		$savepath = $savepath.SEP;
		$zipfilepath = $savepath.'data.zip';
		// Put zip data in file
		if (file_put_contents($zipfilepath, $data) === false) {
			error_log('Unable to save MMS data to file', 0);
			return false;
		}
	
		// Extract files in same folder as zipfile
		if (!$this->unzip($zipfilepath, $savepath, true, true)) {
			error_log('Unable to unzip file, path: '.$zipfilepath, 0);
			return false;
		}
		// Remove zip file
//		if (!unlink($zipfilepath)) {
//			error_log('Unable to delete file '.$zipfilepath, 0);
//		}
	
		// Get name of image and get name of files containing text message from smil.xml
		$xmlfilepath = $savepath.'smil.xml';
		if (!file_exists($xmlfilepath)) {
			error_log('Could not find XML with data, path: '.$xmlfilepath, 0);
			return false;
		}
		$xmlobj = simplexml_load_file($xmlfilepath);
		// Remove xml file
//		if (!unlink($xmlfilepath)) {
//			error_log('Unable to delete file '.$xmlfilepath, 0);
//		}
		
		// Grab each message blob and related media
		$i = 1;
		$dataobjects = $xmlobj->body->par;
		foreach ($dataobjects as $dataobj) {
			// Get image filename
			$imgfile = (string)$dataobj->img->attributes()->src;
			if (empty($imgfile)) {
				error_log('Could not get image file name from XML', 0);
				return false;
			}
			$imgfilepath = $savepath.$imgfile;
			if (!file_exists($imgfilepath)) {
				// For some reason, certain MMS file names are listed differently in XML than in filesystem, this is a workaround
				// Fallback solution: look for the file in the specific folder
				$files = glob($savepath.'*.jpg');
				$match = false;
				// See if any of the files match the current expected name
				foreach ($files as $filename) {
					$basename = basename($filename);
					// Match basename case-insensitive at end of line of expected name - best guess at which photo belongs to what message
					//                            .-------´
					if (preg_match('/'.$basename.'$/i', $imgfile) || count($files) == 1) {
						$imgfilepath = $filename;
						$match = true;
						break;
					}
				}
				if (!$match) {
					error_log('Could not match any files up against imgfile '.$imgfile, 0);
					return false;
				}
				// If it still doesn't exist, we're out of options
				if (!file_exists($imgfilepath)) {
					error_log('Could not find MMS image file, path: '.$imgfilepath, 0);
					return false;
				}
			}
			// If more than one picture in same MMS we need to add a suffix, so we dont overwrite any pictures
			$x = '';
			if ($i > 1) {
				$x = '-'.$i;
			}
			// Move file to parent directory and rename to something more sensible (its timestamp)
			$newpath = $savepath.'..'.SEP.$timestamp.$x.'.jpg';
			$renamesuccess = rename($imgfilepath, $newpath);
			if (!$renamesuccess) {
				error_log('Could not rename MMS image file, path: '.$imgfilepath, 0);
				return false;
			}
			$newpath = realpath($newpath);
			
			// Get texts related to message
			$texts = $dataobj->text;
			$first = true;
			$msg = '';
			foreach ($texts as $text) {
				$txtfile = (string)$text->attributes()->src;
				if (!empty($txtfile)) {
					$txtfilepath = $savepath.$txtfile;
					if (file_exists($txtfilepath)) {
						// Get message from text file
						$msgpart = file_get_contents($txtfilepath);
						// ?: First part of message
						if ($first) {
							// -> Remove default keyword from start of sentence
							$e = explode(' ', $msgpart, 2);
							if (count($e) > 1 && strtolower($this->config['keywords_default']) == strtolower($e[0])) {
								$msgpart = $e[1];
							}
							$first = false;
						}
						
						// Append message part to message
						$msg .= $msgpart;					
						// Remove text file
//						if (!unlink($txtfilepath)) {
//							error_log('Unable to delete file '.$txtfilepath, 0);
//						}
					}
					else {
						error_log('Warning: Could not find text message file, path: '.$txtfilepath, 0);
					}
				}
			}
			if (empty($msg)) {
				$msg = $message;
			}
			
			// Trim double whitespace in message
			$msg = preg_replace('/(\s){2,}/', '${1}', $msg);
			// Trim whitespaces at end of message
			$msg = trim($msg);			
			
			$relpath = $this->find_relative_path(dirname(__FILE__), $newpath);
			
			// Add MMS information to database and push to MMSadmin
			$mms = new mmsReaction();
			if ($mms->addMms($phonenumber, $msg, $relpath) < 0) {
				error_log('Could not add MMS information to database, path: '.$savepath, 0);
				return false;
			}			
			
			$i++;
		}
		// Remove folder
//		if (!rmdir($savepath)) {
//			error_log('Unable to delete folder '.$savepath, 0);
//		}

		return true;
	}
	
	/**
	 * Unzip the source_file in the destination dir
	 * From: http://no.php.net/manual/en/ref.zip.php
	 *
	 * @param   string      The path to the ZIP-file.
	 * @param   string      The path where the zipfile should be unpacked, if false the directory of the zip-file is used
	 * @param   boolean     Indicates if the files will be unpacked in a directory with the name of the zip-file (true) or not (false) (only if the destination directory is set to false!)
	 * @param   boolean     Overwrite existing files (true) or not (false)
	 * 
	 * @return  boolean     Successful or not
	 */
	private function unzip($src_file, $dest_dir = false, $create_zip_name_dir = true, $overwrite = true) {
		if ($zip = zip_open( $src_file )) {
			if (is_resource($zip)) {
				$splitter = ($create_zip_name_dir === true) ? "." : SEP;
				if ($dest_dir === false) $dest_dir = substr( $src_file, 0, strrpos( $src_file, $splitter ) ) . "/";
				
				// Create the directories to the destination dir if they don't already exist
				$this->create_dirs( $dest_dir );
				
				// For every file in the zip-packet
				while ($zip_entry = zip_read( $zip )) {
					// Now we're going to create the directories in the destination directories
					

					// If the file is not in the root dir
					$pos_last_slash = strrpos( zip_entry_name( $zip_entry ), SEP );
					if ($pos_last_slash !== false) {
						// Create the directory where the zip-entry should be saved (with a "/" at the end)
						$this->create_dirs( $dest_dir . substr( zip_entry_name( $zip_entry ), 0, $pos_last_slash + 1 ) );
					}
					
					// Open the entry
					if (zip_entry_open( $zip, $zip_entry, "r" )) {
						
						// The name of the file to save on the disk
						$file_name = $dest_dir . zip_entry_name( $zip_entry );
						
						// Check if the files should be overwritten or not
						if ($overwrite === true || $overwrite === false && !is_file( $file_name )) {
							// Get the content of the zip entry
							$fstream = zip_entry_read( $zip_entry, zip_entry_filesize( $zip_entry ) );
							
							file_put_contents( $file_name, $fstream );
							// Set the rights
							chmod( $file_name, 0777 );
						}
						
						// Close the entry
						zip_entry_close( $zip_entry );
					}
				}
				// Close the zip-file
				zip_close( $zip );
			}
		}
		else {
			return false;
		}
		
		return true;
	}
	
	/**
	 * This function creates recursive directories if it doesn't already exist
	 *
	 * @param String  The path that should be created
	 * 
	 * @return  void
	 */
	private function create_dirs($path) {
		if (!is_dir( $path )) {
			$directory_path = '';
			$directories = explode( DIRECTORY_SEPARATOR, $path );
			array_pop( $directories );
			
			foreach ($directories as $directory) {
				$directory_path .= $directory . DIRECTORY_SEPARATOR;
				if (!is_dir( $directory_path )) {
					mkdir( $directory_path );
					chmod( $directory_path, 0777 );
				}
			}
		}
	}
	
	// Find the relative path from frompath to topath
	private function find_relative_path ( $frompath, $topath ) {
	    $startpath = explode( SEP, $frompath ); // Start path
	    $path = explode( SEP, $topath ); // Find relative path from start path
	    $relpath = '';
	
	    $i = 0;
	    while ( isset($startpath[$i]) && isset($path[$i]) ) {
	        if ( $startpath[$i] != $path[$i] ) break;
	        $i++;
	    }
	    $j = count( $startpath ) - 1;
	    while ( $i <= $j ) {
	        if ( !empty($startpath[$j]) ) $relpath .= '..'.SEP;
	        $j--;
	    }
	    while ( isset($path[$i]) ) {
	        if ( !empty($path[$i]) ) $relpath .= $path[$i].SEP;
	        $i++;
	    }
	    
	    // Strip last separator
	    return substr($relpath, 0, -1);
	}
}

?>
