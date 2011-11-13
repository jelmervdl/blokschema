function Ruler(timeline)
{
	this.timeline = timeline;
	this.ruler = document.createElement('li');
	this.ruler.className = 'current-time';
	this.ruler.style.height = this.timeline.clientHeight;
	this.timeline.appendChild(this.ruler, this.timeline);
}

Ruler.prototype.update = function()
{
	var start = new Date(),
		end = new Date();
		now = new Date();

	start.setTime(this.timeline.getAttribute('data-from') * 1000);
	end.setTime(this.timeline.getAttribute('data-till') * 1000);
	var offset = Math.min(
		now.getTime() - start.getTime(), // einde van vandaag
		end.getTime() - start.getTime() // einde van festival
	);

	this.ruler.style.left = offset / (60 * 1000) + 'px';
}

function toggleFavorite(show_el)
{
	var favorite = window.localStorage[show_el.title] =
		window.localStorage[show_el.title] == 'True'
			? ''
			: 'True';
	
	markFavoriteShows();
}

function markFavoriteShows()
{
	Array.prototype.forEach.call(
		document.querySelectorAll('.show-track > li'),
		markFavoriteShow);
}

function markFavoriteShow(show)
{
	show.className = window.localStorage[show.title] ? 'favorite' : '';
}

function openDetails(show)
{
	var hash = show.getElementsByTagName('a').item(0).getAttribute('data-hash');

	loadDetails(hash, function (response) {
		detailsPullOver.innerHTML = response;
		try { fixTrailer(detailsPullOver); } catch(e) {}

		var closeButton = document.createElement('a');
			closeButton.href = '#';
			closeButton.className = 'close-button';
			closeButton.innerHTML = '&times;';
			closeButton.onclick = function() {
				closeDetails();
				return false;
			};
		
		detailsPullOver.appendChild(closeButton);
	});

	detailsPullOver.className = 'visible';
}

function closeDetails()
{
	detailsPullOver.className = 'hidden';
}

function fixTrailer(root)
{
	var script = root.getElementsByTagName('script').item(0).innerText,
		html = script.match(/\.html\('(.+)'\)/)[1];
	
	window.showTrailer = function() {
		document.getElementById('movie-trailer').innerHTML = html;
	}
}

function cacheDetails(show)
{
	var hash = show.getElementsByTagName('a').item(0).getAttribute('data-hash');

	if (show.offsetLeft < window.ruler.ruler.offsetLeft)
		return;

	loadDetails(hash, function() {});
}

function loadDetails(hash, callback)
{
	if (window.localStorage[hash] && document.location.hash != '#nocache')
	{
		callback(window.localStorage[hash]);
		return;
	}
	
	var request = new XMLHttpRequest();
	request.open('GET', 'articles/' + hash, true);
	request.onreadystatechange = function() {
		if (request.readyState == 4)
		{
			window.localStorage[hash] = request.responseText;
			callback(window.localStorage[hash]);
		}
	};
	request.send();
}

window.onload = function()
{
	window.detailsPullOver = document.createElement('div');
	detailsPullOver.id = 'details-pull-over';
	detailsPullOver.className = 'hidden';
	document.body.appendChild(detailsPullOver);

	window.ruler = new Ruler(document.getElementsByClassName('timeline').item(0));
	window.ruler.update();
	window.scrollTo(window.ruler.ruler.offsetLeft, 0);
	setInterval(function() {
		window.ruler.update();
	}, 6000);

	Array.prototype.forEach.call(
		document.querySelectorAll('.show-track > li'),
		function(show) {
			show.addEventListener('dblclick', function(e) {
				toggleFavorite(show);
				e.preventDefault();
				e.cancel();
			});

			show.addEventListener('click', function(e) {
				openDetails(show);
				e.cancelBubble = true;
				e.preventDefault();
			});

			markFavoriteShow(show);

			cacheDetails(show);
		}
	);

	var focusListener = function(e) {
		var target = e.target;
		do {
			if (target == detailsPullOver)
				break;
			
			if (target == document.body)
				closeDetails();

		} while (target = target.parentNode);
	};

	document.body.addEventListener('click', focusListener);
}