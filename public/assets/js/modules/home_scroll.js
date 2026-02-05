function homeScroll(id, dir) {
  const el = document.getElementById(id);
  if (!el) return;

  let delta = 520;
  const w = window.innerWidth;

  if (w < 768) delta = 320;
  else if (w < 992) delta = 420;

  el.scrollLeft += dir * delta;
}

function initHomeScroll() {
  const books = document.getElementById('home-books');
  const movies = document.getElementById('home-movies');

  if (!books && !movies) return;

  window.homeScroll = homeScroll;
}

export { initHomeScroll };
