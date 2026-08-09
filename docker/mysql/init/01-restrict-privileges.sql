-- docker/mysql/init/01-restrict-privileges.sql
--
-- The official MariaDB image auto-creates the app's DB user (from
-- MYSQL_USER/MYSQL_PASSWORD env vars) with ALL PRIVILEGES on the target
-- database -- already meaningfully better than using root, but still more
-- than the app actually needs. This script runs immediately after that
-- auto-creation (docker-entrypoint-initdb.d scripts execute in filename
-- order) and tightens the grant to exactly what the application code
-- performs: SELECT / INSERT / UPDATE / DELETE. No CREATE, ALTER, DROP,
-- GRANT, or FILE privilege -- so even a successful SQL injection (should
-- one ever slip through the prepared-statement fixes) or a compromised
-- app container cannot alter the schema, create new tables, or read/write
-- arbitrary files via INTO OUTFILE / LOAD_FILE.
--
-- NOTE: the username here must match DB_USER in your .env file. The
-- default in .env.example is 'ethiomf_app' -- if you change DB_USER,
-- update this file to match (SQL init scripts are not env-substituted).

REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'ethiomf_app'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON `ethio_microfinance`.* TO 'ethiomf_app'@'%';
FLUSH PRIVILEGES;
