<?php 

/**
 * main.php
 * 
 * "Filemanager example" to show the usage of a webservice endpoint to allow
 * a file download for the client.
 * 
 * Endpoint: "../filemanager-example/download/<filename>.<ext>"
 * 
 * @author Michael Pohl (www.simatex.de)
 */




/*******************************************************************************
 * Main
 * 
 * "Filemanager example" mail class. It's mandatory to extend 'ApplicationTemplate'
 */
class Main extends ApplicationTemplate
{
	
	/**********************************************************************
	 * This example doesn't use a constructor or any functionality in
	 * __initCall() or __exitCall().
	 */
	
	
	/**********************************************************************
	 * get_Download ()
	 * 
	 * Endpoint:   .../filemanager-example/download/<filename>.<ext>
	 * REST verb:  GET
	 * Method:     download
	 * Parameters: unnamed
	 * Response:   File content to download at the client side
	 * 
	 * Sends a file located in the files subfolder as response to the client.
	 */
	function get_Download ()
	{
		// Getting the unnamed parameters (only the first one is important, as
		// it is the requested filename)
		$arUnnamed = $this->Request->getUnnamedParameters();
		
		if ( count($arUnnamed) > 0 && $arUnnamed[0] != "")
		{
			// Getting the complete location of the requested file
			$strFileToDownload = $this->BasePath . '/files/' . $arUnnamed[0];
			
			// Setting the content type depending on the file's extension
			switch(strtolower(substr($arUnnamed[0], strlen($arUnnamed[0])-3)))
			{
				case 'zip':
					$this->Response->setContentType('application/zip-archive');
					break;
				case 'apk':
					$this->Response->setContentType('application/vnd.android.package-archive');
					break;
				default:
					$this->Response->setContentType('text/plain');
					break;
			}
			
			// Setting the file als response content
			$this->Response->setFileContent($strFileToDownload);
		}
		else
		{
			$this->Response->setStatusCode(404);
			$this->Response->setStatusMessage('No filename to download given (try test.txt or test.zip)');
			$this->Response->setTextContent('No filename to download given (try test.txt or test.zip)');
		}
	}
}