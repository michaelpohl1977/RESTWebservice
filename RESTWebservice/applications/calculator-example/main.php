<?php 

/**
 * main.php
 * 
 * "Calculator example" to show the usage of a webservice endpoint with
 * calculator functionality.
 * 
 * Endpoint: "../calculator-example/<method><parameter>"
 * 
 * @author Michael Pohl (www.simatex.de)
 */




/*******************************************************************************
 * Main
 * 
 * "Calculator example" main class. It's mandatory to extend 'ApplicationTemplate'
 */
class Main extends ApplicationTemplate
{
	
	/**********************************************************************
	 * __construct ()
	 * 
	 * Optional constructor to initialize the "caculator example"
	 */
	public function __construct ()
	{
		// The following optional methods (from 'ApplicationTemplate') can 
		// be used to set additional information for the automatically
		// generated webservice documentation:

		// Description of the application/function collection
		$this->setDescription("The 'Calculator example' application can be " .
			"used to process some calculations via webservice request. " .
			"Several webservice endpoints allow calculations for extern clients.");

		// Set the allowed content types for the application/function collection.
		// The number of parameters is variable.
		$this->setAllowedContentTypes("text/plain", "text/html", "application/xml");

		// Additional function information to set with the parameters:
		// 1) Function name
		// 2) Function description
		// 3) (optional) Parameter information
		// 4) (optional) Return information
		$this->addFunctionInformation(
			"get_multiply",
			"Multiplies any count of given numbers.",
			"Any number of integers as unnamed parameters (e.g. '.../multiply/10/2/5')",
			"The calculated result extended by a copyright text.");
	}
	
	
	/**********************************************************************
	 * __initCall ()
	 * 
	 * Optional method (from 'ApplicationTemplate') for tests that must
	 * be carried out before the processing of the request.
	 * In this case every given unnamed parameter is checked because it has
	 * to be a numeric value.
	 * 
	 * @returns Bool false, if the webservice request should not be processed
	 */
	function __initCall ()
	{
		// Getting all submitted unnamed paramters
		$arTemp = $this->Request->getUnnamedParameters();

		foreach ($arTemp as &$value)
		{
			// If one of the parameters is no numeric value, the processing
			// of the request is stopped
			if (!is_numeric($value))
			{
				$this->Response->setStatusCode(412);
				$this->Response->setStatusMessage("Only numeric values are allowed");
				$this->Response->setTextContent("One or more parameters are not numeric");
				return false;
			}
		}
	}
	
	
	/**********************************************************************
	 * __exitCall ()
	 * 
	 * Optional method (from 'ApplicationTemplate') for task that have to
	 * be executed after a webservice request is processed.
	 * In this case each webservice response is extended with a copyright
	 * text as 'footer'.
	 */
	function __exitCall ()
	{
		$this->Response->setTextContent(
			$this->Response->getContent() . "\r\n(c) Webservice");
	}
	
	
	/**********************************************************************
	 * get ()
	 * 
	 * Endpoint:  .../calculator-example
	 * REST verb: GET
	 * Method:    none
	 * 
	 * Example of a nameless GET endpoint without any method name. This
	 * method is called, if a GET request is sent directly to the application's
	 * URL.
	 */
	function get ()
	{
		$this->Response->setTextContent("To calculate, please use endpoint 'multiply' or 'add'!");
	}
	
	
	/**********************************************************************
	 * get_Multiply ()
	 * 
	 * Endpoint:   .../calculator-example/multiply/<parameters>
	 * REST verb:  GET
	 * Method:     multiply
	 * Parameters: unnamed
	 * Responose:  Result as HTML text
	 * 
	 * Endpoint multiplies any count of integer numbers requested as unnamed
	 * parameters.
	 */
	function get_Multiply ()
	{
		$result = 1;
		
		// Getting all given unnamed parameters in this request
		$arTemp = $this->Request->getUnnamedParameters();

		// Multiplying all paramters
		foreach ($arTemp as &$value)
		{
			$result = $result * $value;
		}

		// Sending a response as HTML text
		$this->Response->setContentType("text/html");
		$this->Response->setTextContent("<h1>" . $result . "</h1>");
	}
	
	
	/**********************************************************************
	 * get_Add ()
	 * 
	 * Endpoint:   .../calculator-example/add/<parameters>
	 * REST verb:  GET
	 * Method:     add
	 * Parameters: unnamed
	 * Responose:  Result as Text
	 * 
	 * Endpoint adds up any count of integer numbers requested as unnamed
	 * parameters.
	 */
	function get_Add ()
	{
		$result = 0;

		// Getting all given unnamed parameters in this request
		$arTemp = $this->Request->getUnnamedParameters();
		
		// Adding up all parameters
		foreach ($arTemp as &$value)
		{
			$result = $result + $value;
		}

		// Sending a response as Text
		$this->Response->setTextContent($result);
	}
}
