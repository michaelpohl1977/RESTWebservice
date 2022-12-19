<?php

/**
 * restrequest.php
 * 
 * Class representation of a singe webservice request
 * 
 * @author Michael Pohl (www.simatex.de)
 */




require_once("general.php");
   
   
   
 
/*******************************************************************************
 * RestRequest
 * 
 * Main class for a single REST request
 */
class RestRequest
{
	// The complete called URL (endpoint)
	private $_Url = "";               
	// The application to use
	private $_Application = "";     
	// Used REST verb (GET, POST etc.)   
	private $_RestVerb = "";           
	// Representation of the method to use
	private $_Method = "";            
	// Given "value" parameters (no key/name) sent by the request as array
	private $_UnnamedParameters = array(); 
	// Given "key-value" paramters sent by the request as array
	private $_NamedParameters = array();   
	// Optional PHP-FILE parameter for files sent in the request
	private $_FileParameters = array();
	// Sent content type
	private $_ContentType = "";         
	// Length of the sent content
	private $_ContentLength = 0;         
	// Sent content (e.g. POST, PUT)
	private $_Content = "";      
	// Sent header information
	private $_Header = array();
	
	
	
	
	/**********************************************************************
	 * __construct ()
	 * 
	 * Constuctor that initializes the object with all current data, values,
	 * variables etc.
	 */
	public function __construct ()
	{
		// If the system doesn't provide this $_SERVER variables, they are set 
		// to allow following function a usage of this variables (different 
		// webservice don't provide this variable or only with special call types)
		if (!isset($_SERVER["CONTENT_TYPE"])) 
			$_SERVER["CONTENT_TYPE"] = "";
		if (!isset($_SERVER["CONTENT_LENGTH"]))
			$_SERVER["CONTENT_LENGTH"] = 0;

		// Because of "PATH_INFO" can contain different values depending on
		// the webserver, this class only works with "REQUEST_URI".
		// "REQUEST_URI" always contains the complete path. Because of the
		// webservice can be located in a subdirectory of the path, the
		// subpath is extracted.
		$strPathToModify = substr(
			$_SERVER["REQUEST_URI"], strlen(dirname($_SERVER["SCRIPT_NAME"])));

		// Because the $strPathtoModify can and with a filename, it's removed
		// if this the case.
		$strRequestPath = parse_url($strPathToModify);
		$strRequestPath = $strRequestPath["path"];
		$iPosLastSlash  = strrpos($strRequestPath, "/");
		$iPosLastDot    = strrpos($strRequestPath, ".");

		if ($iPosLastDot > $iPosLastSlash)
			$_SERVER["PATH_INFO"] = substr($strRequestPath, 0, $iPosLastSlash);
		else
			$_SERVER["PATH_INFO"] = $strRequestPath;

		// Setting the complete called URL (endpoint)
		$this->_Url = $this->getUrlString();

		// Reading the REST verb ("POST", "PUT", "GET" etc.)
		$this->_RestVerb = strtoupper($_SERVER["REQUEST_METHOD"]);

		// Reading the content type. Depending on the client multipart content
		// types can be sent. If this is the case only the first part of the
		// multipart content is read.
		if (isset($_SERVER["CONTENT_TYPE"]))
		{
			$arrContent = preg_split('/;/', $_SERVER["CONTENT_TYPE"], -1);
			$this->_ContentType = $arrContent[0];
		}

		// The content is read directly from PHP-stdin
		$this->_Content = trim(file_get_contents('php://input'));
		// The content length is read from the $_SERVER variable
		$this->_ContentLength = isset($_SERVER["CONTENT_LENGTH"]) ?
			$_SERVER["CONTENT_LENGTH"] : 0;

		// Reading the sent header information
		$this->_Header = getallheaders();

		// Reading the application information and parameters
		$this->setApplicationInfoAndParameter();
	}

	
	/***********************************************************************
	 * getUrlString ()
     * 
     * Returns the complete requested URL string (endpoint)
     * 
     * @returns String Requested URL string (endpoint)
	 */
	private function getUrlString ()
	{
		return formatString("http://{0}{1}", $_SERVER["HTTP_HOST"],	$_SERVER["REQUEST_URI"]);
	}
	
	
	/**********************************************************************
	 * removeEmptyElements ()
	 * 
	 * Removes all empty elements from an array and executes a reindexing.
	 * 
	 * @param Array $Array Original array from which all elements have to be
	 *                     removed
	 * 
	 * @returns Array Reindexed array without empty elements
	 */
	private function removeEmptyElements ($Array)
	{
		$arRet = array();

		foreach ($Array as &$value)
		{
			if ($value != "")
			{
				$arRet[] = $value;
			}
		}

		return $arRet;
	}
	
	
	/**********************************************************************
	 * setApplicationInfoAndParameter ()
	 * 
	 * Reads all application and parameter informations from the request
	 * and sets them into the object
	 */
	private function setApplicationInfoAndParameter ()
	{
		global $_CONFIG;

		// Extract application information from the URL. Because the string
		// can start/end with a delimiter, there can exist empty array elements.
		// This empty elements are removed.
		$arTemp = $this->removeEmptyElements(explode("/",$_SERVER["PATH_INFO"]));

		// The request automatically cuts off existing filenames at the end of
		// the URL. So, the filename is manually added again.
		// (Filenames without "." returned by 'basename' are ignored, because 
		// they already exist). 
		$strFilename = basename($_SERVER['REQUEST_URI']);

		// Following the filename there can be '?' and '&' parameters. They have
		// to be removed.
		$ParamDelimiter = FALSE;
		$ParamDelimiterTemp1 = strpos($strFilename, '?');
		$ParamDelimiterTemp2 = strpos($strFilename, '&');

		if ($ParamDelimiterTemp1 !== FALSE)
		{
			$ParamDelimiter = $ParamDelimiterTemp1;
		}
		if ($ParamDelimiterTemp2 !== FALSE)
		{
			if ($ParamDelimiter === FALSE)
			{
				$ParamDelimiter = $ParamDelimiterTemp2;
			}
			else
			{
				$ParamDelimiter = $ParamDelimiterTemp1 < $ParamDelimiterTemp2 ?
					$ParamDelimiterTemp1 : $ParamDelimiterTemp2;
			}
		}

		if ($ParamDelimiter !== FALSE)
		{
			$strFilename = substr($strFilename, 0, $ParamDelimiter);
		}

		if ($strFilename != "" && strrpos($strFilename, ".") != false)
		{
			$arTemp[] = $strFilename;
		}

		$iCount = count($arTemp);

		// Default setting: usage of multiple applications within the webservice
		if (!isset($_CONFIG['webservice.useapplications']) ||
		   $_CONFIG['webservice.useapplications'] == true)
		{
			// The path information always has the same pattern:
			// /<application>/<method>/<optional parameters>/...

			// 1. Elemement: Application
			if ($iCount >= 1)
			{
				$this->_Application = strtolower($arTemp[0]);

				// 2. Element: Method name
				if ($iCount >= 2)
				{
					$this->_Method = strtolower($arTemp[1]);
					// The method name is set as first unnamed parameter as a
					// precaution, because it's possible to work withoud a
					// method name if configured (e.g. get() instead of 
					// get_Method()). If the webservice should work with methods,
					// this element is removed from the array.
					$this->_UnnamedParameters[] = $arTemp[1];

					// 3. Element and following: "Value" parameters
					for ($i = 2; $i < $iCount; $i++)
					{
						$this->_UnnamedParameters[] = $arTemp[$i];
					}
				}
			}
		}
		
		// Changed setting: No usage of multiple applications
		else
		{
			// The path information always has the same pattern:
			// /<method>/<optional parameters>/...

			// 1. Elemement: Method
			if ($iCount >= 1)
			{
				$this->_Application = '';
				$this->_Method = strtolower($arTemp[0]);
				// The method name is set as first unnamed parameter as a
				// precaution, because it's possible to work withoud a
				// method name if configured (e.g. get() instead of 
				// get_Method()). If the webservice should work with methods,
				// this element is removed from the array.
				$this->_UnnamedParameters[] = $arTemp[0];

				// 2. Element and following: "Value" parameters
				if ($iCount >= 2)
				{
					for ($i = 1; $i < $iCount; $i++)
					{
					   $this->_UnnamedParameters[] = $arTemp[$i];
					}
				}
			}
		}

		// 4. The named parameters are extracted from the $_REQUEST object.
		//    The first element always is the application's method path. This
		//    element is discarded.
		$this->_NamedParameters = $_REQUEST;
		$keys = array_keys($this->_NamedParameters);

		if (is_array($keys) && count($keys) > 0)
		{
			// If the webservice runs without using a method but named parameters,
			// the first element mustn't be deleted because the first named
			// parameter would be missing.
			if ((!isset($_CONFIG['webservice.useapplications']) ||
				$_CONFIG['webservice.useapplications'] == true) ||
				$this->_Method !== "")
			{
				unset($this->_NamedParameters[$keys[0]]);
			}
		}

		// 5. A possibly transmitted file content is read from the $_FILES
		//    object.
		$this->_FileParameters = $_FILES;

		// Because the keys can be used in different spelling, they are converted
		// to lower case. So they can be accessed within the application less
		// complicated.
		$this->_NamedParameters = array_change_key_case($this->_NamedParameters, CASE_LOWER);
		$this->_FileParameters = array_change_key_case($this->_FileParameters, CASE_LOWER); 
	}


	/**********************************************************************
	 * getUrl ()
	 * 
	 * Returns the original request URL.
	 *
	 * @returns String Original request URL
	 */
	public function getUrl ()
	{
		return $this->_Url;
	}


	/**********************************************************************
	 * getApplication ()
	 * 
	 * Returns the requested application name
	 *
	 * @returns String Name of the requested application
	 */
	public function getApplication ()
	{
		return $this->_Application;
	}


	/**********************************************************************
	 * getRestVerb ()
	 * 
	 * Returns the used REST verb. By default, the follwoing verbs are common:
	 * GET, POST, PUT, DELETE
	 *
	 * @returns String REST verb used by the request
	 */
	public function getRestVerb ()
	{
		return $this->_RestVerb;
	}


	/**********************************************************************
	 * getMethod ()
	 * 
	 * Returns the method of the applcation to execute. Every executable
	 * method has to be named according to the following pattern:
	 *    <REST verb>_<method name from URL>
	 * A POST request of the method "test" calls the following PHP method:
	 *    post_Test()
	 * 
	 * @returns String Method name within the application to execute
	 */
	public function getMethod ()
	{
		return $this->_Method;
	}


	/**********************************************************************
	 * getUnnamedParameters ()
	 * 
	 * Returns the list of unnamed parameters of the request in an array.
	 * Unnamed parameters are parameters that can be sent divided by "/" in
	 * the request URL.
	 * e.g. URL "https://.../Test/1/2/3 generates an array with values {1, 2, 3}
	 * 
	 * @returns Array List of unnamed parameters send with the request
	 */
	public function getUnnamedParameters ()
	{
		return $this->_UnnamedParameters;
	}


	/**********************************************************************
	 * hasUnnamedParameters ()
	 * 
	 * Checks if there exist unnamed parameter in the current request.
	 * 
	 * @returns Bool True, if at least one unnamed parameter exists
	 */
	public function hasUnnamedParameters ()
	{
		return (count($this->_UnnamedParameters) > 0);
	}


	/**********************************************************************
	 * getNamedParameters ()
	 * 
	 * Returns the named parameters of the current request as array. Named
	 * parameters are parameters that are sent in the request URL via "?".
	 * e.g. URL "https://.../Test?A=1&B=2" returns the array {'A' => 1, 'B' => 2}
	 * 
	 * @returns Array Named paramters used in the URL of the request
	 */
	public function getNamedParameters ()
	{
		return $this->_NamedParameters;
	}


	/**********************************************************************
	 * hasNamedParameters ()
	 * 
	 * Checks if there exist named parameters in the current request.
	 * 
	 * @returns Bool True, if at least one named parameter exists
	 */
	public function hasNamedParameters ()
	{
		return (count($this->_NamedParameters) > 0);
	}


	/**********************************************************************
	 * getFileParameters ()
	 * 
	 * Returns the file parameters of the request as array. This array can
	 * contain 0 - n file content datasets received from client (via POST).
	 * Each dataset of the associative array contains:
	 * 
	 * ['name']    Original filename at the client system.
	 * {'type']    MIME type of the file. The type is not checked and forwarded
	 *             exactly as it is received from the client.
	 * ['size']    Byte size of the received file
	 * [tmp_name'] Temporary filename (incl. path) with which the file was stored
	 *             at the server.
	 * ['error']   Errorcode of the file transfer.
	 * 
	 * Important! The upload form has to contain the mandatory attribute
	 * 'enctype="multipart/form-data"' otherwise the upload will not work because
	 * only the filename and no content is transfered.
	 * 
	 * @returns Array Datasets with information about every transfered file
	 */
	public function getFileParameters ()
	{
		return $this->_FileParameters;
	}
	
	
	/**********************************************************************
	 * hasFileParameters ()
	 * 
	 * Checks, if file parameters exist
	 * 
	 * @returns Bool true, if at least one file parameter exists
	 */
	public function hasFileParameters ()
	{
		return (count($this->_FileParameters) > 0);
	}


	/**********************************************************************
	 * getContentType ()
	 * 
	 * Returns the content type sent by the request.
	 * 
	 * @returns String Transmitted content type (e.g. "text/plain")
	 */
	public function getContentType ()
	{
		return $this->_ContentType;
	}


	/**********************************************************************
	 * getContentLength ()
	 * 
	 * Returns the length of the content sent by the request (usually used
	 * by POST/PUT).
	 * 
	 * @returns Integer Byte length of the sent content.
	 */
	public function getContentLength ()
	{
		return $this->_ContentLength;
	}


	/**********************************************************************
	 * getContent ()
	 * 
	 * Returns the content as it was sent by the client via request.
	 * 
	 * @returns String Content transmittet by the client
	 */
	public function getContent ()
	{
		return $this->_Content;
	}
	
	
	/**********************************************************************
	 * getJsonContent ()
	 * 
	 * Returns a transmitted JSON content as array structure.
	 * e.g. A JSON string like
	 *      '{"a":1,"b":2,"c":3,"d":4,"e":5}'
	 *      is returned as array
	 *      array ('a'=>1,'b'=>2,'c'=>3,'d'=>4,'e'=>5)
	 * 
	 * @returns Array JSON data converted from string to an array
	 */
	public function getJsonContent ()
	{
		return json_decode ($this->_Content);
	}
	
	
	/**********************************************************************
	 * getHeader ()
	 * 
	 * Returns the header of the request as an associative array like
	 * array ('User-Agent' => 'Mozilla/5.0 (Windows NT 10...').
	 * 
	 * @returns Array All header data sent with the request as associative 
	 *                array.
	 */
	public function getHeader ()
	{
		return $this->_Header;
	}

	
	/**********************************************************************
	 * getHeaderValue ()
	 * 
	 * Returns the value of a named header field.
	 * 
	 * @param String $Key Name of the header field, which value should be
	 *                    returned. The key search is case insensitive.
	 * 
	 * @returns String Value of the header field with the given name/key or
	 *                 '' if the key couldn't be found.
	 */
	public function getHeaderValue ( $Key )
	{
		// By default a case insensitive key search is not possible in an
		// associative array. That's why a workaround is used.
		$HeaderKeys = array_keys($this->_Header);

		foreach ($HeaderKeys as $HK)
		{
			if (strtolower($HK) == strtolower($Key))
			{
				return $this->_Header[$HK];
			}
		}

		return '';
	}
	
	
	/**********************************************************************
	 * removeUnnamedParameter ()
	 * 
	 * Removes the unnamed parameter at the given index. If the index is
	 * invalid or couldn't be found, this method does nothing.
	 * 
	 * @param Integer $Index Index of the unnamed parameter, which should
	 *                       be removed.
	 */
	public function removeUnnamedParameter ( $Index )
	{
		if (!is_numeric($Index))
		{
			return;
		}

		if ($Index < 0 || $Index >= count($this->_UnnamedParameters))
		{
			return;
		}

		unset($this->_UnnamedParameters[$Index]);
		
		// The index of all field remain unchanged after the removal. So is
		// has to be 'reindexed'.
		$this->_UnnamedParameters = array_values($this->_UnnamedParameters);
	}
}
