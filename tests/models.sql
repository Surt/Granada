CREATE TABLE manufactor (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT
);

CREATE TABLE owner (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT
);

CREATE TABLE part (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT
);

CREATE TABLE car (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    manufactor_id INTEGER,
    owner_id INTEGER,
    FOREIGN KEY (manufactor_id) REFERENCES manufactor (id),
    FOREIGN KEY (owner_id) REFERENCES owner (id)
);


CREATE TABLE car_part (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    car_id INTEGER,
    part_id INTEGER,
    FOREIGN KEY (car_id) REFERENCES car (id),
    FOREIGN KEY (part_id) REFERENCES part (id)
);

INSERT INTO manufactor(id,name) VALUES (1, 'Manufactor1');
INSERT INTO manufactor(id,name) VALUES (2, 'Manufactor2');

INSERT INTO owner(id,name) VALUES (1, 'Owner1');
INSERT INTO owner(id,name) VALUES (2, 'Owner2');
INSERT INTO owner(id,name) VALUES (3, 'Owner3');
INSERT INTO owner(id,name) VALUES (4, 'Owner4');

INSERT INTO part(id,name) VALUES (1, 'Part1');
INSERT INTO part(id,name) VALUES (2, 'Part2');
INSERT INTO part(id,name) VALUES (3, 'Part3');
INSERT INTO part(id,name) VALUES (4, 'Part4');
INSERT INTO part(id,name) VALUES (5, 'Part5');

INSERT INTO car(id,name,manufactor_id, owner_id) VALUES (1, 'Car1',1,1);
INSERT INTO car(id,name,manufactor_id, owner_id) VALUES (2, 'Car2',1,2);
INSERT INTO car(id,name,manufactor_id, owner_id) VALUES (3, 'Car3',2,3);
INSERT INTO car(id,name,manufactor_id, owner_id) VALUES (4, 'Car4',2,4);

INSERT INTO car_part(id,car_id,part_id) VALUES (1,1,1);
INSERT INTO car_part(id,car_id,part_id) VALUES (2,2,1);
INSERT INTO car_part(id,car_id,part_id) VALUES (3,3,1);
INSERT INTO car_part(id,car_id,part_id) VALUES (4,4,1);

INSERT INTO car_part(id,car_id,part_id) VALUES (5,1,2);
INSERT INTO car_part(id,car_id,part_id) VALUES (6,2,3);
INSERT INTO car_part(id,car_id,part_id) VALUES (7,3,4);
INSERT INTO car_part(id,car_id,part_id) VALUES (8,4,5);
INSERT INTO car_part(id,car_id,part_id) VALUES (9,1,1);
