#!/usr/bin/env bash
set -euo pipefail

INSTALL_DIR="${HOME}/whisper.cpp"
MODEL="${1:-base}"
PORT="${WHISPER_PORT:-8080}"

echo "Installing build tools and ffmpeg..."
sudo apt update
sudo apt install -y git cmake build-essential ffmpeg curl

if [ ! -d "$INSTALL_DIR/.git" ]; then
  git clone https://github.com/ggml-org/whisper.cpp.git "$INSTALL_DIR"
else
  git -C "$INSTALL_DIR" pull --ff-only
fi

cmake -S "$INSTALL_DIR" -B "$INSTALL_DIR/build" -DWHISPER_FFMPEG=yes
cmake --build "$INSTALL_DIR/build" --config Release -j"$(nproc)"

mkdir -p "$INSTALL_DIR/models"
"$INSTALL_DIR/models/download-ggml-model.sh" "$MODEL"

cat > "$INSTALL_DIR/run-polylango-whisper.sh" <<EOF
#!/usr/bin/env bash
exec "$INSTALL_DIR/build/bin/whisper-server" \
  --host 127.0.0.1 \
  --port "$PORT" \
  --model "$INSTALL_DIR/models/ggml-$MODEL.bin" \
  --convert
EOF

chmod +x "$INSTALL_DIR/run-polylango-whisper.sh"

echo
echo "Installed."
echo "Start it with:"
echo "  $INSTALL_DIR/run-polylango-whisper.sh"
echo
echo "Then test:"
echo "  curl http://127.0.0.1:$PORT/"
