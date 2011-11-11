<?php

include 'datatypes.php';
include '/Users/jelmer/Sites/Werk/movies.ikhoefgeen.nl/_includes/imdb.php';

$movies = unserialize(file_get_contents('movies-filtered.txt'));
$imdb = \imdb\imdb();

foreach ($movies as $i => $movie)
{
	printf("%3d van %3d: %s\r", $i, count($movies), $movie->title);

	// sla films die we al hebben gehad over.
	if ($movie->imdb_id)
		continue;

	$results = $imdb->find($movie->title);

	if (count($results))
		$movie->imdb_id = $results[0]->id();
	
	// sla tussendoor ook maar op.
	file_put_contents('movies-augmented.txt', serialize($movies));
}

echo "\n";
