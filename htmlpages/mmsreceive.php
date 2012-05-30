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
		if ($savepath === false) {
			error_log('Could not create folder structure for saving MMS data. Permission problem?', 0);
			return false;
		}
		$savepath = $savepath.SEP;
		$zipfilepath = $savepath.'data.zip';
		file_put_contents($zipfilepath, $data);
	
		// Extract files in same folder as zipfile
		if (!$this->unzip($zipfilepath, $savepath, true, true)) {
			error_log('Unable to unzip '.$zipfilepath, 0);
			return false;
		}
		// Remove zip file
		if (!unlink($zipfilepath)) {
			error_log('Unable to delete file '.$zipfilepath, 0);
		}
	
		// Get name of image and get name of files containing text message from smil.xml
		$xmlfilepath = $savepath.'smil.xml';
		if (!file_exists($xmlfilepath)) {
			error_log('Could not find XML with data '.$xmlfilepath, 0);
			return false;
		}
		$xmlobj = simplexml_load_file($xmlfilepath);
		// Remove xml file
		if (!unlink($xmlfilepath)) {
			error_log('Unable to delete file '.$xmlfilepath, 0);
		}
		
		// Get image filename
		$imgfile = (string)$xmlobj->body->par->img->attributes()->src;
		if (empty($imgfile)) {
			error_log('Could not get image file name from XML', 0);
			return false;
		}
		$imgfilepath = $savepath.$imgfile;
		if (!file_exists($imgfilepath)) {
			error_log('Could not find MMS image file '.$imgfilepath, 0);
			return false;
		}
		// Move file to parent directory and rename to something more sensible (its timestamp)
		$newpath = $savepath.'..'.SEP.$timestamp.'.jpg';
		$renamesuccess = rename($imgfilepath, $newpath);
		if ($renamesuccess) {
			$imgfilepath = realpath($newpath);
		}
		else {
			error_log('Could not rename MMS image file '.$imgfilepath, 0);
			return false;
		}
		
		// Check for text message file(s) in XML 
		$texts = $xmlobj->body->par->text;
		$first = true;
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
 						// -> Add space between subject and actual message if user sent text in subject
 						if (!empty($message)) {
							$msgpart = ' '.$msgpart;
 						}
						$first = false;
					}
					
					// Append message part to message
					$message .= $msgpart;					
					// Remove text file
					if (!unlink($txtfilepath)) {
						error_log('Unable to delete file '.$txtfilepath, 0);
					}
				}
				else {
					error_log('Could not find text message file '.$txtfilepath, 0);
				}
			}
		}
		// Remove folder
		if (!rmdir($savepath)) {
			error_log('Unable to delete folder '.$savepath, 0);
		}

		// Trim double whitespace in message
		$message = preg_replace('/(\s){2,}/', '${1}', $message);
		// Trim whitespaces at end of message
		$message = trim($message);
		
		// Add MMS information to database and push to MMSadmin
		$mms = new mmsReaction();
		if ($mms->addMms($phonenumber, $message, $imgfilepath) < 0) {
			error_log('Could not add MMS information to database', 0);
			return false;
		}
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
	 * @return  boolean     Succesful or not
	 */
	private function unzip($src_file, $dest_dir = false, $create_zip_name_dir = true, $overwrite = true) {
		if ($zip = zip_open( $src_file )) {
			if (is_resource($zip)) {
				$splitter = ($create_zip_name_dir === true) ? "." : "/";
				if ($dest_dir === false) $dest_dir = substr( $src_file, 0, strrpos( $src_file, $splitter ) ) . "/";
				
				// Create the directories to the destination dir if they don't already exist
				$this->create_dirs( $dest_dir );
				
				// For every file in the zip-packet
				while ($zip_entry = zip_read( $zip )) {
					// Now we're going to create the directories in the destination directories
					

					// If the file is not in the root dir
					$pos_last_slash = strrpos( zip_entry_name( $zip_entry ), "/" );
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
}

?>
