# Use official PostgreSQL image
FROM postgres:13

# Set environment variables (override with docker-compose if needed)
ENV POSTGRES_USER=postgres
ENV POSTGRES_PASSWORD=root
ENV POSTGRES_DB=labormanual

# Copy custom initialization scripts, if any
COPY init.sql /docker-entrypoint-initdb.d/

# Expose PostgreSQL default port
EXPOSE 5432
