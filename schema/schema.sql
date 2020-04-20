/*CREATE schema IF NOT EXISTS ratingSchema;*/
CREATE database IF NOT EXISTS rating;

CREATE TABLE IF NOT EXISTS `Url` (
    `id` INT NOT NULL AUTO_INCREMET PRIMARY KEY,
    `url` VARCHAR(255) NOT NULL
                                 );

CREATE TABLE IF NOT EXISTS `Comentario` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `url_id` VARCHAR(255) NOT NULL
    `score` INT NOT NULL
    `comment` VARCHAR(255)
                                        );