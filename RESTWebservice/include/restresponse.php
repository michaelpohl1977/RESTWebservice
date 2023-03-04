<?php

/**
 * restresponse.php
 * 
 * Class representation of a singe webservice response
 * 
 * @author Michael Pohl (www.simatex.de)
 */




require_once("general.php");




/*******************************************************************************
 * RestResponse
 * 
 * Main class for a single REST response
 */
class RestResponse
{
	// The default statuscode is 200 (OK) but can be changed if needed
	private $_StatusCode = 200; 
	// Optional status message which should be appended to the default message
	// to give back more or more detailled information
	private $_StatusMessage = "";
	// The used content type (default "text/plain")
	private $_ContentType = "text/plain";
	// Optional content length which overrides the automatically generated one
	private $_ContentLength = -1;
	// Content to send with the response
	private $_Content = "";
	// Header values to send with the response
	private $_Header = array();




	/**********************************************************************
	 * __construct ()
	 * 
	 * Constructor that initializes the object
	 */
	public function __construct ()
	{
		global $_CONFIG;
		$this->_StatusCode  = $_CONFIG["response.defaultstatuscode"]; 
		$this->_ContentType = $_CONFIG["response.defaultcontenttype"];
	}      
	
	
	/**********************************************************************
	 * translateStatusCode ()
	 * 
	 * Translates a statuscode to a human readable string.
	 * 
	 * @param String $Status Optional code to be translated. If no code is
	 *                       given, the status code in $this->StatusCode
	 *                       is translated.
	 * 
	 * @returns String Human readable representation of a status code.
	 */
	public function translateStatusCode ($Status)
	{
		if (!isset($Status))
			$Status = $this->_StatusCode;

		$arStatusCodes = Array(  
			0   => 'Unknown',
			100 => 'Continue',  
			101 => 'Switching Protocols',  
			200 => 'OK',  
			201 => 'Created',  
			202 => 'Accepted',  
			203 => 'Non-Authoritative Information',  
			204 => 'No Content',  
			205 => 'Reset Content',  
			206 => 'Partial Content',  
			300 => 'Multiple Choices',  
			301 => 'Moved Permanently',  
			302 => 'Found',  
			303 => 'See Other',  
			304 => 'Not Modified',  
			305 => 'Use Proxy',  
			306 => '(Unused)',  
			307 => 'Temporary Redirect',  
			400 => 'Bad Request',  
			401 => 'Unauthorized',  
			402 => 'Payment Required',  
			403 => 'Forbidden',  
			404 => 'Not Found',  
			405 => 'Method Not Allowed',  
			406 => 'Not Acceptable',  
			407 => 'Proxy Authentication Required',  
			408 => 'Request Timeout',  
			409 => 'Conflict',  
			410 => 'Gone',  
			411 => 'Length Required',  
			412 => 'Precondition Failed',  
			413 => 'Request Entity Too Large',  
			414 => 'Request-URI Too Long',  
			415 => 'Unsupported Media Type',  
			416 => 'Requested Range Not Satisfiable',  
			417 => 'Expectation Failed',  
			500 => 'Internal Server Error',  
			501 => 'Not Implemented',  
			502 => 'Bad Gateway',  
			503 => 'Service Unavailable',  
			504 => 'Gateway Timeout',  
			505 => 'HTTP Version Not Supported'
		);  

		return (isset($arStatusCodes[$Status])) ? $arStatusCodes[$Status] : $arStatusCodes[0]; 
	}
	
	
	/**********************************************************************
	 * getStatusCode ()
	 * 
	 * Returns the status code set in this object.
	 * 
	 * @returns Integer Status code which is seet in this object
	 */
	public function getStatusCode ()
	{
		return $this->_StatusCode;
	}
	
	
	/**********************************************************************
	 * setStatusCode ()
	 * 
	 * Sets the status code that should be send with the response.
	 * 
	 * @param Integer $Code Status code that should be set
	 */
	public function setStatusCode ($Code)
	{
		if (is_int($Code))
		{
			$this->_StatusCode = $Code;
		}
		else
		{
			$this->_StatusCode = 0;
		}
	}
	
	
	/**********************************************************************
	 * getStatusMessage ()
	 * 
	 * Returns the currently set status message. This status message consists
	 * of the status code translation and an optional set additional status 
	 * message, if set.
	 * 
	 * @returns String Translated status message and an optional set additional
	 *                 status message
	 */
	public function getStatusMessage ()
	{
		return FormatString("{0}{1}{2}",
			$this->translateStatusCode($this->_StatusCode),
			($this->_StatusMessage != "" ? " - " : ""),
			$this->_StatusMessage);
	}
	
	
	/**********************************************************************
	 * setStatusMessage ()
	 * 
	 * Sets the additional status message. This only affects the additional
	 * status message added to the translated default status message ("OK" etc.).
	 * 
	 * @param String $Text Additional status message to set.
	 */
	public function setStatusMessage ($Text)
	{
		$this->_StatusMessage = $Text;
	}
	
	
	/**********************************************************************
	 * getContentType ()
	 * 
	 * Returns the content type set in this response object.
	 * 
	 * @returns String Lower case content type set in this object 
	 *                 (e.g. "text/plain"))
	 */
	public function getContentType ()
	{
		return $this->_ContentType;
	}
	
	
	/**********************************************************************
	 * setContentType ()
	 * 
	 * Sets the content type to use in the response (the value is always
	 * converted to lower case).
	 * 
	 * @param String $Type Content type to send with the response. If no value
	 *                     is given, "text/plain" is set.
	 */
	public function setContentType ($Type)
	{
		if (is_string($Type))
		{
			if ($Type != "")
			{
				$this->_ContentType = strtolower($Type);
				return;
			}
		}

		$this->_ContentType = "text/plain";
	}
	
	
	/**********************************************************************
	 * getContentLength ()
	 * 
	 * Returns the content length to send with the response. If a value was
	 * set manually, this value is returned. Otherwise the value is calculated
	 * automatically.
	 * 
	 * @returns Integer Byte length of the content or manually overrid value
	 */
	public function getContentLength()
	{
		if ($this->_ContentLength > -1)
		{
			return $this->_ContentLength;
		}
		else
		{
			return strlen($this->_Content);
		}
	}
	
	
	/**********************************************************************
	 * setContentLength ()
	 * 
	 * Sets the content length to send with the response. If the content
	 * length is set manually, this value is returned. Otherwise the
	 * automatically calculated value is sent with the response.
	 * 
	 * @param Integer $Length Content length to set in the response 
	 */
	public function setContentLength($Length)
	{
		if (is_int($Length) && $Length > -1)
		{
			$this->_ContentLength = $Length;
		}
		else
		{
			$this->_ContentLength = -1;
		}
	}
	
	
	/**********************************************************************
	 * getContent ()
	 * 
	 * Returns the content stored in the response object.
	 * 
	 * @returns Mixed Stored content (e.g. String or byte data)
	 */
	public function getContent ()
	{
		return $this->_Content;
	}
	
	
	/**********************************************************************
	 * setTextContent()
	 * 
	 * Sets the given text content to send as response.
	 * 
	 * @param String $Content Text content so send as response.
	 */
	public function setTextContent ($Content)
	{
		$this->_Content = $Content;
	}
	
	
	/**********************************************************************
	 * setJsonContent ()
	 * 
	 * Sets the given array structure as JSON string content.
	 * e.g. The array
	 *      array ('a'=>1,'b'=>2,'c'=>3,'d'=>4,'e'=>5)
	 *      is set as content
	 *      "{"a":1,"b":2,"c":3,"d":4,"e":5}"
	 * 
	 * @param Array $JsonArray Array with data that should be encoded to a
	 *                         JSON string and send as response.
	 */
	public function setJsonContent ($JsonArray)
	{
		$this->_Content = json_encode ($JsonArray);
	}
	
	
	/**********************************************************************
	 * setFileContent ()
	 * 
	 * Sets the content of the given file (absolute path) as content. The
	 * file has to exist; the content length is calculated automatically.
	 * 
	 * @param String $Filename Absolute path (e.g. "/root/file/file.zip") of
	 *                         the file that's content is to set as response
	 *                         content.
	 */
	public function setFileContent ($Filename)
	{
		if (file_exists($Filename))
		{
			ob_start();
			readfile($Filename);
			$FileContent = ob_get_contents();
			ob_end_clean();

			$this->setContentLength(filesize($Filename));
			$this->setTextContent($FileContent);
		}
		else
		{
			$this->setStatusCode(404);
			$this->setStatusMessage("The requested file could not be found");
		}
	}
	
	
	/**********************************************************************
	 * setHeaderItem ()
	 * 
	 * Sets the key-value-pair als header element. If the given key already
	 * exists in the header, it is overriden (case insensitive).
	 * 
	 * Important! 'content-type' and 'content-length' are always overriden,
	 *            when the response is sent. So this two keys cannot be set by
	 *            the user and will be ignored.
	 * 
	 * @param String $Key Header key for the value to set
	 * @param String $Value Value to set for the given key
	 */
	public function setHeaderItem ( $Key, $Value )
	{
		$Key = trim($Key);

		// Searches the given key in the existing key list (case insensitive).
		$HeaderKeys = array_keys($this->_Header);

		foreach ($HeaderKeys as $Item)
		{
			if (strtolower($Item) == strtolower($Key))
			{
				$this->_Header[$Item] = $Value;
				return;
			}
		}

		$this->_Header[$Key] = $Value;
	}
	
	
	/**********************************************************************
	 * removeHeaderItem ()
	 * 
	 * Removes the header element with the given key from the response to
	 * the client. The key search is done case insensitive.
	 * 
	 * Important! 'content-type' and 'content-length' are overriden auto-
	 *            matically when the response is sent. This two keys cannot
	 *            be removed by the user.
	 * 
	 * @param String $Key Key of the header element to remove
	 */
	public function removeHeaderItem ( $Key )
	{
		$HeaderKeys = array_keys($this->_Header);

		foreach ($HeaderKeys as $Item)
		{
			if (strtolower($Item) == strtolower($Key))
			{
				unset($this->_Header[$Item]);
				return;
			}
		}
	}
	
	
	/**********************************************************************
	 * getHeader()
	 * 
	 * Returns the header data stored in this response object as 
	 * associative array like:
	 *    array ('User-Agent' => 'Mozilla/5.0 (Windows NT 10...')
	 * 
	 * @returns Array Header data as assiciative array
	 */
	public function getHeader ()
	{
		return $this->_Header;
	}
	
	
	/**********************************************************************
	 * getHeaderValue ()
	 * 
	 * Returns the value of a header element with the given key. The key
	 * search is done case insenstive.
	 * 
	 * @param String $Key Name/key of the header element that's value should
	 *                    be returned (case insensitive)
	 * 
	 * @returns String Value of the header element with the given key or
	 *                 '', if the key couldn't be found
	 */
	public function getHeaderValue ( $Key )
	{
		// Searches the given key in the existing key list (case insensitive).
		$HeaderKeys = array_keys($this->_Header);

		foreach ($HeaderKeys as $Item)
		{
			if (strtolower($Item) == strtolower($Key))
			{
				return $this->_Header[$Item];
			}
		}

		return '';
	}
	
	
	/**********************************************************************
	 * Send ()
	 * 
	 * Sends all data stored in this response object to the client.
	 */
	public function Send ()
	{
		$strStatusHeader = FormatString("HTTP/1.1 {0} {1}",
			$this->getStatusCode(),
			$this->getStatusMessage());

		// Setzen des Headers
		header($strStatusHeader);
		// 'content-type' and 'content-length' are always set here, even if
		// the user changed/removed this values previously.
		$this->setHeaderItem('Content-type', $this->getContentType());
		$this->setHeaderItem('Content-length', $this->getContentLength());

		$HeaderKeys = array_keys($this->_Header);

		foreach ($HeaderKeys as $Key)
		{
			header("$Key: ".$this->_Header[$Key]);
		}

		// Sending the webservice response to the client
		$fp = fopen("php://output", 'r+');
		fputs($fp, $this->getContent());
	}
}
