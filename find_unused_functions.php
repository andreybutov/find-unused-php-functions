#!/usr/bin/php -f

<?php

// ============================================================================
//
// find_unused_functions.php
//
// Find unused functions in a set of PHP files.
// version 1.3 - 08/20/2011
//
// ============================================================================
//
// USAGE: find_unused_functions.php <root_directory>
//
// NOTE: This is a 'quick-n-dirty' approach to the problem. This script
// only performs a lexical pass over the files, and does not respect 
// situations where different modules define identically named functions or 
// methods. If you use an IDE for your PHP development, it may offer a more 
// comprehensive solution.
//
// Requires PHP 5
//
// ============================================================================
//
// Copyright (c) 2011-2014, Andrey Butov. All Rights Reserved.
// This script is provided as is, without warranty of any kind. 
//
// http://www.andreybutov.com
//
// ============================================================================


// This may take a bit of memory...
ini_set('memory_limit', '2048M');

if ( !isset($argv[1]) ) 
{
	usage();
}

$root_dir = $argv[1];

if ( !is_dir($root_dir) || !is_readable($root_dir) )
{
	echo "ERROR: '$root_dir' is not a readable directory.\n";
	usage();
}

$files = php_files($root_dir);
$tokenized = array();

if ( count($files) == 0 )
{
	echo "No PHP files found.\n";
	exit;
}

$defined_functions = array();

foreach ( $files as $file )
{
	$tokens = tokenize($file);

	if ( $tokens )
	{
		// We retain the tokenized versions of each file,
		// because we'll be using the tokens later to search
		// for function 'uses', and we don't want to 
		// re-tokenize the same files again.

		$tokenized[$file] = $tokens;

		for ( $i = 0 ; $i < count($tokens) ; ++$i )
		{
			$current_token = $tokens[$i];
			$next_token = safe_arr($tokens, $i + 2, false);

			if ( is_array($current_token) && $next_token && is_array($next_token) )
			{
				if ( safe_arr($current_token, 0) == T_FUNCTION )
				{
					// Find the 'function' token, then try to grab the 
					// token that is the name of the function being defined.
					// 
					// For every defined function, retain the file and line
					// location where that function is defined. Since different
					// modules can define a functions with the same name,
					// we retain multiple definition locations for each function name.

					$function_name = safe_arr($next_token, 1, false);
					$line = safe_arr($next_token, 2, false);

					if ( $function_name && $line )
					{
						$function_name = trim($function_name);
						if ( $function_name != "" )
						{
							$defined_functions[$function_name][] = array('file' => $file, 'line' => $line);
						}
					}
				}
			}
		}
	}
}

// We now have a collection of defined functions and
// their definition locations. Go through the tokens again, 
// and find 'uses' of the function names. 

foreach ( $tokenized as $file => $tokens )
{
	foreach ( $tokens as $token )
	{
		if ( is_array($token) && safe_arr($token, 0) == T_STRING )
		{
			$function_name = safe_arr($token, 1, false);
			$function_line = safe_arr($token, 2, false);;

			if ( $function_name && $function_line )
			{
				$locations_of_defined_function = safe_arr($defined_functions, $function_name, false);

				if ( $locations_of_defined_function )
				{
					$found_function_definition = false;

					foreach ( $locations_of_defined_function as $location_of_defined_function )
					{
						$function_defined_in_file = $location_of_defined_function['file'];
						$function_defined_on_line = $location_of_defined_function['line'];

						if ( $function_defined_in_file == $file && 
							 $function_defined_on_line == $function_line )
						{
							$found_function_definition = true;
							break;
						}
					}

					if ( !$found_function_definition )
					{
						// We found usage of the function name in a context
						// that is not the definition of that function. 
						// Consider the function as 'used'.

						unset($defined_functions[$function_name]);
					}
				}
			}
		}
	}
}


print_report($defined_functions);	
exit;


// ============================================================================

function php_files($path) 
{
	// Get a listing of all the .php files contained within the $path
	// directory and its subdirectories.

	$matches = array();
	$folders = array(rtrim($path, DIRECTORY_SEPARATOR));
	
	while( $folder = array_shift($folders) ) 
	{
		$matches = array_merge($matches, glob($folder.DIRECTORY_SEPARATOR."*.php", 0));
		$moreFolders = glob($folder.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR);
		$folders = array_merge($folders, $moreFolders);
	}

	return $matches;
}

// ============================================================================

function safe_arr($arr, $i, $default = "")
{
	return isset($arr[$i]) ? $arr[$i] : $default;
}

// ============================================================================

function tokenize($file)
{
	$file_contents = file_get_contents($file);

	if ( !$file_contents )
	{
		return false;
	}

	$tokens = token_get_all($file_contents);
	return ($tokens && count($tokens) > 0) ? $tokens : false;
}

// ============================================================================

function usage()
{
	global $argv;
	$file = (isset($argv[0])) ? basename($argv[0]) : "find_unused_functions.php";
	die("USAGE: $file <root_directory>\n\n");
}

// ============================================================================

function print_report($unused_functions)
{
	if ( count($unused_functions) == 0 )
	{
		echo "No unused functions found.\n";
	}

	$count = 0;
	foreach ( $unused_functions as $function => $locations )
	{
		foreach ( $locations as $location )
		{
			echo "'$function' in {$location['file']} on line {$location['line']}\n";
			$count++;
		}
	}

	echo "=======================================\n";
	echo "Found $count unused function" . (($count == 1) ? '' : 's') . ".\n\n";
}

// ============================================================================

/* EOF */
