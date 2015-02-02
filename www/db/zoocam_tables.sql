BEGIN;

CREATE TABLE cameras (
id INT PRIMARY KEY NOT NULL,
type_id INT,
name TEXT );

CREATE TABLE media_types (
id INT PRIMARY KEY NOT NULL,
code INT,
name TEXT );

CREATE TABLE camera_types (
id INT PRIMARY KEY NOT NULL,
code INT,
name TEXT );

CREATE TABLE configs (
id INT PRIMARY KEY NOT NULL,
camera_id INT NOT NULL );

CREATE TABLE config_params (
id INT PRIMARY KEY NOT NULL,
camera_id INT NOT NULL,
param_id INT NOT NULL );

CREATE TABLE parameters (
id INT PRIMARY KEY NOT NULL,
name TEXT,
value TEXT );

CREATE TABLE param_options (
id INT PRIMARY KEY NOT NULL,
param_id INT NOT NULL,
name TEXT,
value TEXT );

CREATE TABLE users (
id INT PRIMARY KEY NOT NULL,
first_name TEXT NOT NULL,
last_name TEXT NOT NULL,
username TEXT NOT NULL,
password TEXT NOT NULL,
permissions INT NOT NULL,
created_date DATE,
created_time TIME,
last_login_date DATE,
last_login_time TIME );

CREATE TABLE media (
id INT PRIMARY KEY NOT NULL,
type_id INT NOT NULL,
camera_id INT NOT NULL );

COMMIT;
