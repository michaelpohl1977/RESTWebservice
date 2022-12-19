<?php  

/**
 * applicationlauncher.php
 * 
 * Main class to handle a request followed by a response
 * 
 * @author Michael Pohl (www.simatex.de)
 */




require_once("general.php");


   
   
/*******************************************************************************
 * ApplicationLauncher
 * 
 * Main class to handle a single request from the client and sends a correct
 * response back to it.
 */
class ApplicationLauncher
{
	// Buffered request object
	private $_RequestObject;
	// Buffered response object
	private $_ResponseObject;
	// main.php file of the requested application
	private $_ApplicationMainFile;
	// Main class of the application to execute
	private $_MainApplication = null;
	// Method name which schould be called (consist of REST verb and via URL
	// given method name)
	private $_MethodToCall = "";
	// Base directory, if the webservice isn't located in the root directory
	private $_BaseDir = "";
	// Should the webservice be used with (true) or without (false) using
	// applications
	private $_UseApplications = true;
	// Information about the application being installed
	public $isInstalled = true;   
	// Information about the documentation URL is called instead of a method
	public $isDocumentationRequest = false;
	public $isJDocumentationRequest = false;
      
      
      
      
	/**********************************************************************
	 * __construct ()
	 * 
	 * Constructor which initializes the object
	 * 
	 * @param RestRequest  $RequestObject  Filled RestRequest object with
	 *                                     request data send by the client
	 * @param RestResponse $ResponseObject RestResponse object that can be
	 *                                     filled and sent to the client.
	 */
	public function __construct (&$RequestObject, &$ResponseObject)
	{
		global $GlobalAppDir;
		global $_CONFIG;

		$this->_RequestObject  = $RequestObject;
		$this->_ResponseObject = $ResponseObject;

		$this->_UseApplications = $_CONFIG['webservice.useapplications'] ||
			!isset($_CONFIG['webservice.useapplications']);
                  
		if ($this->_UseApplications)
		{
			// Setting the class file for the currently requested application
			// if applications should be used by the webservice
			$this->_ApplicationMainFile = FormatString("{0}/{1}/main.php", $GlobalAppDir,
				$RequestObject->getApplication()); 
		} 
		else
		{
			// Setting the class for the currently requested method if
			// applications should not be used by the webservice.
			$this->_ApplicationMainFile = FormatString("{0}/main.php", $GlobalAppDir); 
		}

		// Determin the base path, if the webservice isn't located in the
		// root directory
		$this->_BaseDir = dirname($_SERVER["SCRIPT_NAME"]);
		
		// Executes an check about the application being installed in this
		// webservice properly.
		$this->checkInstallation();
	}
	
	
	/**********************************************************************
	 * checkInstallation ()
	 * 
	 * Checks if the requested application is installed properly. The
	 * contitions are an existence of the application's main.php and a valid
	 * application subclass with the requested method.
	 */
	private function checkInstallation()
	{
		global $_CONFIG;

		// If there was no application name requested but the configuration
		// says 'webservice.useapplications => true', the request isn't
		// processed.
		if ($this->_RequestObject->getApplication() == '' && $this->_UseApplications)
		{
			$this->_ResponseObject->setStatusCode(400); // 'Not implemented'
			$this->_ResponseObject->setStatusMessage(FormatString("No Application selected",
				$this->_RequestObject->getApplication()));
			$this->_ResponseObject->setTextContent(
				$this->_ResponseObject->getStatusMessage());
			$this->isInstalled = false;
		}
		// If the requested main.php file exists, it is tried to load the
		// 'Main' class of the application into the launcher.
		else if (file_exists($this->_ApplicationMainFile))
		{
			require_once($this->_ApplicationMainFile);

			// Does a 'Main' class exist in the application?
			if (class_exists('Main'))
			{
				$this->_MainApplication = new Main();

				// The 'Main' class has to extend the 'ApplicationTemplate'.
				// To allow a nesting of extended 'ApplicationTemplate', it
				// is not checked, if the 'Main' class extends it directly but
				// if any subclass in any layer extends 'ApplicationTemplate'.
				if (is_subclass_of($this->_MainApplication, 'ApplicationTemplate'))
				{               
					// Set method name to extecute (pattern "get_Method"). If
					// no method name exist, only the REST verb (e.g. "get") is 
					// used.
					// To check, if a method name was given and no unnamed
					// parameter, a method with the given name is searched in
					// the 'Main' class. If this method exists, it is executed.
					// Otherwise the name is set as unnamed parameter.
					$this->_MethodToCall = strtolower($this->_RequestObject->getRestVerb()) . 
						($this->_RequestObject->getMethod() != "" ? 
						'_' . ucfirst(strtolower($this->_RequestObject->getMethod())) :
						'');
                  
					if (!method_exists($this->_MainApplication, $this->_MethodToCall))
					{
						$this->_MethodToCall = strtolower($this->_RequestObject->getRestVerb());
					}
					else
					{
						// If a method to use was found, the method name initially
						// set as unnamed parameter has to be removed.
						$this->_RequestObject->removeUnnamedParameter(0);
					}
				  
					if (method_exists($this->_MainApplication, $this->_MethodToCall))
					{
						// Setting the base path of the application (to allow an
						// access to the file system within the application).
						if ($this->_UseApplications)
						{
							$this->_MainApplication->BasePath = 
								dirname($_SERVER['SCRIPT_FILENAME']) . '/applications/' . 
								$this->_RequestObject->getApplication();
						}
						else
						{
							$this->_MainApplication->BasePath = 
								dirname($_SERVER['SCRIPT_FILENAME']) . '/applications';
						}

						$this->isInstalled = true;
					}
					else
					{
						// If a documentation URL was requested, this information
						// is set (a documentation URL doesn't have a matching
						// method in the 'Main' class). The documentation is some
						// kind of virtual method.
						if ($this->_RequestObject->getMethod() == 
						strtolower($_CONFIG['documentation.url']))
						{
							$this->isInstalled = true;
							$this->isDocumentationRequest = true;
						}
						else if ($this->_RequestObject->getMethod() == 
							strtolower($_CONFIG['documentation.jurl']))
						{
							$this->isInstalled = true;
							$this->isJDocumentationRequest = true;
						}
						else
						{
							$this->_ResponseObject->setStatusCode(501); // 'Not implemented'
							$this->_ResponseObject->setStatusMessage(FormatString(
								$this->_UseApplications ? 
								"Requested {0} function '{1}' does not exist in application '{2}'" :
								"Requested {0} function '{1}' does not exist in the webservice",
								$this->_RequestObject->getRestVerb(),	
							   ($this->_RequestObject->getMethod() != "" ?
								$this->_RequestObject->getMethod() :
								strtolower($this->_RequestObject->getRestVerb())),
								$this->_RequestObject->getApplication()));
								$this->_ResponseObject->setTextContent(
								$this->_ResponseObject->getStatusMessage());
							$this->isInstalled = false;
						}
					}
				}
				else
				{
					$this->_ResponseObject->setStatusCode(501); // 'Not implemented'
					$this->_ResponseObject->setStatusMessage(
						$this->_UseApplications ?
						"Invalid type of application's Main entry point" :
						"Invalid type of webservice's Main entry point");
					$this->_ResponseObject->setTextContent(
						$this->_ResponseObject->getStatusMessage());
					$this->isInstalled = false;
				}
			}
			else
			{
				$this->_ResponseObject->setStatusCode(501); // 'Not implemented'
				$this->_ResponseObject->setStatusMessage(
					$this->_UseApplications ?
					"Application has no Main entry point" :
					"Webservice has no Main entry point");
				$this->_ResponseObject->setTextContent(
					$this->_ResponseObject->getStatusMessage());
				$this->isInstalled = false;
			}
		}
		else
		{ 
			if ($this->_RequestObject->getApplication() == 
				strtolower($_CONFIG['documentation.url']))
			{
				$this->isInstalled = true;
				$this->isDocumentationRequest = true;
			}
			else if ($this->_RequestObject->getApplication() == 
				strtolower($_CONFIG['documentation.jurl']))
			{
				$this->isInstalled = true;
				$this->isJDocumentationRequest = true;
			}
			else
			{
				$this->_ResponseObject->setStatusCode(501); // 'Not implemented'
				$this->_ResponseObject->setStatusMessage(FormatString(
					$this->_UseApplications ?
					"Application '{0}' is not installed on this server" :
					"Webservice is not installed on this server",
					$this->_RequestObject->getApplication()));
				$this->_ResponseObject->setTextContent(
					$this->_ResponseObject->getStatusMessage());
				$this->isInstalled = false;
			}
		}
	}
	
	
	/**********************************************************************
	 * execute ()
	 * 
	 * Executes the application function requested by the clients call, if
	 * it is installed/exists.
	 */
	public function execute ()
	{   
		// The execution only starts, if the application is installed properly...
		if ($this->isInstalled)
		{
			// ...and the application supports the requested content type.
			if ($this->contentTypeIsAllowed($this->_MethodToCall))
			{
				$this->_MainApplication->execute($this->_MethodToCall,
					$this->_RequestObject, $this->_ResponseObject);
			}
			else
			{
				$this->_ResponseObject->setStatusCode(406);
				$this->_ResponseObject->setStatusMessage(FormatString(
					$this->_UseApplications ?
					"Content-Type '{0}' not allowed by application" :
					"Content-Type '{0}' not allowed by webservice",
					$this->_RequestObject->getContentType()));
				$this->_ResponseObject->setTextContent("Only the following ContentTypes are allowed:\r\n" . implode("\r\n", 
					$this->_MainApplication->AllowedContentTypes[strtolower($this->_MethodToCall)]));
			}
		}
	}
	
	
	/**********************************************************************
	 * contentTypeIsAllowed ()
	 * 
	 * Checks if the content type used in the current request is allowed
	 * in this application.
	 * 
	 * @param String $MethodName Name of the method, that's allowed content
	 *                           types should be checked.
	 * 
	 * @returns Bool True, if the application allows the requested content type.
	 */
	private function contentTypeIsAllowed ($MethodName)
	{
		if ($this->isInstalled)
		{
			// Because of the fact, that keys can exist in different spelling,
			// they are converted to lower case to allow an easier access within
			// the application.
			$this->_MainApplication->AllowedContentTypes = 
				array_change_key_case($this->_MainApplication->AllowedContentTypes, CASE_LOWER);

			// If the AllowdContentType array is empty, no restriction was set.
			if (!isset($this->_MainApplication->AllowedContentTypes[strtolower($MethodName)]) ||
				count($this->_MainApplication->AllowedContentTypes[strtolower($MethodName)]) == 0)
			{
				return true;
			}

			$RequestContentType = strtolower($this->_RequestObject->getContentType());

			foreach ($this->_MainApplication->AllowedContentTypes[strtolower($MethodName)] as &$value)
			{
				if ($RequestContentType == strtolower($value))
				{
					return true;
				}
			}

			// Because the created $value variable is not reset automatically,
			// this has to be done in this loop manually.
			unset($value);
		}

		return false;
	}
	
	
	/**********************************************************************
	 * documetation()
	 * 
	 * Collects the documentation information of the application and fills
	 * the response object with the neccessary documentation data.
	 */
	public function documentation ()
	{
		require_once 'documentation.php';

		// If applications are used by the webservice, the documentation can be
		// called either from the application directory (documentation for this
		// application) or from the root directory (docuementation for all
		// installed applications).
		if ($this->isInstalled && 
			($this->isDocumentationRequest ^ $this->isJDocumentationRequest == true))
		{
			$this->_ResponseObject->setStatusCode(200);
			$this->_ResponseObject->setContentType(
				$this->isJDocumentationRequest ?
				"application/json" :
				"text/html");

			// 1. Call from base/root directory
			if ($this->_MainApplication == NULL)
			{
				$this->_ResponseObject->setTextContent(
					$this->isJDocumentationRequest ?
					Documentation::getDocumentationAsJson() :
					Documentation::getDocumentationAsHtml());
			}
			// 2. Call from application directory
			else
			{
				$this->_ResponseObject->setTextContent(
					$this->isJDocumentationRequest ?
					Documentation::getDocumentationAsJson($this->_RequestObject->getApplication()) :
					Documentation::getDocumentationAsHtml($this->_RequestObject->getApplication()));
			}
		}
		
		// A JSON-documentation was requested.
		if ($this->isInstalled && $this->isJDocumentationRequest)
		{
			$this->_ResponseObject->setStatusCode(200);
			$this->_ResponseObject->setContentType("application/json");
			$this->_ResponseObject->setTextContent(Documentation::getDocumentationAsJson());
		}
	}
}
