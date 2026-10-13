#!/usr/bin/env bash
set -euo pipefail

mysql --protocol=socket -uroot -p"${MYSQL_ROOT_PASSWORD}" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS identity_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE DATABASE IF NOT EXISTS social_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE DATABASE IF NOT EXISTS learning_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE DATABASE IF NOT EXISTS notification_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE DATABASE IF NOT EXISTS ai_knowledge_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

    CREATE USER IF NOT EXISTS 'app'@'%' IDENTIFIED BY '${MYSQL_APP_PASSWORD}';
    GRANT ALL PRIVILEGES ON identity_db.* TO 'app'@'%';
    GRANT ALL PRIVILEGES ON social_db.* TO 'app'@'%';
    GRANT ALL PRIVILEGES ON learning_db.* TO 'app'@'%';
    GRANT ALL PRIVILEGES ON notification_db.* TO 'app'@'%';
    GRANT ALL PRIVILEGES ON ai_knowledge_db.* TO 'app'@'%';
    FLUSH PRIVILEGES;
EOSQL
