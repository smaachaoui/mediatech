/**
 * MODULE GENRE SELECTOR - Sélection dynamique des genres
 * Charge les genres selon le type de média sélectionné (book/movie)
 */

export function initGenreSelector() {
  const mediaTypeSelect = document.getElementById('createCollectionMediaType');
  const genreSelect = document.getElementById('createCollectionGenre');

  if (!mediaTypeSelect || !genreSelect) {
    // Pas sur la page avec le sélecteur de genre
    return;
  }

  /**
   * Récupération des genres depuis les attributs data
   * Les genres sont injectés dans le HTML par le backend
   */
  const genresData = document.getElementById('genresData');
  if (!genresData) {
    console.warn('Les données de genres sont introuvables');
    return;
  }

  const bookGenres = JSON.parse(genresData.dataset.bookGenres || '[]');
  const movieGenres = JSON.parse(genresData.dataset.movieGenres || '[]');

  /**
   * Met à jour la liste des genres selon le type de média
   */
  function updateGenreOptions() {
    const mediaType = mediaTypeSelect.value;
    
    // Réinitialise le select
    genreSelect.innerHTML = '<option value="">Sélectionnez un genre (optionnel)</option>';
    genreSelect.disabled = false;

    let genres = [];
    if (mediaType === 'book') {
      genres = bookGenres;
    } else if (mediaType === 'movie') {
      genres = movieGenres;
    }

    // Remplit le select avec les genres disponibles
    genres.forEach((genre) => {
      const option = document.createElement('option');
      option.value = genre.name;
      option.textContent = genre.name;
      genreSelect.appendChild(option);
    });

    // Si aucun genre disponible
    if (genres.length === 0) {
      genreSelect.innerHTML = '<option value="">Aucun genre disponible</option>';
      genreSelect.disabled = true;
    }
  }

  // Événement sur le changement de type de média
  mediaTypeSelect.addEventListener('change', updateGenreOptions);

  console.log('✓ Module genre-selector initialisé');
}

export default initGenreSelector;