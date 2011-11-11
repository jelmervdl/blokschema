<?php

include 'datatypes.php';

function parse_page($url)
{
	$html = download_page($url);

	//$html = html_entity_decode($html, ENT_QUOTES, "utf-8");

	$doc = new DOMDocument();
	@$doc->loadHTML($html);
	$doc->formatOutput = true;

	$xpath = new DOMXPath($doc);
	
	$movie_nodes = $xpath->query('/html/body/ul/li');
	
	if ($movie_nodes->length == 0)
		return array();

	$movies = array();
	foreach ($movie_nodes as $movie_node)
		$movies[] = parse_movie_node($xpath, $movie_node);
	
	return $movies;
}

function parse_movie_node(DOMXPath $xpath, DOMNode $movie_node)
{
	$movie = new Movie;

	//echo $movie_node->ownerDocument->saveXML($movie_node);
	//exit;

	$movie->thumbnail = $xpath->evaluate('string(div[contains(@class,"thumbnail")]/a/img/@src)', $movie_node);

	$movie->title = $xpath->evaluate('string(div[contains(@class,"details")]/h2/a)', $movie_node);
	$movie->url = $xpath->evaluate('string(div[contains(@class,"details")]/h2/a/@href)', $movie_node);

	$movie->director = $xpath->evaluate('string(div[contains(@class,"details")]/h2/span[contains(@class,"movie-director")])', $movie_node);

	if (!$movie->title)
		echo $movie_node->ownerDocument->saveXML($movie_node);

	return $movie;
}

function fetch_movie(Movie $movie)
{
	$html = download_page($movie->url);
	$dom = new DOMDocument();
	@$dom->loadHTML($html);
	$dom->formatOutput = true;
	$xpath = new DOMXPath($dom);

	$movie_node = $xpath->query('//article[contains(@class, "cff_movie")]')->item(0);

	$details_node = $xpath->query('//div[contains(@class, "movie-details")]', $movie_node)->item(0);

	parse_movie_details($movie, $details_node);

	$movie->image = $xpath->evaluate('string(id("movie-trailer")/img/@src)');
	
	$voorstellingen_node = $xpath->query('//div[contains(@class, "movie-showing")]', $movie_node)->item(0);
	foreach ($xpath->query('ul/li', $voorstellingen_node) as $show_node)
	{
		$show = new Show;
		$show->time = new DateTime($xpath->evaluate('string(time/@datetime)', $show_node));
		$show->location = trim_location($xpath->evaluate('string(*[contains(@class,"location")])', $show_node));
		$movie->shows[] = $show;
	}
}

function parse_movie_details(Movie $movie, DOMNode $details_node)
{
	$details_line = $details_node->firstChild;
	while ($details_line)
	{
		if ($details_line instanceof DOMText)
			parse_movie_details_text($movie, $details_line->textContent);
		
		$details_line = $details_line->nextSibling;
	}
}

function parse_movie_details_text(Movie $movie, $line)
{
	if (preg_match('/^(.+?), (\d{4}), (\d+)\'$/', $line, $data))
	{
		$movie->countries = array_map('trim', explode(',', $data[1]));
		$movie->year = intval($data[2]);
		$movie->runtime = intval($data[3]);
	}
	elseif (preg_match('/^Regie: (.+?)$/',  $line, $data))
	{
		$movie->directors = array_map('trim', preg_split('/&|,/', $data[1]));
	}

	else
		file_put_contents('details-lines.txt', $line . "\n", FILE_APPEND);
}

function download_page($url)
{
	$cache_name = md5($url);

	if (file_exists("cache/$cache_name"))
		return file_get_contents("cache/$cache_name");
	
	$data = file_get_contents($url);
	file_put_contents("cache/$cache_name", $data);

	return $data;
}

function trim_location($location)
{
	return preg_replace('/^Locatie: /', '', $location);
}

function main()
{
	$movies = array();
	$i = 0;

	// clean out details-lines.txt log file.
	file_put_contents('details-lines.txt', '');

	do {
		$url = sprintf('http://noordelijkfilmfestival.nl/wp-content/themes/cff/ajax/movielist.php?action=list&c=p%d&y=2011', $i++);
		$movies_on_page = parse_page($url);
		$movies = array_merge($movies, $movies_on_page);
	} while (count($movies_on_page));

	foreach ($movies as $n => $movie)
	{
		printf("Processing movie %d of %d: %s\n",
			$n + 1, count($movies), $movie->title);
		
		fetch_movie($movie);
	}
	
	file_put_contents('movies.txt', serialize($movies));
}

main();