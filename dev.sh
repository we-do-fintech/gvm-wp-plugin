#!/usr/bin/env bash
set -euo pipefail

# dev.sh — manage the local WordPress development environment.
#
# The plugin is bind-mounted into the running WordPress container
# (see wordpress-env/docker-compose.yml), so edits are reflected
# immediately after activating the plugin once.
#
#   ./dev.sh up         Start WordPress + MySQL (http://localhost:8080)
#   ./dev.sh down       Stop the containers
#   ./dev.sh logs       Tail WordPress logs
#   ./dev.sh restart    Down + up
#   ./dev.sh zip        Build the installable zip (delegates to build.sh)

COMPOSE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/wordpress-env" && pwd)"

up() {
	(cd "$COMPOSE_DIR" && podman compose up -d)
	echo
	echo "WordPress is starting: http://localhost:8080"
	echo "Plugin is mounted at wp-content/plugins/gvm-wp-plugin"
	echo "Activate it via Plugins -> Installed Plugins."
}

down() {
	(cd "$COMPOSE_DIR" && podman compose down)
}

logs() {
	(cd "$COMPOSE_DIR" && podman compose logs -f wordpress)
}

restart() {
	down
	up
}

zip_() {
	bash "$(dirname "${BASH_SOURCE[0]}")/build.sh" zip
}

case "${1:-up}" in
	up) up ;;
	down) down ;;
	logs) logs ;;
	restart) restart ;;
	zip) zip_ ;;
	*) echo "usage: $0 [up|down|logs|restart|zip]" >&2; exit 1 ;;
esac
