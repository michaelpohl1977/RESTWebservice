<?php  

/**
 * general.php
 * 
 * Collection of helper functions
 *
 * @author Michael Pohl (www.simatex.de)
 */




/*******************************************************************************
 * FormatString()
 * 
 * Formats a string based on placeholders and returns the result
 * 
 * @param String $Text String with any number of placeholders "{0}" - "{n}" 
 *                     representing the passed parameters.
 *                     (e.g. Format("I am 1 and you {0} {1}", "are", 2))
 * 
 * @returns String Formatted Text with replaced placeholders
 */
function FormatString ($Text = "" /*, ... */)
{
	$Arguments    = func_get_args();
	$ElementCount = count($Arguments);

	for ($i = 1; $i < $ElementCount; $i++)
	{
		$Text = str_replace("{".($i-1)."}", $Arguments[$i], $Text);
	}

	return $Text;
}


/*******************************************************************************
 * SearchFiles()
 * 
 * Searches (recursively) for all files within a given directory, which match a 
 * certain filename pattern.
 * 
 * @param String $BaseDir   Directory in which the search should start
 * @param Bool   $Recursive true, if the search should be done recursively
 *                          (default), otherwise false
 * @param String $Filter    Regular expression als filename pattern
 *                          (e.g. "/test.txt/" - default is "(.*)" for all files
 * 
 * @returns Array Result of the file search as array with the complete path
 *                including the filename
 */
function SearchFiles($BaseDir, $Recursive = true, $Filter = "(.*)")
{
	$Files = array();

	foreach(new DirectoryIterator($BaseDir) as $Item)
	{
		if($Item->isDir() && $Recursive == true)
		{
			if (!$Item->isDot())
			{
			   $Files = array_merge($Files, SearchFiles($Item->getPathname(), $Recursive, $Filter));
			}
			continue;
		}

		if (preg_match($Filter, $Item->getFilename()))
		{
			$Files[] = $Item->getPathname();
		}
	} 
	
	return $Files;
}
