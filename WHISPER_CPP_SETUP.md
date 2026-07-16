# PolyLango Firefox microphone — free whisper.cpp setup

This version does **not** use OpenAI’s hosted API and does not require a paid API key.

Chrome and Edge still use their built-in browser speech recognition when available. Firefox records audio with `MediaRecorder`; `transcribe.php` forwards that recording to your own local `whisper-server`.

## Requirements

- Linux server with shell access
- PHP with cURL enabled
- HTTPS on the PolyLango website
- `ffmpeg`
- Enough RAM for the chosen Whisper model

Approximate model choices:

- `tiny`: fastest, least accurate
- `base`: good default for a small server
- `small`: better accuracy, heavier
- `medium`: much heavier
- Use multilingual models, not `.en` models, because PolyLango supports many languages

## Automatic Ubuntu/Debian installation

Upload the PolyLango folder, then run:

```bash
cd /path/to/polylango
chmod +x install-whisper-cpp-ubuntu.sh
./install-whisper-cpp-ubuntu.sh base
```

Start the server:

```bash
~/whisper.cpp/run-polylango-whisper.sh
```

Leave that terminal open for the first test.

Check it:

```bash
curl http://127.0.0.1:8080/
```

Open this in the browser:

```text
https://YOUR-SITE/polylango/whisper-health.php
```

It should return `"ok": true`.

## Run whisper.cpp automatically with systemd

Copy the included example:

```bash
sudo cp polylango-whisper.service.example /etc/systemd/system/polylango-whisper.service
sudo nano /etc/systemd/system/polylango-whisper.service
```

Replace every `YOUR_LINUX_USERNAME` with the real Linux account.

Then run:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now polylango-whisper
sudo systemctl status polylango-whisper
```

View logs:

```bash
journalctl -u polylango-whisper -f
```

## Different port or machine

Copy:

```bash
cp config.example.php config.php
```

Edit `config.php`:

```php
<?php
return [
    'whisper_url' => 'http://127.0.0.1:8080/inference',
    'timeout_seconds' => 90,
];
```

Keep whisper.cpp bound to `127.0.0.1` when it is on the same server. The public browser never talks directly to whisper.cpp; it talks to `transcribe.php`.

## Shared hosting warning

Ordinary shared hosting usually cannot run a persistent C++ server. You need a VPS, dedicated server, home server, or another machine that can keep `whisper-server` running.

If PolyLango is on shared hosting and whisper.cpp is on another private server, change `whisper_url` to that server’s protected address. Do not expose an unrestricted transcription endpoint publicly.

## Troubleshooting

### “Could not reach whisper.cpp”

Run:

```bash
sudo systemctl status polylango-whisper
curl http://127.0.0.1:8080/
```

### Firefox records but transcription fails

Confirm the build used FFmpeg conversion and the server was started with `--convert`. Firefox commonly records WebM or Ogg, while whisper.cpp internally needs decoded audio.

### Wrong language recognition

PolyLango sends the selected course locale to `transcribe.php`, which forwards its two-letter language code to whisper.cpp.

### Slow transcription

Use the `tiny` or `base` model, increase CPU resources, or use a GPU-enabled whisper.cpp build.

## Security

- No hosted OpenAI API key is used.
- `whisper-server` should listen on `127.0.0.1`, not the public internet.
- Keep the website on HTTPS so browsers permit microphone recording.
- Limit upload size and PHP request rate if the site is public.
