CREATE TABLE seq(
	id INTEGER NOT NULL
);
INSERT INTO seq(id) VALUES(1);


CREATE TABLE dsn(
	id VARCHAR(128),
	name VARCHAR(128) NOT NULL,
	value VARCHAR(128)
);
CREATE UNIQUE INDEX u_dsn_id ON dsn(id);

CREATE TABLE item(
	id INTEGER NOT NULL,
	name VARCHAR(128) NOT NULL,
	sql TEXT NOT NULL,
	dsn_id INTEGER NOT NULL,
	created INTEGER NOT NULL,
	modified INTEGER NOT NULL
);
CREATE UNIQUE INDEX u_item_id ON item(id);
