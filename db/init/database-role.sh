#!/bin/sh
set -eu

# Role aplikasi dan service dibuat di sini supaya database baru langsung siap dipakai.
psql_app() {
    psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" "$@"
}

if ! psql_app -tAc "SELECT 1 FROM pg_roles WHERE rolname = 'simrs_app'" | grep -q 1; then
    psql_app -c "CREATE ROLE simrs_app LOGIN PASSWORD '${DB_APP_PASSWORD}'"
fi

if ! psql_app -tAc "SELECT 1 FROM pg_roles WHERE rolname = 'simrs_svc'" | grep -q 1; then
    psql_app -c "CREATE ROLE simrs_svc LOGIN PASSWORD '${DB_SVC_PASSWORD}'"
fi

psql_app <<SQL
CREATE SCHEMA IF NOT EXISTS simrs AUTHORIZATION simrs_app;
GRANT CONNECT ON DATABASE ${POSTGRES_DB} TO simrs_app, simrs_svc;
GRANT USAGE ON SCHEMA simrs TO simrs_svc;
GRANT USAGE ON SCHEMA public TO simrs_app;
ALTER ROLE simrs_app IN DATABASE ${POSTGRES_DB} SET search_path = simrs, public;
ALTER ROLE simrs_svc IN DATABASE ${POSTGRES_DB} SET search_path = simrs, public;
SQL
