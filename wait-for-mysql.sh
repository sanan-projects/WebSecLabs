#!/bin/bash

# Birinci MySQL server üçün yoxlama
until mysql -h "db" -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" -e 'SELECT 1;' >/dev/null 2>&1; do
  >&2 echo "Əsas MySQL serveri gözlənilir..."
  sleep 1
done

>&2 echo "Əsas MySQL serveri hazırdır!"

# Admin MySQL server üçün yoxlama
until mysql -h "admin_db" -u "$ADMIN_MYSQL_USER" -p"$ADMIN_MYSQL_PASSWORD" -e 'SELECT 1;' >/dev/null 2>&1; do
  >&2 echo "Admin MySQL serveri gözlənilir..."
  sleep 1
done

>&2 echo "Admin MySQL serveri hazırdır!"

# Əgər əlavə əmrlər varsa icra et
if [ $# -gt 0 ]; then
    exec "$@"
fi 