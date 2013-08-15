<?php

if ( ! function_exists('value_for_key'))
{
	function value_for_key($keys, $array, $default = false)
	{	
		// Cast all variables as array.
		if ( ! is_array($array) )
		{
			if ( is_object($array) )
			{
				$array = (array)$array;
			}
			else
			{
				return $default;	
			}
		}

		// If array is empty return default.
		if ( empty($array) )
		{
			return $default;
		}

		if ( array_key_exists($keys, $array) )
		{
			return $array[$keys];		
		}

		// Prepare for loop
		$keys = explode('.', $keys);

		// If there is one key than we can skip the loop and check directly.
		if ( count($keys) == 1 )
		{
			return $default;
		}

		// Loop through array tree and find value.
		do
		{
			// Get the next key
			$key = array_shift($keys);

			if (isset($array[$key]))
			{
				if (is_array($array[$key]) AND ! empty($keys))
				{
					// Dig down to prepare the next loop
					$array = $array[$key];
				}
				else
				{
					// Requested key was found
					return $array[$key];
				}
			}
			else
			{
				// Requested key is not set
				break;
			}
		}
		while ( ! empty($keys));

		// Nothing found so return default.
		return $default;
	}
}