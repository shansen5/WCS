BEGIN;

INSERT INTO users VALUES ( 1, 'Steve', 'Hansen', 'shansen', 'steve123', 1, date('now'), time('now'), NULL, NULL);
INSERT INTO users VALUES ( 2, 'Mark', 'Conover', 'mconover', 'mark123', 0, NULL, NULL, NULL, NULL);
INSERT INTO users VALUES ( 3, 'Burke', 'Hovde', 'bhovde', 'steve123', 2, date('now'), time('now'), NULL, NULL);

COMMIT;
