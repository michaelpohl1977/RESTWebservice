<?php  

/**
 * documentation.php
 * 
 * Functionallity neccessary for the display of an HTML documentation or the
 * response of a JSON documentation.
 * 
 * @author Michael Pohl (www.simatex.de)
 */




require_once("general.php");


   
   
/*******************************************************************************
 * Documentation
 * 
 * Documentation class with static functions to allow the automatic creation
 * of a webservice/application/method documentation.
 */
class Documentation
{
	/**********************************************************************
	 * getRelevantMainFiles ()
	 * 
	 * Returns all main.php files that are neccessary for the creatin of
	 * a documentation, that have to be analyzed.
	 * 
	 * @param String $Application Optional name of an application, if only the
	 *                            main.php of this single application should be
	 *                            searched and analyzed.
	 * 
	 * @returns Array Collection of elements containing the found main.php
	 *                files to use for the documentation.
	 */
	public static function getRelevantMainFiles ($Application = '')
	{
		global $_CONFIG;

		// The usage of applications by the webservice can be activated or
		// deactivated. Depending on this configuration the file search is done
		// recursively or not.
		$UseApplications = ($_CONFIG['webservice.useapplications'] == true);
		$MainFiles =  SearchFiles(
			'applications' . ($Application == '' ? '' : DIRECTORY_SEPARATOR.$Application), 
			$Application == '' ? $UseApplications : false, 
			'/main.php/');

		// $MainFiles can contain a main.php of an applicationless installation.
		// This ones are removed.
		if ($UseApplications)
		{
			foreach ($MainFiles as $Key => $File) 
			{
				if ($File == 'applications'.DIRECTORY_SEPARATOR.'main.php')
				{
					unset($MainFiles[$Key]);
				}
			}
		}

		return $MainFiles;
	}
	
	
	/**********************************************************************
	 * generateDocumentationData ()
	 * 
	 * Generates all data neccessary for a documentation based on a main.php
	 * file as an array to process in a next step.
	 * 
	 * @param String $MainFile Filename incl. path to a main.php, that's data
	 *                         should be read.
	 * 
	 * @returns Mixed Array with all data neccessary for a proper documentation
	 *                or FALSE, if an error occured.
	 */
	public static function generateDocumentationData ($MainFile)
	{
		if (!file_exists($MainFile))
			return FALSE;

		// To avoid a multiple generation of an 'Main' object of each main.php 
		// file, the classes within this main.php files are temporarily renamed, 
		// because all 'Main' classes are named identically.
		// So this 'Main' classes are renamed to a new unique class name an 
		// object can created of without any side effects.
		$ClassName = uniqid("Main");

		// Loading the class definition and changing the class name.
		$FileContent = file_get_contents($MainFile);
		$FileContent = '?>' . 
			str_ireplace(' Main ', " $ClassName ", $FileContent);

		// Creation of an instance of the renamed 'Main' class
		eval ($FileContent);
		if (!class_exists($ClassName))
			return FALSE;

		$MainInstance = new $ClassName();

		// Checking if the loades class extends the 'ApplicationTemplate' class
		// properly
		if (!is_subclass_of($MainInstance, 'ApplicationTemplate'))
			return FALSE;

		// Fetching all methods of the application to execute.
		$arAllMethods = get_class_methods($MainInstance);
		sort($arAllMethods);

		$ClassInfo = array();

		$PathInfo = explode(DIRECTORY_SEPARATOR, $MainFile);

		$ClassInfo['name'] = 
				count($PathInfo) == 3 ? $PathInfo[1] : '';
		$ClassInfo['baseurl'] = FormatString(
			"http://{0}{1}{2}{3}",
			$_SERVER['HTTP_HOST'],
			dirname($_SERVER['SCRIPT_NAME']),
			($ClassInfo['name'] != '' ? '/' : ''),
			($ClassInfo['name'] != '' ? $ClassInfo['name'] : ''));
		$ClassInfo['description'] = $MainInstance->ApplicationDescription;
		$ClassInfo['allowedcontenttypes'] = $MainInstance->AllowedContentTypes;

		// Add method information
		$ClassInfo['functions'] = array();

		foreach ($arAllMethods as $Method)
		{
			$arMethodArray = explode("_", $Method);

			// Getting documentation data of methods which's name consist of
			// REST verb and method name (e.g. "get_multiply()")
			if (count($arMethodArray) == 2)
			{
				$arMethodArray[0] = strtoupper($arMethodArray[0]);
				$arMethodArray[1] = strtolower($arMethodArray[1]);

				$arDocMethods = array 
					(
						"name"    => $arMethodArray[1],
						"method"  => $arMethodArray[0],
						"baseurl" => $ClassInfo['baseurl'] . '/' . $arMethodArray[1],
						"description" => '',
						"param" => '',
						"return" => ''
					);

				// Getting additional information of the function, if it was set
				// by the user.
				$strFunctionName = strtolower($arMethodArray[0]) . '_' . $arMethodArray[1];
				if (array_key_exists($strFunctionName, $MainInstance->FunctionInformation))
				{
					$arDocMethods = array_merge($arDocMethods, 
						$MainInstance->FunctionInformation[$strFunctionName]);
				}

				$ClassInfo['functions'][] = $arDocMethods;
			}
			// Getting documentation data from general REST methods, which
			// doesn't consist of REST verb and method name (e.g. "get()").
			else if (strtolower($Method) == strtolower($arMethodArray[0]))
			{
				$arDocMethods = array 
					(
						"name"    => $Method,
						"method"  => $arMethodArray[0],
						"baseurl" => $ClassInfo['baseurl'] . '/',
						"description" => '',
						"param" => '',
						"return" => ''
					);
				
				$ClassInfo['functions'][] = $arDocMethods;
			}
		}

		return $ClassInfo;
	}  
	
	
	/**********************************************************************
	 * generateFullDocumentationData ()
	 * 
	 * Generates all data neccessary for a complete documentation from all
	 * relevant main.php files for further processing.
	 * 
	 * @param String $Application Optional name of an application for which
	 *                            the documentation should be created. If no
	 *                            name is given, the documentation data is
	 *                            generated for all installed applications.
	 * 
	 * @returns Mixed Elements with all information of the existing main.php
	 *                files or FALSE, if an error occurs.
	 */
	public static function generateFullDocumentationData ($Application = "")
	{
		global $_CONFIG;

		$UseApplications = ($_CONFIG['webservice.useapplications'] == true);

		$MainFiles = Documentation::getRelevantMainFiles($Application);

		$DocData = array();

		$DocData['webservice'] = array();
		$DocData['webservice']['baseurl'] = FormatString("http://{0}{1}/", 
			$_SERVER['HTTP_HOST'], dirname($_SERVER['SCRIPT_NAME']));
		$DocData['webservice']['response.defaultstatuscode'] = $_CONFIG['response.defaultstatuscode'];
		$DocData['webservice']['response.defaultcontenttype'] = $_CONFIG['response.defaultcontenttype'];
		$DocData['webservice']['webservice.useapplications'] = $_CONFIG['webservice.useapplications'];

		if ($UseApplications == true)
		{
			$DocData['webservice']['applications'] = array();
		}
		else
		{
			$DocData['webservice']['functions'] = array();
		}

		foreach ($MainFiles as $File)
		{
			if ($UseApplications == true)
			{
				$DocData['webservice']['applications'][] = Documentation::generateDocumentationData($File);
			}
			else
			{
				$aTemp = Documentation::generateDocumentationData($File);
				$DocData['webservice']['functions'] = $aTemp['functions'];
			}
		}

		return $DocData;
	}
	
	
	/**********************************************************************
	 * getDocumentationAsJson ()
	 * 
	 * Returns the complete documentation as JSON string.
	 * 
	 * @param String $Application Optional name of an application, the
	 *                            documentation should created for or '' if
	 *                            the documentation should be created for all
	 *                            applications.
	 * 
	 * @returns String JSON encoded string with all documentation data.
	 */
	public static function getDocumentationAsJson ($Application = '')
	{
		$DocData = Documentation::generateFullDocumentationData($Application);

		return json_encode($DocData);
	}
	
	
	/**********************************************************************
	 * getDocumentationAsHtml ()
	 * 
	 * Returns the complete documentation als HTML string.
	 * 
	 * @param String $Application Optional name of an application, the
	 *                            documentation should created for or '' if
	 *                            the documentation should be created for all
	 *                            applications.
	 * 
	 * @returns String Documentation data as HTML string
	 */
	public static function getDocumentationAsHtml ($Application = '')
	{
		$DocData = Documentation::generateFullDocumentationData($Application);
		$RespTemp = new RestResponse();
		$UseApplications = ($DocData['webservice']['webservice.useapplications'] == true);

		// Header information
		$strHtmlReturn = FormatString(
			"<body style='font-family: Arial; font-size: 0.75em'>" .
			"  <h1>Webservice documentation</h1>\n" .
			"  <h1>1 General</h1>\n" .
			"  <table style='font-family: Arial; font-size:1.1em;'>\n" .
			"    <tr>\n" .
			"      <td><b>Base URL</b></td><td width='20'/><td><a href='{0}'>{0}</a></td>\n" .
			"    </tr><tr>\n" .
			"      <td><b>Default response code</b></td><td width='20'/><td>{1} ({2})</td>\n" .
			"    </tr>\n" .
			"    </tr><tr>\n" .
			"      <td><b>Default response type</b></td><td width='20'/><td>{3}</td>\n" .
			"    </tr>\n" .
			"    </tr><tr>\n" .
			"      <td><b>Webservice  mode</b></td><td width='20'/><td>{4}</td>\n" .
			"    </tr>\n" .
			"  </table>\n",
			$DocData['webservice']['baseurl'],
			$DocData['webservice']['response.defaultstatuscode'],
			$RespTemp->translateStatusCode($DocData['webservice']['response.defaultstatuscode']),
			$DocData['webservice']['response.defaultcontenttype'],
			$UseApplications == true ? "With applications" : "No applications used"
			);

		// The documentation mustn't show methods, which come directly from the
		// parent class 'ApplicationTemplate'.
		$arTemplateMethodes = get_class_methods ('ApplicationTemplate');
		
		// Applications
		if ($UseApplications)
		{
			$strHtmlReturn .= "  <h1>2 Application". (count($DocData['webservice']['applications']) > 1 ? 's' : '')."</h1>\n";
			$iCountApp = 0;
			foreach ($DocData['webservice']['applications'] as $App)
			{
				$iCountFunc = 0;
				$iCountApp++;
				$strHtmlReturn .= FormatString(
					"  <h2>2.{0} {1}</h2>\n" .
					"  <table style='font-family: Arial; font-size:1.1em;'>\n" .
					"    <tr>\n" .
					"      <td><b>Base URL</b></td><td width='20'/><td><a href='{2}'>{2}</a></td>\n" .
					"    </tr>\n" .
					"    <tr>\n" .
					"      <td><b>Allowed content types</b></td><td width='20'/><td>{3}</td>\n" .
					"    </tr>\n" .
					($App['description'] != '' ?
					   "    <tr>\n" .
					   "      <td><b>Description</b></td><td width='20'/><td>{4}</td>\n" .
					   "    </tr>\n" : "") .
					"   </table>"
					,
					$iCountApp,
					$App['name'],
					$App['baseurl'],
					count($App['allowedcontenttypes']) == 0 ? "Alle" : implode(', ', $App['allowedcontenttypes']),
					$App['description'] == '' ? '---' : $App['description']
					);

				$strHtmlReturn .= FormatString(
					"  <h3>2.{0}.1 Function". (count($App['functions']) > 1 ? 's' : '')."</h3>",
					$iCountApp,
					$iCountFunc
					);

				foreach ($App['functions'] as $Func)
				{
					// The methods of the 'ApplicationTemplate' class are skipped.
					if (in_array($Func['name'],$arTemplateMethodes))
						continue;
				
					$iCountFunc++;

					$strHtmlReturn .= FormatString(
						"  <h3>2.{0}.1.{1} {2}</h3>" .
						"  <table style='font-family: Arial; font-size:1.1em;'>\n" .
						"    <tr>\n" .
						"      <td><b>Base URL</b></td><td width='20'/><td><a href='{3}'>{3}</a></td>\n" .
						"    </tr>\n" .
						"    <tr>\n" .
						"      <td><b>REST method</b></td><td width='20'/><td>{4}</td>\n" .
						"    </tr>\n" .
						($Func['description'] != '' ?
						"    <tr>\n" .
						"      <td><b>Description</b></td><td width='20'/><td>{5}</td>\n" .
						"    </tr>\n" : "" ) .
						($Func['param'] != '' ?
						"    <tr>\n" .
						"      <td><b>Parameter information</b></td><td width='20'/><td>{6}</td>\n" .
						"    </tr>\n" : "" ) .
						($Func['return'] != '' ?
						"    <tr>\n" .
						"      <td><b>Response information</b></td><td width='20'/><td>{7}</td>\n" .
						"    </tr>\n" : "" ) .
						"   </table>",
						$iCountApp,
						$iCountFunc,
						$Func['name'],
						$Func['baseurl'],
						$Func['method'],
						$Func['description'],
						$Func['param'],
						$Func['return']
						);
				}
			}
		}
		else
		{
			$strHtmlReturn .= "  <h1>2 Function". (count($DocData['webservice']['functions']) > 1 ? 's' : '')."</h1>";

			$iCountFunc = 0;
			foreach ($DocData['webservice']['functions'] as $Func)
			{
				// Methods from the 'ApplicationTemplate' are skipped
				if (in_array($Func['name'],$arTemplateMethodes))
					continue;
				
				$iCountFunc++;
				print_r($arrTemplateMethods);
				$strHtmlReturn .= FormatString(
					"  <h2>2.{0} {1}</h2>" .
					"  <table style='font-family: Arial; font-size:1.1em;'>\n" .
					"    <tr>\n" .
					"      <td><b>Base URL</b></td><td width='20'/><td><a href='{2}'>{2}</a></td>\n" .
					"    </tr>\n" .
					"    <tr>\n" .
					"      <td><b>REST method</b></td><td width='20'/><td>{3}</td>\n" .
					"    </tr>\n" .
					($Func['description'] != '' ?
					"    <tr>\n" .
					"      <td><b>Description</b></td><td width='20'/><td>{4}</td>\n" .
					"    </tr>\n" : "" ) .
					($Func['param'] != '' ?
					"    <tr>\n" .
					"      <td><b>Parameter information</b></td><td width='20'/><td>{5}</td>\n" .
					"    </tr>\n" : "" ) .
					($Func['return'] != '' ?
					"    <tr>\n" .
					"      <td><b>Response information:</b></td><td width='20'/><td>{6}</td>\n" .
					"    </tr>\n" : "" ) .
					"   </table>",
					$iCountFunc,
					$Func['name'],
					$Func['baseurl'],
					$Func['method'],
					$Func['description'],
					$Func['param'],
					$Func['return']
					);
			}
		}

		return $strHtmlReturn;
	}
}
