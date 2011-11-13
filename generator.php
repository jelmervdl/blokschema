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

	public $font_size = 100;

	public $title;

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
		return sprintf('<li style="top: %dpx; left: %dpx; width: %dpx; height: %dpx; font-size:%d%%" title="%s">%s</li>',
			$this->top, $this->left, $this->width, $this->height, $this->font_size,
			htmlspecialchars($this->title, ENT_QUOTES, 'utf-8'), $this->innerHTML);
	}
}

class TimeLine
{
	public $width;

	public $height;

	public $title;

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

		// Is one of the blocks exactly the same? Well, that is just stupid!
		if (in_array($block, $overlapping_blocks))
			return;

		$overlapping_blocks[] = $block;

		// Adjust height and position of those block so they no longer overlap.
		$a_block_height = $this->height / count($overlapping_blocks);
		for ($i = 0; $i < count($overlapping_blocks); ++$i)
		{
			$a_block = $overlapping_blocks[$i];
			$a_block->height = $a_block_height;
			$a_block->font_size = 50 + 50 / count($overlapping_blocks);
			$a_block->top = $i * $a_block_height;
		}

		$this->blocks[] = $block;
	}

	public function asHTML()
	{
		return sprintf('<ul class="show-track" title="%s" style="width: %dpx; height: %dpx">%s</ul>',
			htmlspecialchars($this->title, ENT_QUOTES, 'utf-8'),
			$this->width, $this->height,
			implode(array_map(call_method('asHTML'), $this->blocks), "\n"));
	}
}

function format_title($title)
{
	return preg_replace('/^(.+?): /', '', utf8_decode($title));
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
		$block->title = format_title($show->movie->title);
		$block->innerHTML = sprintf('<a href="%s" data-hash="%s">%s</a>',
			$show->movie->url,
			md5($show->movie->url),
			htmlspecialchars(format_title($show->movie->title), ENT_COMPAT, 'utf-8'));
		
		$timeline->addBlock($block);
	}

	$timeline->title = $location;
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

	$timezone_offset = $time_range->start->getTimeZone()->getOffset($time_range->start);
	printf('<ul class="timeline" data-from="%s" data-till="%s">%s</ul>',
		$time_range->start->getTimestamp() - $timezone_offset,
		$time_range->end->getTimestamp() - $timezone_offset,
		implode("\n", $ticks));
}


$movies = load_movies(isset($argv[1]) ? $argv[1] : 'movies-augmented.txt');

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

?>
<!DOCTYPE html>
<html>
	<head>
		<title>NFF 2011</title>
		<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<link rel="stylesheet" href="timetable.css">
		<script src="timetable.js"></script>
	</head>
	<body>
		<?php generate_timeline($time_range) ?>

		<?php foreach ($locations as $location)
			generate_blocks($location, $time_range); ?>
	</body>
</html>