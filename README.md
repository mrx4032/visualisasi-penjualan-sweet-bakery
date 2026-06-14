# Visualisasi Penjualan Sweet Bakery

Quick Docker instructions to run the PHP app locally.

Run with Docker Compose:

```bash
docker compose up --build
```

Open http://localhost:8080

Database credentials (docker-compose):
- host: `db`
- port: `3306`
- database: `sweet_bakery`
- user: `user`
- password: `password`

Edit `koneksi.php` to match these credentials or use environment variables.
