# RESTWebservice 1.8.1

RESTWebservice is a very lean implementation of a PHP webservice without installation.
Just copy the files of this library into any directory, to enable a powerful webservice endpoint.

RESTWebservice not only supports the common kind of request verbs (GET, POST etc.) but also every other user defined verb you want to use. With nearly no effort you can turn the webservice in a collection of several webservice applications in one base endpoint.

You only have to fill the existing response object and the RESTWebservice will handle everything else (response headers like content-length, content-type, status-codes and many more).

## Documentation

Visit https://www.simatex.de/documentation/restwebservice for detailed information.

## Example

    // Webservice with endpoint ".../returnmessage/<message>" that 
	// accepts GET requests and returns the given message as response.
    
	// 1. Create the endpoint's Main class in file '.../applications/main.php'
	class Main extends ApplicationTemplate
	{
		
		// 2. Create a method with the name '<accepted REST verb>_<endpoint name>()'
		function get_Returnmessage ()
		{
		
			// 3. Read the given message parameter and send
			//    it back to the client as webservice response
			$strOriginalMessage = $this->Request->getUnnamedParameter[0];
			$this->Response->setTextContent( $strOriginalMessage );
		
		}
		
	}

## History

**Version 1.8.1 - 2023-10-29**
* Correction in handling of CORS-domain handling

**Version 1.8.0 - 2022-12-19**
* Correction of possible errors when creating the application documentation
* Several code restructuring

**Version 1.7.0 - 2019-01-24**
  * The webservice is now able to be used completely without webservice methods (e.g. get() instead of get_Multiply())
  * Code optimization
  * Correction of documentation functions
  
**Version 1.6.0 - 2017-02-08**
  * Error correction in the evaluation of unnamed parameters when a file name and URL parameters are used at the same time
  * The error message about not existing (application-)methods now returns information about the used REST verb ("POST", "GET" etc.)
  * Extension of the RestResponse object to allow the setting of header information/parameters
  * Extension of the RestRequest object to allow reading and processing values sent in the header of the request
  
**Version 1.5.0 - 2015-02-18**
  * RESTWebservice updates are not longer overwriting the existing configuration changed by the user. Instead of this every new version only contains a 'configuration.php.sample' file to be used as base configuration.
  
**Version 1.4.1 - 2014-08-18**
  * Correction of in some cases incorrectly processed PATH_INFO (different webserver installations create this constant with or without a filename)
  
**Version 1.4.0 - 2014-02-25**
  * Restructuring the webserver base to enable working completely without applications but only with functions if necessary
  * Within the webservice application/function collection the documentation information description, parameter information and return information can be set with 'addFunctionInformation()'
  * Optional information about the webservice application/function collection can be set with the helper methods 'setDescription()' and 'setAllowedContentTypes()'
  * Complete rewriting of the documentation module for creation of automatically generated HTML/JSON documentations
  * Correction of regognition of filenames sent as unnamed paramters
  * Several smaller corrections
  
**Version 1.3.0 - 2013-11-22**
  * Adding request attributes for a structured reading of file parameters sent via POST request (parallel to named/unnamed paramters)
  * Adding the methods 'hasUnnamedParameters()', 'hasNamedParameters()' and 'hasFileParameters()' to check if such parameters exist
  * Correction of the evaluation of named paramters in POST requests. In some cases they weren't read and filled correctly
  * Correction of the request URL evaluation. In some cases the evaluated URLs were incorrect.
  * Converting the whole project to UTF-8
  * Several correctens and clean ups
  
**Version 1.2.1 - 2013-11-20**
  * Removement of all ShortOpenTags in the whole project to rise the compatibility to several PHP configurations
  
**Version 1.2.0 - 2012-08-02**
  * Adding the two overridable application methods '__initCall()' and '__exitCall()' to execute any code before or after the webservice processing.
  * Complete rewriting of the URL redirect to allow a correct execution of the webservice methods under PHP installed as FCGI.
  * The install check of the application doesn't test the direct extension of 'ApplicationTemplate' but checks, if any class on any layer extends 'ApplicationTemplate'. This allows nested extension of freely definable base classes.
  * Correction of link paths in the automatically generated application documentation. Shown links were uncomplete, when the webservice wasn't installed in the root directory of the webserver.
  
**Version 1.1.0 - 2012-07-20**
  * Replacement of _SERVER['PATH_INFO'] by a self detected path value, if the webserver doesn't provide PATH_INFO.
  * Catching several server exceptions thrown by not existing array indexes when different call types are used.
  
**Version 1.0.0 - 2011-08-26** 
  * Initial version