CREATE TABLE store (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT
);

CREATE TABLE buyer (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT
);

CREATE TABLE product (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT
);

CREATE TABLE sale (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    store_id INTEGER,
    buyer_id INTEGER,
    FOREIGN KEY (store_id) REFERENCES store (id),
    FOREIGN KEY (buyer_id) REFERENCES buyer (id)
);


CREATE TABLE sale_product (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sale_id INTEGER,
    product_id INTEGER,
    FOREIGN KEY (sale_id) REFERENCES sale (id),
    FOREIGN KEY (product_id) REFERENCES product (id)
);

INSERT INTO store(id,name) VALUES (1, 'Store #1');
INSERT INTO store(id,name) VALUES (2, 'Store #2');

INSERT INTO buyer(id,name) VALUES (1, 'Jim');
INSERT INTO buyer(id,name) VALUES (2, 'Susan');
INSERT INTO buyer(id,name) VALUES (3, 'Erik');
INSERT INTO buyer(id,name) VALUES (4, 'Tom');

INSERT INTO product(id,name) VALUES (1, 'Apple');
INSERT INTO product(id,name) VALUES (2, 'Bananas');
INSERT INTO product(id,name) VALUES (3, 'Cookies');
INSERT INTO product(id,name) VALUES (4, 'Steak');
INSERT INTO product(id,name) VALUES (5, 'Bread');

INSERT INTO sale(id,name,store_id, buyer_id) VALUES (1, 'Sale 1',1,1);
INSERT INTO sale(id,name,store_id, buyer_id) VALUES (2, 'Sale 2',1,2);
INSERT INTO sale(id,name,store_id, buyer_id) VALUES (3, 'Sale 3',2,3);
INSERT INTO sale(id,name,store_id, buyer_id) VALUES (4, 'Sale 4',2,4);

INSERT INTO sale_product(id,sale_id,product_id) VALUES (1,1,1);
INSERT INTO sale_product(id,sale_id,product_id) VALUES (2,2,1);
INSERT INTO sale_product(id,sale_id,product_id) VALUES (3,3,1);
INSERT INTO sale_product(id,sale_id,product_id) VALUES (4,4,1);

INSERT INTO sale_product(id,sale_id,product_id) VALUES (5,1,2);
INSERT INTO sale_product(id,sale_id,product_id) VALUES (6,2,3);
INSERT INTO sale_product(id,sale_id,product_id) VALUES (7,3,4);
INSERT INTO sale_product(id,sale_id,product_id) VALUES (8,4,5);
INSERT INTO sale_product(id,sale_id,product_id) VALUES (9,1,1);
