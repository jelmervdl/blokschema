<?php

error_reporting(E_ALL);
date_default_timezone_set('Europe/Amsterdam');

class Movie
{
	public $title;

	public $director;

	public $thumbnail;

	public $image;

	public $url;

	public $runtime;

	public $shows = array();

	public $imdb_id;

	public function __construct($url = null)
	{
		$this->url = $url;
	}
}

class Show
{
	public $time;

	public $location;

	public $movie;
}


class MovieList extends ArrayObject
{
	public function locations()
	{
		$locations = array();

		foreach ($this as $movie)
			foreach ($movie->shows as $show)
				$locations[] = $show->location;
		
		return array_unique($locations);
	}

	public function shows_at($location)
	{
		$shows = array();

		foreach ($this as $movie)
			foreach ($movie->shows as $show)
				if ($show->location == $location)
				{
					$show->movie = $movie;
					$shows[] = $show;
				}
		
		return $shows;
	}
}

function load_movies($source)
{
	return new MovieList(unserialize(file_get_contents($source)));
}