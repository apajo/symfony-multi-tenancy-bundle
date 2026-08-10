-- The official entrypoint grants MYSQL_USER rights on MYSQL_DATABASE only, but
-- AbstractDatabaseTestCase creates three schemas using the default connection's
-- credentials and then reads two of them over the tenant connection. One user
-- with server-scope rights is the simplest thing that works, and this container
-- is disposable and never holds real data.

CREATE DATABASE IF NOT EXISTS `mtb_default_test` CHARACTER SET utf8mb4;
CREATE DATABASE IF NOT EXISTS `mtb_tenant_test` CHARACTER SET utf8mb4;
CREATE DATABASE IF NOT EXISTS `mtb_tenant_alpha_test` CHARACTER SET utf8mb4;

GRANT ALL PRIVILEGES ON *.* TO 'mtb'@'%';
FLUSH PRIVILEGES;
