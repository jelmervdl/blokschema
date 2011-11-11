<?php

include 'datatypes.php';

function call_method($method)
{
	$bound_args = func_get_args();
	array_shift($bound_args);

	return function($instance) use ($method, $bound_args) {
		return call_user_func_array(array($instance, $method), $bound_args);
	};
}

function curry($callback, $arg0 = null)
{
	$bound_args = func_get_args();
	array_shift($bound_args);

	return function() use ($callback, $bound_args) {
		$calltime_args = func_get_args();
		return call_user_func($callback, $bound_args + $calltime_args);
	};
}

class Block
{
	public $top;

	public $left;

	public $width;

	public $height;

	public $innerHTML;

	public function overlaps(Block $other)
	{
		return $other->left >= $this->left &&
		       $other->left <= $this->left + $this->width
			|| $other->left + $other->width >= $this->left &&
			   $other->left + $other->width <= $this->left + $this->width;
	}

	public function asHTML()
	{
		return sprintf('<li style="top: %dpx; left: %dpx; width: %dpx; height: %dpx">%s</li>',
			$this->top, $this->left, $this->width, $this->height, $this->innerHTML);
	}
}

class TimeLine
{
	public $width;

	public $height;

	public $blocks = array();

	public function __construct($width, $height)
	{
		$this->width = $width;
		$this->height = $height;
	}

	public function addBlock(Block $block)
	{
		// default values for while calculating overlappings
		$block->top = 0;
		$block->height = $this->height;

		// Find all overlapping/overlapped blocks
		$overlapping_blocks = array_values(array_filter($this->blocks, array($block, 'overlaps')));
		$overlapping_blocks[] = $block;

		// Adjust height and position of those block so they no longer overlap.
		$a_block_height = $this->height / count($overlapping_blocks);
		for ($i = 0; $i < count($overlapping_blocks); ++$i)
		{
			$a_block = $overlapping_blocks[$i];
			$a_block->height = $a_block_height;
			$a_block->top = $i * $a_block_height;
		}

		$this->blocks[] = $block;
	}

	public function asHTML()
	{
		return sprintf('<ul class="show-track" style="width: %dpx; height: %dpx">%s</ul>',
			$this->width, $this->height,
			implode(array_map(call_method('asHTML'), $this->blocks), "\n"));
	}
}

$movies = load_movies('movies-augmented.txt');

$locations = $movies->locations();

usort($locations, function($a, $b) use ($movies) {
	$shows_at_a = count($movies->shows_at($a));
	$shows_at_b = count($movies->shows_at($b));

	return $shows_at_a != $shows_at_b
		? ($shows_at_a > $shows_at_b ? -1 : 1)
		: 0;
});

$time_range = (object) array(
	'start' => new DateTime('9 nov 2011 09:00'),
	'end' => new DateTime('14 nov 2011 01:00')
);

function format_title($title)
{
	return preg_replace('/^([\w\s]+): /', '', utf8_decode($title));
}

function generate_blocks($location, $time_range)
{
	global $movies;

	$timeline = new TimeLine(($time_range->end->getTimestamp() - $time_range->start->getTimestamp()) / 60, 40);

	foreach ($movies->shows_at($location) as $show)
	{
		$block = new Block;
		$block->left = ($show->time->getTimestamp() - $time_range->start->getTimestamp()) / 60;
		$block->width = max($show->movie->runtime, 40);
		$block->innerHTML = sprintf('<a href="%s">%s</a>',
			$show->movie->url,
			htmlspecialchars(format_title($show->movie->title), ENT_COMPAT, 'utf-8'));
		
		$timeline->addBlock($block);
	}

	printf("<h2>%s</h2>\n%s\n\n", htmlspecialchars($location), $timeline->asHTML());
}

function generate_timeline($time_range)
{
	$ticks = array();

	$time_range_seconds = $time_range->end->getTimestamp() - $time_range->start->getTimestamp();

	for ($s = 0; $s < $time_range_seconds; $s += 3600)
	{
		$timestamp = new DateTime('@' . ($time_range->start->getTimestamp() + $s));
		$hour = $timestamp->format('H');

		$ticks[] = sprintf('<li style="left: %dpx">%s</li>',
			$s / 60,
			$hour == 0
				? $timestamp->format('l')
				: $timestamp->format('H:00'));
	}

	printf('<ul class="timeline">%s</ul>', implode("\n", $ticks));
}

function generate_css()
{
	echo '<link rel="stylesheet" href="timetable.css">';
}

generate_css();

generate_timeline($time_range);

foreach ($locations as $location)
	generate_blocks($location, $time_range);