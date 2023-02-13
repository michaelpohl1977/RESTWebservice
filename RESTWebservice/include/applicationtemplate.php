<?php 

/**
 * applicationtemplate.php
 * 
 * Template class that has to be extended by every existing application
 * 
 * @author Michael Pohl (www.simatex.de)
 */




/*******************************************************************************
 * ApplicationTemplate
 * 
 * Template class that has to be extended by every existing application
 */
class ApplicationTemplate
{
	// System generated request object which can be used by the application
	public $Request = null;
	// System generated response object which can be filled an used by the
	// application
	public $Response = null;
	// Array with allowed content types for the application. If this array
	// is empty, all content types are allowed
	public $AllowedContentTypes = array();
	// Description of the application that is shown in the automatically 
	// generated documentation
	public $ApplicationDescription = "";
	// Description of available functions of the application that is shown
	// in the automatically generated documentation
	public $FunctionInformation = array();
	
	
	/**********************************************************************
	 * __initCall ()
	 * 
	 * Overridable method which is called prior to every execution of a 
	 * REST method. So any code can be executed before a REST method is 
	 * executed.
	 * 
	 * @returns Bool If this method returns false, the call of the requested
	 *               REST call is prevented (also the execution of __existCall()).
	 *               So the creator of the application can controll the
	 *               execution, check parameters etc., before the request is
	 *               handled within the applications main.php.
	 */
	public function __initCall ()
	{
		return true;
	}
	
	
	/**********************************************************************
	 * __exitCall ()
	 * 
	 * Overridable method which is called after every execution of a REST
	 * method. So any code can be executed after a REST method is executed
	 * in the applications main.php.
	 * 
	 * Important! This method is not called, if __initCall() returned false.
	 */
	public function __exitCall ()
	{

	}
	 
	 
	/**********************************************************************
	 * execute ()
	 * 
	 * Executes the requested function of the subclass, when a REST call
	 * is processed.
	 * 
	 * @param String         $Method         Method to call within the application
	 * @param RequestObject  $RequestObject  System generated request object 
	 *                                       which can be used by the application 
	 * @param ResponseObject $ResponseObject System generated response object 
	 *                                       which can be filled an used by the 
	 *                                       application
	 */
	public function execute ($Method, &$RequestObject, &$ResponseObject)
	{
		$this->Request = $RequestObject;
		$this->Response = $ResponseObject;

		// The requested method is only executed, if it really exists.
		// Execution of a non-existing method should be prevented by prior
		// testing
		if (method_exists($this, $Method))
		{ 
			if ($this->__initCall() !== false)
			{
				$this->{$Method}();
				$this->__exitCall();
			}
		}
		else
		{
			$this->Response->setStatusCode(501); // 'Not implemented'
			$this->Response->setStatusMessage(FormatString(
				"Requested method does not exist in application '{0}'",
				$this->Request->getApplication()));
			$this->Response->setContent(
				$this->Response->getStatusMessage());
		}
	}
	
	
	/**********************************************************************
	 * setDescription ()
	 * 
	 * Sets the application's description shown in the automatically 
	 * generated documentation
	 * 
	 * @param String $Description Desciption text of the application/function
	 *                            collection
	 */
	public function setDescription ($Description)
	{
		$this->ApplicationDescription = $Description;
	}
	
	
	/**********************************************************************
	 * setAllowedContentTypes ()
	 * 
	 * Sets the allowed content types for the current application/function
	 * collection. Already existing ones are overwritten, not added.
	 * 
	 * @param String [...] Any count of string parameters with allowed content
	 *                     types to set.
	 *                     e.g. ...("application/text", "application/json")
	 */
	public function setAllowedContentTypes (/*...*/)
	{
		$this->AllowedContentTypes = array();

		if (func_num_args() > 0)
		{
			$aParameters = func_get_args();

			foreach ($aParameters as $Parameter)
			{
				if (is_string($Parameter))
				{
					$this->AllowedContentTypes[] = $Parameter;
				}
			}
		}
	}
	
	
	/**********************************************************************
	 * addFunctionInformation ()
	 * 
	 * Adds additional information for the webservice funktions used in the
	 * application/function collection which are shown in the automatically
	 * generated documentation.
	 * 
	 * @param String $Functionname  Name of the function for which the information
	 *                              is set. The name has to be identical with
	 *                              the complete name in the main.php-code
	 *                              (e.g. "get_dosomething").
	 * @param String $Description   General description of the current function
	 * @param String $ParameterInfo Information about the paramters used in 
	 *                              this function (optional).
	 * @param String $ReturnInfo    Information about the return value used in
	 *                              this function (optional).
	 */
	public function addFunctionInformation ($FunctionName, $Description, 
			$ParameterInfo = "", $ReturnInfo = "")
	{
		if (is_string($FunctionName) && $FunctionName != "")
		{
			$strLowerName = strtolower($FunctionName);

			$this->FunctionInformation[$strLowerName] = array();
			$this->FunctionInformation[$strLowerName]['description'] = $Description;
			$this->FunctionInformation[$strLowerName]['param'] = $ParameterInfo;
			$this->FunctionInformation[$strLowerName]['return'] = $ReturnInfo;
		}
	}
}
  
