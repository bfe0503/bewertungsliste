-- Schema for "bewertung" app (MariaDB 10.x)
-- All identifiers and comments are in English.

-- Create DB if not exists and use it
CREATE DATABASE IF NOT EXISTS bewertung
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE bewertung;

-- Users
DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email          VARCHAR(190) NOT NULL,
  password_hash  VARCHAR(255) NOT NULL,
  display_name   VARCHAR(80) NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lists (owned by a user)
DROP TABLE IF EXISTS lists;
CREATE TABLE lists (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NOT NULL,
  title        VARCHAR(150) NOT NULL,
  description  TEXT NULL,
  visibility   ENUM('public','private') NOT NULL DEFAULT 'public',
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_lists_user (user_id),
  CONSTRAINT fk_lists_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Items within a list
DROP TABLE IF EXISTS list_items;
CREATE TABLE list_items (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  list_id      INT UNSIGNED NOT NULL,
  name         VARCHAR(150) NOT NULL,
  description  TEXT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_items_list (list_id),
  CONSTRAINT fk_items_list
    FOREIGN KEY (list_id) REFERENCES lists(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ratings per user & item (1..5) + optional comment
DROP TABLE IF EXISTS ratings;
CREATE TABLE ratings (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  item_id    INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  score      TINYINT UNSIGNED NOT NULL,      -- app enforces 1..5
  comment    TEXT NULL,                       -- optional free text
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY uq_rating_user_item (item_id, user_id),
  KEY idx_ratings_item (item_id),
  KEY idx_ratings_user (user_id),

  CONSTRAINT fk_ratings_item
    FOREIGN KEY (item_id) REFERENCES list_items(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_ratings_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
