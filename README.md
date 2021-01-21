# room-docker

Chat room using custom Http+Websocket server (in a docker container)

# Usage

## Full rebuild

run `docker-compose down && docker-compose build app && docker-compose up`

## Partial update:

run `docker-compose build app && docker-compose up`

this preserves values in the database (assuming `docker-compose.yml/services/database` stays the same)
