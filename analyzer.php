<?php

include 'datatypes.php';

$movies = unserialize(file_get_contents('movies.txt'));

$locations = array();

$titles = array();

echo count($movies) . "\n";
$movies = array_unique_($movies);
echo count($movies) . "\n";

$location_translation = array(
	'Nivo Noordzaal 1 & 2' => 'Nivo Noord 1 & 2',
	'Nivo Noord 1&2' => 'Nivo Noord 1 & 2',
	'Kleine zaal Filmhuis' => 'Kleine Zaal Filmhuis',
	'Grote zaal Filmhuis' => 'Grote Zaal Filmhuis',
	'Friesland Bankzaal' => 'Friesland Bank Zaal',
	'Friesland bankzaal' => 'Friesland Bank Zaal',
	'Grote Zaal FIlmhuis' => 'Grote Zaal Filmhuis',
	'De Friesland Zorgverzekeraarzaal' => 'De Friesland Zorgverzekeraar Zaal',
	'Friesland Zorgverzekeraarzaal'    => 'De Friesland Zorgverzekeraar Zaal'
);

foreach ($movies as $movie)
	foreach ($movie->shows as $show)
		if (isset($location_translation[trim($show->location)]))
			$show->location = $location_translation[trim($show->location)];

foreach ($movies as $movie)
{
	foreach ($movie->shows as $show)
		@$locations[$show->location][] = $movie;
}

function array_unique_($input)
{
	$output = array();

	foreach ($input as $input_element)
	{
		foreach ($output as $output_element)
			if ($output_element == $input_element)
				continue 2;
			
		$output[] = $input_element;
	}

	return $output;
}

echo implode(array_keys($locations), "\n");

file_put_contents('movies-filtered.txt', serialize($movies));