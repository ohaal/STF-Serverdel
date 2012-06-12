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
		// If we get an error, we mark this as true, so temporary MMS files are not deleted.
		// Error can therefore be reproduced easily by using the corresponding ZIP
		$error = false;
		// Check if the user sent in any message in the subject - strip keyword from MMS gateway
		$title = explode(' ', $subject, 2);
		$message = '';
		if (count($title) > 1) {
			$message = $title[1];
		}
		
		// Put data in a zip in a unique folder - Folder structure: mmsfolder/phonenumber/timestamp
		$timestamp = time();

		// Avoid folders with same name if sent at exact same time -> add a suffix
		$k = 1;
		do {
			$suffix = ($k > 1) ? '-'.$k : '';
			$k++;
			$savepath = $this->config['mms_folder'].SEP.$phonenumber.SEP.$timestamp.$suffix.SEP;
		} while (is_dir($savepath));
		
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
			// Try a different naming scheme
			$files = glob($savepath.'*.smil');
			$filescount = count($files);
			if ($filescount != 1) {
				error_log('Could not find XML with data. Found '.$filescount.' SMIL files in "'.$savepath.'". Expected 1.', 0);
				return false;
			}
			$xmlfilepath = $files[0];
		}
		$xmlobj = simplexml_load_file($xmlfilepath);
		// Remove xml file
//		if (!unlink($xmlfilepath)) {
//			error_log('Unable to delete file '.$xmlfilepath, 0);
//		}

		echo '<pre>';
		
		// Grab each message blob and related media
		$dataobjs = $xmlobj->body->par;
		$datablobs = array();
		// Restructure XML object to a cleaner map with only relevant data (for us)
		for ($i = 0, $j = 1; isset($dataobjs[$i]); $i++) {
			
			echo "##### i = $i #####\n";
			var_dump($dataobjs[$i]);
			
			// Deal with images if they are in the blob
			if (isset($dataobjs[$i]->img)) {
				$imgobjs = $dataobjs[$i]->img;
				
				// Generate a suffix if more than one picture in same MMS, so we dont overwrite any pictures
				$suffix = ($j > 1) ? '-'.$j : '';
				$j++;

				// Iterate through image objects
				$newpaths = $this->moveAndRenameImgObjsInPathToParent($imgobjs, $savepath, $timestamp.$suffix); 
				if (count($newpaths) > 0) {
					$datablobs[$i]['img'] = $newpaths;
				}
			}
			// Deal with texts if they are in the blob
			if (isset($dataobjs[$i]->text)) {
				$textobjs = $dataobjs[$i]->text;
				$textmsg = $this->findAndConcatTextMessages($textobjs, $savepath);
				if (!empty($textmsg)) {
					$datablobs[$i]['text'] = $textmsg;
				}
			}
		}

		$lonelyimg = array();
		$lonelytext = array();
		$mms = new mmsReaction();		
		// Loop through resulting map, add MMS information to database
		foreach ($datablobs as $blob) {
			if (!isset($blob['text'])) {
				$lonelyimg[] = $blob['img'];
				continue;
			}
			else if (!isset($blob['img'])) {
				$lonelytext[] = $blob['text'];
				continue;
			}
			foreach ($blob['img'] as $imgpath) {
				$relpath = $this->find_relative_path(dirname(__FILE__), $imgpath);
				echo "Adding MMS!\n";
				if ($mms->addMms($phonenumber, $blob['text'], $relpath) < 0) {
					error_log('Could not add MMS information to database, path: '.$savepath, 0);
					return false;
				}
			}
		}
		
		$lonelyimgcount = count($lonelyimg);
		if ($lonelyimgcount < count($lonelytext)) {
			$error = true;
			error_log('Warning: More texts found than images, see path: '.$savepath, 0);
		}
		// Loop through "lonely" images/texts and combine as best possible
		// "Lonely" are images that have no text (logically) associated with it
		for ($i = 0; $i < $lonelyimgcount; $i++) {
			$finaltext = $message; // Default to using MMS subject
			if (isset($lonelytext[$i]))	{
				$finaltext = $lonelytext[$i];
			}
			foreach ($lonelyimg[$i] as $imgpath) {
				$relpath = $this->find_relative_path(dirname(__FILE__), $imgpath);
				echo "Adding (lonely) MMS!\n";
				if ($mms->addMms($phonenumber, $finaltext, $relpath) < 0) {
					error_log('Could not add MMS information to database, path: '.$savepath, 0);
					return false;
				}
			}
		}
		
		echo "///// THE END!!!!... result /////\n";
		var_dump($datablobs);
		
		if (!$error) {
			// Delete files
			echo "!!!!!!!!!!!!!!!NO ERRORS!!!!!!!\n";
		}
		
		echo '</pre>';

		// Push to MMSadmin
		$mms->pushMms();
		return true;
	}
	
	/**
 	 * Goes through all files in textobjs and concatenates to a single string
	 * 
	 * @param	SimpleXML	$textobjs	SimpleXML text data objects
	 * @param	string		$path		path containing files
	 * 
	 * @return	string					concatenated text message
	 */
	private function findAndConcatTextMessages($textobjs, $path) {
		$textmsg = '';
		$first = true;
		foreach ($textobjs as $text) {
			$textfilename = $text->attributes()->src;
			$textfilepath = $path.$textfilename;
			
			if (file_exists($textfilepath)) {
				// Grab message from text file
				$textmsgpart = file_get_contents($textfilepath);
				echo 'Textmsgpart1: '.$textmsgpart."\n";
				// ?: Message grab failed
				if ($textmsgpart === false) {
					// -> Log a warning
					error_log('Warning: Could not read text message file, path: '.$textfilepath, 0);
				}
				else {
					// ?: First part of message
					if ($first) {
						// -> Remove default keyword from start of sentence
						$e = preg_split('/\s/', $textmsgpart, 2);
						if (strtolower($this->config['keywords_default']) == strtolower($e[0])) {
							$textmsgpart = (count($e) > 1) ? $e[1] : '';
						}
						$first = false;
					}
					echo 'Textmsgpart2: '.$textmsgpart."\n";
					// Append text message part to full message
					$textmsg .= $textmsgpart;
				}
			}
			else {
				error_log('Warning: Could not find text message file, path: '.$textfilepath, 0);
			}
		}
		if (!empty($textmsg)) {
			// Trim accidental double whitespace in message
			$textmsg = preg_replace('/(\s){2,}/', '${1}', $textmsg);
			// Trim whitespaces at end of message
			$textmsg = trim($textmsg);
		}
		return $textmsg;
	}
	
	/**
	 * Moves and renames all images in imgobjs to specified name, and falls back to searching in
	 * path if unable to find filenames in imgobjs in filesystem. 
	 * 
	 * @param	SimpleXMLElement	$imgobjs	SimpleXML img data objects
	 * @param	string				$path		path containing files
	 * @param	string				$name		base file name to rename to
	 * 
	 * @return	string[]						array of paths to image files moved/renamed
	 */
	private function moveAndRenameImgObjsInPathToParent($imgobjs, $path, $name) {
		$paths = array();
		$i = 1;
		foreach ($imgobjs as $img) {
			$imgfilename = $img->attributes()->src;
			$imgfilepath = $path.$imgfilename;
			
			// For some reason, certain MMS file names are listed differently in XML than in the filesystem,
			// but will have some similarities. Example:
			// In XML: 			cid:_external_images_media_2$02.jpg
			// In filesystem: 	02.jpg
			// This is a workaround/fallback solution: Look for the file in the specific folder
			if (!file_exists($imgfilepath)) {
				$jpgfiles = glob($path.'*.jpg'); // Gives us full path to JPG file
				$match = false;
				// Iterate over all JPG files in folder
				foreach ($jpgfiles as $jpgfilepath) {
					$basename = basename($jpgfilepath); // Use base name of JPG file
					// Match basename case-insensitive at end of line of expected name
					// This is a best guess method for determing which photo belongs to what message
					if (preg_match('/'.$basename.'$/i', $imgfilename) || count($jpgfiles) == 1) {
						$imgfilepath = $jpgfilepath;
						$match = true;
						break;
					}
				}
				if (!$match) {
					error_log('Could not match any files up against imgfile '.$imgfilename, 0);
					return $paths;
				}
				// If it still doesn't exist, we're out of options
				if (!file_exists($imgfilepath)) {
					error_log('Could not find MMS image file, path: '.$imgfilepath, 0);
					return $paths;
				}
			}
			
			// Now we have the full image file path, let's rename it
			$exists = false;
			do {
				// Generate a suffix if more than one picture in same MMS or file with same name exists
				// so we dont overwrite any pictures
				$suffix = ($i > 1 || $exists) ? '-'.$i : '';
				$i++;
				
				// Find an available path
				$newpath = $path.'..'.SEP.$name.$suffix.'.jpg';
				
				$exists = file_exists($newpath);
			} while($exists);
			// Move file to parent directory and rename to something more sensible (its timestamp+suffix)			
			$renamesuccess = rename($imgfilepath, $newpath);
			if (!$renamesuccess) {
				error_log('Could not rename MMS image file, path: '.$imgfilepath, 0);
				return $paths;
			}
			$newpath = realpath($newpath);
			$paths[] = $newpath;
		}
		return $paths;
	}

//			
//			// Get texts related to message
//			$first = true;
//			$msg = '';
//			foreach ($texts as $text) {
//				$txtfile = (string)$text->attributes()->src;
//				if (!empty($txtfile)) {
//					$txtfilepath = $savepath.$txtfile;
//					if (file_exists($txtfilepath)) {
//						// Get message from text file
//						$msgpart = file_get_contents($txtfilepath);
//						// ?: First part of message
//						if ($first) {
//							// -> Remove default keyword from start of sentence
//							$e = explode(' ', $msgpart, 2);
//							if (count($e) > 1 && strtolower($this->config['keywords_default']) == strtolower($e[0])) {
//								$msgpart = $e[1];
//							}
//							$first = false;
//						}
//						
//						// Append message part to message
//						$msg .= $msgpart;					
//						// Remove text file
////						if (!unlink($txtfilepath)) {
////							error_log('Unable to delete file '.$txtfilepath, 0);
////						}
//					}
//					else {
//						error_log('Warning: Could not find text message file, path: '.$txtfilepath, 0);
//					}
//				}
//			}
//			if (empty($msg)) {
//				if (isset($prevmsg) && empty($newpath)) {
//					$msg = $prevmsg;
//					unset($prevmsg);
//				}
//				else {
//					$msg = $message;
//					// If no image found, we store message for next iteration
//					if (empty($newpath)) {
//						echo "Continue 2\n";
//						$prevmsg = $msg;
//						continue;
//					}
////					else if (empty($newpath)) {
////						error_log('Message with no picture, and no chance of binding to one');
////						return false;
////					}
//				}
//			}
//
//			
//			// Trim double whitespace in message
//			$msg = preg_replace('/(\s){2,}/', '${1}', $msg);
//			// Trim whitespaces at end of message
//			$msg = trim($msg);			
//			
//			$relpath = $this->find_relative_path(dirname(__FILE__), $newpath);
//			
//			// Add MMS information to database and push to MMSadmin
//			$mms = new mmsReaction();
//			if ($mms->addMms($phonenumber, $msg, $relpath) < 0) {
//				error_log('Could not add MMS information to database, path: '.$savepath, 0);
//				return false;
//			}			
//		}
		// Remove folder
//		if (!rmdir($savepath)) {
//			error_log('Unable to delete folder '.$savepath, 0);
//		}
	
	
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
