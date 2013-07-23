<?php
/*
This file is a sandbox for texting/developing the Debug class.
*/
require_once "Debug.php";
echo "<pre>";
function a()
{
	Debug::p(1 == 1, "What happens if I enter a boolean into the Debugger and request it show no backtrace?");

	$object = new Debug();
	Debug::p($object, "What happens if I enter an object into the Debugger and request only the 'last' call in the backtrace?", "last");

	Debug::p(null, "What happens if I pass no object ('null'), request a full backtrace ('all') and enter 'true' as the last (4th) argument?", 'all', true);

	echo "This is never shown, that's what.";
}

function b()
{
	a();
}

function c()
{
	b();
}

c();