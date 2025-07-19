FROM mysql:8.1 AS builder

# Copy SQL files to a temp directory for explicit control
COPY classifieds_import.sql /tmp/sql/
COPY classifieds_restore.sql /tmp/sql/
ENV MYSQL_ROOT_PASSWORD=password
ENV MYSQL_DATABASE=osclass

# Initialize and populate database
RUN mysqld --initialize-insecure --user=mysql --datadir=/var/lib/mysql && \
    mysqld --user=mysql --datadir=/var/lib/mysql --socket=/tmp/mysql.sock & \
    pid="$!" && \
    echo "Waiting for MySQL to start..." && \
    for i in {60..0}; do \
        if mysql -u root --socket=/tmp/mysql.sock -e "SELECT 1" &> /dev/null; then \
            break; \
        fi; \
        sleep 1; \
    done && \
    if [ "$i" = 0 ]; then \
        echo "MySQL failed to start"; \
        exit 1; \
    fi && \
    echo "MySQL started, loading data..." && \
    mysql -u root --socket=/tmp/mysql.sock -e "CREATE DATABASE IF NOT EXISTS osclass;" && \
    mysql -u root --socket=/tmp/mysql.sock osclass < /tmp/sql/classifieds_import.sql && \
    echo "Applying restore script..." && \
    mysql -u root --socket=/tmp/mysql.sock osclass < /tmp/sql/classifieds_restore.sql && \
    echo "Creating remote access for root user..." && \
    mysql -u root --socket=/tmp/mysql.sock -e "CREATE USER 'root'@'%' IDENTIFIED BY 'password'; GRANT ALL PRIVILEGES ON *.* TO 'root'@'%'; FLUSH PRIVILEGES;" && \
    echo "Data loaded, shutting down MySQL..." && \
    mysqladmin -u root --socket=/tmp/mysql.sock shutdown && \
    wait "$pid"

FROM mysql:8.1
# Copy the golden state to a separate directory
COPY --from=builder /var/lib/mysql /var/lib/mysql-golden
# Also copy it to the working directory for first start
COPY --from=builder /var/lib/mysql /var/lib/mysql

ENV MYSQL_ROOT_PASSWORD=password
ENV MYSQL_DATABASE=osclass

# Add custom entrypoint that resets data on every start
COPY custom-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/custom-entrypoint.sh

ENTRYPOINT ["custom-entrypoint.sh"]
CMD ["mysqld"]

# Add healthcheck to ensure MySQL is ready
HEALTHCHECK --interval=1s --timeout=3s --start-period=10s --retries=30 \
  CMD mysqladmin ping -h localhost || exit 1