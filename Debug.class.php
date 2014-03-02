<?php
/*
This class is used to print out detailed information about various pieces of data.
It uses print_r in order to provide as detailed an output of the variable as possible.
It can display a custom message with the printout of the variable.
Its primary feature is providing a configurable backtrace: the class will provide either
a 1-line summary of the last function call (other than its own), a full detail of the 
last function, or a full backtrace.
*/
class Debug
{
	/* Retrieve a backtrace of function calls based off of how detailed the user wants the output to be */
	private static function getTrace($traceLevel = null)
	{
		//Get the full backtrace
		$trace = debug_backtrace();
		//Initialize a variable to record where the user actually called Debug
		$debugCall = "";

		//Loop through every call in the backtrace
		while(isset($trace[0]["class"]) && $trace[0]["class"] == "Debug")
		{
			//Generate a 1-line, file-name, line-number reminder of where the user put the Debug call
			if(!isset($trace[1]["class"]) || $trace[1]["class"] != "Debug")
			{
				$debugCall = "(Debug in ".$trace[0]["file"]." at line: ".$trace[0]["line"].")";
			}
			//If this function call is in the debug class, remove it
			$trace = array_slice($trace, 1);
		}

		//Does the user want a specific chunk of the backtrace?
		switch(strtolower($traceLevel))
		{
			case 1:
			case "last":
				//Give the summary and the last function call
				$trace = array_slice($trace, 0, 1);
				break;

			case 2:
			case "all":
				//Give the summary and the full backtrace
				break;

			default:
				//Give just the summary
				$trace = "";
				break;
		}

		//Return the backtrace details.
		return $debugCall."\n".print_r($trace, true);
	}

	/* Compile a string of output based on what arguments the user wants shown */
	private static function getOutput($obj = null, $msg = null, $traceLevel = null)
	{
		//Get the type of the object to format output
		$type = gettype($obj);

		//If they supplied a custom message, prepend that to the output
		if(isset($msg))
		{
			//make sure it ends in a newline
			if(substr($msg, -1) != "\n")
			{
				$msg .= "\n";	
			}
		}
		else
		{
			$msg = "";	
		}

		//Give as complete a detail of that object as possible, formatted based on its type
		switch($type)
		{
			case "NULL":
				$obj = array(
					'value' => "NULL");
				break;

			case "string":
				$obj = array(
					'type' => $type,
					'length' => strlen($obj),
					'original' => $obj);
				break;

			case "boolean":
				//Boolean values can't be print_r'd, so convert them to strings
				$obj = array(
					'value' => $obj ? "true" : "false");
				break;

			case "object":
				$obj = array(
					'type' => $type,
					'properties' => get_object_vars($obj),
					'methods' => get_class_methods($obj),
					'original' => $obj);
				break;

			default:
				break;
		}

		//Return the output to be shown in one of the public functions
		return $msg.ucwords($type)." details: ".print_r($obj, true).self::getTrace($traceLevel);
	}

	/* Write the object details to the error_log with backtrace details */
	public static function e($obj = null, $msg = null, $traceLevel = null, $die = false)
	{
		error_log(self::getOutput($obj, $msg, $traceLevel));
		if($die)
		{
			die("Killed");
		}
	}

	/* Write the object details to the page with backtrace details */
	public static function p($obj = null, $msg = null, $traceLevel = null, $die = false)
	{
		//Encapsulate output in <pre> tags to ensure clean formatting on page
		echo "<pre>".self::getOutput($obj, $msg, $traceLevel)."</pre>";
		if($die)
		{
			die("Killed");
		}
	}

	/* Get the parameter names from the source code of a function */
	public static function getParameters() {
		//Get a handle on the file and function which was called just before this one -- the one whose parameters we want
		$trace = debug_backtrace();

		//Get the sourcecode from the file
		$code = file_get_contents($trace[1]["file"]);

		//Cut out whatever text matches the function name up until its closing round-bracket
		preg_match('/'.$trace[1]["function"].'.*\)/', $code, $params);
		$params = $params[0];

		//Refine the params string so that it is an array of the parameter names
		$start = strpos($params, "(");
		$params = substr($params, $start + 1, -1);
		$params = str_replace(" ", "", $params);
		$params = explode(",", $params);
		return $params;
	}

	/* Get statistics on running a function */
	public static function functionStats($outputTo = "log", $function, $arguments)
	{
		$startTime = time();
		$startMem = memory_get_usage();
		$output = call_user_func_array($function, $arguments);
		$runtime = time() - $startTime; //Get the execution time
		$memoryUse = memory_get_peak_usage(true) - $startMem;

		$statistics = array(
			"Runtime" => $runtime,
			"Memory usage" => $memoryUse
			);

		//Output statistics to error_log or page
		switch(strtolower($outputTo))
		{
			case 1:
			case "log":
				self::e($statistics, "Runtime:");
				break;
			case 2:
			case "page":
				self::p($statistics, "Runtime:");
				break;
			default:
				break;
		}

		//return function execution so as to not interrupt application flow
		return $output;
	}
}