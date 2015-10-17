
CREATE TABLE my_table (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(30),
    comment VARCHAR(140)
);

CREATE TABLE linked_table (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    mytable INTEGER NOT NULL REFERENCES my_table(id),
    points INTEGER NOT NULL
);

CREATE TABLE bitflag (
    status INTEGER NOT NULL DEFAULT NULL
);

CREATE TABLE multijoin (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    mytable INTEGER NOT NULL REFERENCES my_table(id),
    linkedtable INTEGER REFERENCES linked_table(id)
);

