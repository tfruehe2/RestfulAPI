source "../SECRETS/db_secrets.sh"
source "./initDB.sh"

mysql -u "$DB_USERNAME" -p${DB_PASSWORD} << EOF

DROP DATABASE $DB_NAME;
CREATE DATABASE $DB_NAME;
use '${DB_NAME}';

CREATE TABLE users (
    id int(11) AUTO_INCREMENT,
    username varchar(30) NOT NULL,
    email varchar(255) NOT NULL,
    first_name varchar(30),
    last_name varchar(30),
    hashed_password varchar(100) NOT NULL,
    date_joined datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    profile_picture varchar(255),
    group_id int(3) NOT NULL DEFAULT 1,
    CONSTRAINT uc_username UNIQUE (username),
    CONSTRAINT uc_email UNIQUE (email),
    PRIMARY KEY (id)
);

CREATE TABLE feeds (
    id int(3) AUTO_INCREMENT,
    title varchar(140) NOT NULL,
    description varchar(255) NOT NULL,
    slug varchar(140) NOT NULL,
    date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_active boolean NOT NULL DEFAULT FALSE,
    CONSTRAINT uc_title UNIQUE (title),
    PRIMARY KEY (id)
);

CREATE TABLE permission_groups (
    id int(3) NOT NULL AUTO_INCREMENT,
    name varchar(30) NOT NULL,
    permissions text NOT NULL,
    PRIMARY KEY (id)
);

CREATE TABLE posts (
    id int(9) NOT NULL AUTO_INCREMENT,
    title varchar(140) NOT NULL,
    description text NOT NULL,
    feed_id int(4) NOT NULL DEFAULT 1,
    slug varchar(140),
    date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    author_id int(9) NOT NULL DEFAULT 1,
    image varchar(255),
    views int(9) NOT NULL DEFAULT 0,
    post_type enum('text','song','playlist') NOT NULL,
    url varchar(255),
    CONSTRAINT uc_title UNIQUE (title),
    PRIMARY KEY (id),
    FOREIGN KEY (author_id) REFERENCES users(id)
);

CREATE TABLE songs (
  id int(9) NOT NULL AUTO_INCREMENT,
  title varchar(140) NOT NULL,
  artist_id int(9),
  artist_name varchar(50) NOT NULL,
  featured_artist varchar(50),
  lyrics text NOT NULL,
  video_type enum('soundcloud', 'youtube', 'vimeo'),
  video_url varchar(140) NOT NULL,
  video_id varchar(140) NOT NULL,
  post_id int(9) NOT NULL,
  album_id int(9),
  track_index int(3),
  CONSTRAINT uc_title UNIQUE (title, artist_name),
  CONSTRAINT uc_video_url UNIQUE (video_url),
  PRIMARY KEY (id),
  FOREIGN KEY (post_id) REFERENCES posts(id)
);

CREATE TABLE tags (
  id int(9) NOT NULL AUTO_INCREMENT,
  name varchar(40) NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE genres (
  id int(9) NOT NULL AUTO_INCREMENT,
  name varchar(40) NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE post_tags (
  id int(9) NOT NULL AUTO_INCREMENT,
  post_id int(9) NOT NULL,
  tag_id int(9) NOT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (post_id) REFERENCES posts(id),
  FOREIGN KEY (tag_id) REFERENCES tags(id)
);

CREATE TABLE song_genres (
  id int(9) NOT NULL AUTO_INCREMENT,
  song_id int(9) NOT NULL,
  genre_id int(9) NOT NULL,
  PRIMARY KEY (id),
  CONSTRAINT uc_song_genre UNIQUE (song_id, genre_id),
  FOREIGN KEY (song_id) REFERENCES songs(id),
  FOREIGN KEY (genre_id) REFERENCES genres(id)
);

CREATE TABLE playlists (
  id int(9) NOT NULL AUTO_INCREMENT,
  name varchar(140) NOT NULL,
  description varchar(140) NOT NULL,
  date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  user_id int(9) NOT NULL,
  is_featured boolean NOT NULL DEFAULT FALSE,
  is_private boolean NOT NULL DEFAULT TRUE,
  post_id int(9) NOT NULL,
  song_count int(3) NOT NULL DEFAULT 0,
  CONSTRAINT uc_users_title UNIQUE (name, user_id),
  PRIMARY KEY (id),
  FOREIGN KEY (post_id) REFERENCES posts(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE playlist_songs (
  id int(9) NOT NULL AUTO_INCREMENT,
  song_id int(9) NOT NULL,
  playlist_id int(9) NOT NULL,
  position int(3) NOT NULL,
  PRIMARY KEY (id),
  CONSTRAINT uc_playlist_song UNIQUE (playlist_id, song_id),
  CONSTRAINT uc_playlist_position UNIQUE (playlist_id, position),
  FOREIGN KEY (song_id) REFERENCES songs(id),
  FOREIGN KEY (playlist_id) REFERENCES playlists(id)
);

CREATE TABLE artists (
  id int(9) NOT NULL AUTO_INCREMENT,
  name varchar(140) NOT NULL,
  full_name varchar(140),
  year_formed int(4) NOT NULL,
  year_disbanded int(4),
  DOB date,
  birthplace varchar(140),
  origin varchar(140),
  website varchar(140),
  image varchar(255),
  PRIMARY KEY (id)
);

CREATE TABLE artist_genres (
  id int(9) NOT NULL AUTO_INCREMENT,
  artist_id int(9) NOT NULL,
  genre_id int(9) NOT NULL,
  PRIMARY KEY (id),
  CONSTRAINT uc_artist_genre UNIQUE (artist_id, genre_id),
  FOREIGN KEY (artist_id) REFERENCES artists(id),
  FOREIGN KEY (genre_id) REFERENCES genres(id)
);

CREATE TABLE albums (
  id int(9) NOT NULL AUTO_INCREMENT,
  title varchar(140) NOT NULL,
  artist_name varchar(140),
  artist_id int(9) NOT NULL,
  year_released int(4),
  image varchar(255),
  PRIMARY KEY (id),
  FOREIGN KEY (artist_id) REFERENCES artists(id)
);

CREATE TABLE comments (
  id int(9) NOT NULL AUTO_INCREMENT,
  user_id int(9),
  post_id int(9) NOT NULL,
  date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  body varchar(255) NOT NULL,
  reply_to int(9),
  PRIMARY KEY (id),
  FOREIGN KEY (post_id) REFERENCES posts(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE likes (
  id int(9) NOT NULL AUTO_INCREMENT,
  user_id int(9) NOT NULL,
  post_id int(9) NOT NULL,
  date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (post_id) REFERENCES posts(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE sessions (
  id int(9) NOT NULL AUTO_INCREMENT,
  token varchar(140) NOT NULL,
  user_id int(9) NOT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

ALTER TABLE users
    ADD CONSTRAINT group_fk FOREIGN KEY
    (group_id) REFERENCES permission_groups(id);

ALTER TABLE songs
    ADD CONSTRAINT artist_fk FOREIGN KEY (artist_id) REFERENCES artists(id);

ALTER TABLE songs
    ADD CONSTRAINT album_fk FOREIGN KEY (album_id) REFERENCES albums(id);

$permissionGroupData

$userData

EOF
