<?php 

/**
 * dispatch.php
 * 
 * Dispatcher responsible for the forwarding of requests to the existing
 * applications/services
 * 
 * @author Michael Pohl (www.simtex.de)
 */




if (!file_exists('configuration.php'))
{
	die ("Configuration-file 'configuration.php' does not exist");
}

require_once 'configuration.php';

/**
 * Deactivation of STDOUT. Ouotput is only allowed to responses and the end
 * of the requrest. Because of headers, that are to send, no other output
 * must be done prior this this.
 */
if ($_CONFIG['webservice.allowoutput'] === false) 
{
	ob_start();
}

require_once 'include/restrequest.php';
require_once 'include/restresponse.php';
require_once 'include/applicationlauncher.php';
require_once 'include/applicationtemplate.php';

/**
 * General globally valid directories
 */
$GlobalBaseDir = dirname(__FILE__);
$GlobalAppDir  = $GlobalBaseDir . "/applications";

/**
 * The response object will contain all data that should be send to the remote
 * side as response to it's request
 */
$CurrentResponse = new RestResponse();

/**
 * The request object will contain all data send from the remote side to the
 * webservice
 */
$CurrentRequest = new RestRequest();

/**
 * The application launcher object brings together all webservice 
 * functionallity, the request data and the response object, which has to be 
 * send back to the remote side
 */
$CurrentApplication = new ApplicationLauncher ($CurrentRequest, $CurrentResponse);

/**
 * Only if the requested application and method exist, the processing of the
 * request starts.
 */
if ($CurrentApplication->isInstalled)
{
	// Is there a real method call or a call of the documentation URL?
	if ($CurrentApplication->isDocumentationRequest ||
		$CurrentApplication->isJDocumentationRequest)
	{
		// Showing documentation
		$CurrentApplication->documentation();
	}
	else
	{
		// Processing the webservice request
		$CurrentApplication->execute();
	}
}

/**
 * Activation of STDOUT. At the end of the processing the request object is
 * allowed to write output.
 */
if ($_CONFIG['webservice.allowoutput'] === false) 
{
	ob_end_clean();
}

/**
 * When all processing is done, the data stored in the request object is sent
 * to the remote side
 */
$CurrentResponse->Send();

