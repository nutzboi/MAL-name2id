-- CREATE DATABASE `stalker-bcnf`;
-- CONNECT `stalker-bcnf`;

CREATE TABLE records(
	id INT,
	first_date BIGINT,
	last_date BIGINT,
	username VARCHAR(17),
	PRIMARY KEY (id, first_date));
    
CREATE INDEX username_idx ON records(username);
