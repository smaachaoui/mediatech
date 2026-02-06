CREATE DATABASE IF NOT EXISTS mediatech_test
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE mediatech_test;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS rating;
DROP TABLE IF EXISTS comment;
DROP TABLE IF EXISTS wishlist;
DROP TABLE IF EXISTS collection_movie;
DROP TABLE IF EXISTS collection_book;
DROP TABLE IF EXISTS collection;
DROP TABLE IF EXISTS movie;
DROP TABLE IF EXISTS book;
DROP TABLE IF EXISTS contact_message;
DROP TABLE IF EXISTS login_attempt;
DROP TABLE IF EXISTS genre;
DROP TABLE IF EXISTS user;

CREATE TABLE user (
  id INT NOT NULL AUTO_INCREMENT,
  email VARCHAR(180) NOT NULL,
  roles JSON NOT NULL,
  password VARCHAR(255) NOT NULL,
  pseudo VARCHAR(50) NOT NULL,
  profile_picture VARCHAR(255) DEFAULT NULL,
  biography TEXT DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_user_email (email),
  UNIQUE KEY uniq_user_pseudo (pseudo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE genre (
  id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  type ENUM('book','movie') NOT NULL,
  PRIMARY KEY (id),
  KEY idx_genre_type (type),
  KEY idx_genre_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE book (
  id INT NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  cover_image VARCHAR(500) DEFAULT NULL,
  author VARCHAR(255) DEFAULT NULL,
  publisher VARCHAR(255) DEFAULT NULL,
  translator VARCHAR(255) DEFAULT NULL,
  publication_date DATE DEFAULT NULL,
  genre VARCHAR(100) DEFAULT NULL,
  page_count INT DEFAULT NULL,
  isbn VARCHAR(20) DEFAULT NULL,
  synopsis TEXT DEFAULT NULL,
  google_books_id VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_book_google_books_id (google_books_id),
  KEY idx_book_title (title),
  KEY idx_book_isbn (isbn)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE movie (
  id INT NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  poster VARCHAR(500) DEFAULT NULL,
  director VARCHAR(255) DEFAULT NULL,
  genre VARCHAR(100) DEFAULT NULL,
  release_date DATE DEFAULT NULL,
  synopsis TEXT DEFAULT NULL,
  tmdb_id INT DEFAULT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_movie_tmdb_id (tmdb_id),
  KEY idx_movie_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE collection (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  description TEXT DEFAULT NULL,
  type ENUM('books','movies') NOT NULL,
  visibility ENUM('public','private') NOT NULL DEFAULT 'private',
  is_published TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  published_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_collection_user (user_id),
  KEY idx_collection_visibility (visibility),
  CONSTRAINT fk_collection_user
    FOREIGN KEY (user_id) REFERENCES user(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE collection_book (
  id INT NOT NULL AUTO_INCREMENT,
  collection_id INT NOT NULL,
  book_id INT NOT NULL,
  added_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_collection_book (collection_id, book_id),
  KEY idx_collection_book_collection (collection_id),
  KEY idx_collection_book_book (book_id),
  CONSTRAINT fk_collection_book_collection
    FOREIGN KEY (collection_id) REFERENCES collection(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_collection_book_book
    FOREIGN KEY (book_id) REFERENCES book(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE collection_movie (
  id INT NOT NULL AUTO_INCREMENT,
  collection_id INT NOT NULL,
  movie_id INT NOT NULL,
  added_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_collection_movie (collection_id, movie_id),
  KEY idx_collection_movie_collection (collection_id),
  KEY idx_collection_movie_movie (movie_id),
  CONSTRAINT fk_collection_movie_collection
    FOREIGN KEY (collection_id) REFERENCES collection(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_collection_movie_movie
    FOREIGN KEY (movie_id) REFERENCES movie(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wishlist (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  book_id INT DEFAULT NULL,
  movie_id INT DEFAULT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_wishlist_user (user_id),
  KEY idx_wishlist_book (book_id),
  KEY idx_wishlist_movie (movie_id),
  CONSTRAINT fk_wishlist_user
    FOREIGN KEY (user_id) REFERENCES user(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_wishlist_book
    FOREIGN KEY (book_id) REFERENCES book(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_wishlist_movie
    FOREIGN KEY (movie_id) REFERENCES movie(id)
    ON DELETE CASCADE,
  CONSTRAINT chk_wishlist_one_media
    CHECK (
      (book_id IS NOT NULL AND movie_id IS NULL)
      OR (book_id IS NULL AND movie_id IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE comment (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT DEFAULT NULL,
  collection_id INT NOT NULL,
  content TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_comment_user (user_id),
  KEY idx_comment_collection (collection_id),
  CONSTRAINT fk_comment_user
    FOREIGN KEY (user_id) REFERENCES user(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_comment_collection
    FOREIGN KEY (collection_id) REFERENCES collection(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rating (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  collection_id INT NOT NULL,
  value SMALLINT NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_rating_user_collection (user_id, collection_id),
  KEY idx_rating_user (user_id),
  KEY idx_rating_collection (collection_id),
  CONSTRAINT fk_rating_user
    FOREIGN KEY (user_id) REFERENCES user(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_rating_collection
    FOREIGN KEY (collection_id) REFERENCES collection(id)
    ON DELETE CASCADE,
  CONSTRAINT chk_rating_value
    CHECK (value BETWEEN 0 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contact_message (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT DEFAULT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(180) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_contact_message_user (user_id),
  CONSTRAINT fk_contact_message_user
    FOREIGN KEY (user_id) REFERENCES user(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempt (
  id INT NOT NULL AUTO_INCREMENT,
  email VARCHAR(180) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  success TINYINT(1) NOT NULL,
  attempted_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_login_attempt_email (email),
  KEY idx_login_attempt_ip (ip_address),
  KEY idx_login_attempt_attempted_at (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

