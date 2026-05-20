#!/bin/bash
#
# Local (no-Docker) launcher for Avinash-EYE
#
# Prereqs on host:
#   - Valet (site linked as avinash-eye.test)
#   - postgresql@16 with pgvector (brew services start postgresql@16)
#   - ollama (brew services start ollama)
#   - node, php 8.4, composer
#   - tesseract (optional, `brew install tesseract` for OCR)
#
# Starts in foreground tabs (tmux-style via pids):
#   - Python AI on :8000
#   - Node image processor on :3000
#   - Laravel queue worker
#   - Laravel scheduler loop
#
# Laravel web is served by Valet at http://avinash-eye.test
#
set -e

ROOT="$(cd "$(dirname "$0")" && pwd)"
PIDS_DIR="$ROOT/storage/pids"
LOGS_DIR="$ROOT/storage/logs/local"
mkdir -p "$PIDS_DIR" "$LOGS_DIR"

PG_BIN="/opt/homebrew/opt/postgresql@16/bin"
PATH="$PG_BIN:$PATH"
export PATH

start_service() {
    local name="$1"
    local cmd="$2"
    local pidfile="$PIDS_DIR/$name.pid"
    local logfile="$LOGS_DIR/$name.log"

    if [ -f "$pidfile" ] && kill -0 "$(cat "$pidfile")" 2>/dev/null; then
        echo "[$name] already running (pid $(cat "$pidfile"))"
        return
    fi

    echo "[$name] starting → $logfile"
    bash -c "$cmd" > "$logfile" 2>&1 &
    echo $! > "$pidfile"
}

stop_services() {
    for pidfile in "$PIDS_DIR"/*.pid; do
        [ -f "$pidfile" ] || continue
        local name; name=$(basename "$pidfile" .pid)
        local pid; pid=$(cat "$pidfile")
        if kill -0 "$pid" 2>/dev/null; then
            echo "[$name] stopping (pid $pid)"
            kill "$pid" 2>/dev/null || true
            sleep 0.3
            kill -9 "$pid" 2>/dev/null || true
        fi
        rm -f "$pidfile"
    done
}

status_services() {
    for pidfile in "$PIDS_DIR"/*.pid; do
        [ -f "$pidfile" ] || continue
        local name; name=$(basename "$pidfile" .pid)
        local pid; pid=$(cat "$pidfile")
        if kill -0 "$pid" 2>/dev/null; then
            echo "[$name] running (pid $pid)"
        else
            echo "[$name] dead (stale pidfile)"
            rm -f "$pidfile"
        fi
    done
}

case "${1:-start}" in
    start)
        cd "$ROOT"

        # Sanity: pg up?
        if ! pg_isready -h 127.0.0.1 -p 5432 > /dev/null 2>&1; then
            echo "⚠️  postgres not running — brew services start postgresql@16"
            exit 1
        fi

        # Sanity: ollama up?
        if ! curl -sf http://127.0.0.1:11434/api/tags > /dev/null 2>&1; then
            echo "⚠️  ollama not reachable — brew services start ollama"
        fi

        # Python AI (main.py = slim, main_multimedia.py = full with face-recog/whisper/paddleocr)
        PY_ENTRY="${PY_ENTRY:-main:app}"
        if [ -d "$ROOT/python-ai/.venv" ]; then
            start_service "python-ai" \
                "cd '$ROOT/python-ai' && . .venv/bin/activate && OLLAMA_HOST=http://127.0.0.1:11434 uvicorn $PY_ENTRY --host 127.0.0.1 --port 8000 --workers 1"
        else
            echo "⚠️  python-ai/.venv missing — run: cd python-ai && python3.11 -m venv .venv && .venv/bin/pip install -r requirements-local.txt"
        fi

        # Node image processor
        start_service "node-processor" \
            "cd '$ROOT/node-image-processor' && node server.js"

        # Queue worker (handles all queues in priority order)
        start_service "queue-worker" \
            "cd '$ROOT' && php artisan queue:work --queue=image-processing,video-processing,document-processing,audio-processing,batch-processing,default --tries=3 --timeout=300 --sleep=3 --max-jobs=100"

        # Scheduler
        start_service "scheduler" \
            "cd '$ROOT' && while true; do php artisan schedule:run >> '$LOGS_DIR/scheduler.log' 2>&1; sleep 60; done"

        echo ""
        echo "✅ Services started. Web: http://avinash-eye.test"
        echo "   logs: $LOGS_DIR"
        echo "   stop: $0 stop"
        ;;
    stop)
        stop_services
        ;;
    restart)
        stop_services
        sleep 1
        exec "$0" start
        ;;
    status)
        status_services
        ;;
    *)
        echo "usage: $0 {start|stop|restart|status}"
        exit 1
        ;;
esac
