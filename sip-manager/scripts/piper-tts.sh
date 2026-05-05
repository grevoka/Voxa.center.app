#!/bin/bash
# AGI script: synthesize text with Piper TTS and play it back to the caller.
#
# Usage from dialplan:
#   AGI(piper-tts.sh, "<text>", <model>, [<speaker_id>])
#
# Examples:
#   AGI(piper-tts.sh, "Bonjour", fr_FR-siwis-medium)
#   AGI(piper-tts.sh, "Bonjour", fr_FR-upmc-medium, 0)   # Jessica
#   AGI(piper-tts.sh, "Bonjour", fr_FR-upmc-medium, 1)   # Pierre

TEXT="$1"
MODEL="${2:-fr_FR-siwis-medium}"
SPEAKER="$3"

PIPER="/opt/piper/piper/piper"
MODELS_DIR="/opt/piper/models"
CACHE_DIR="/var/spool/asterisk/tts_cache"

mkdir -p "$CACHE_DIR"

# Cache key includes text, model and speaker so different combos don't collide.
HASH=$(printf '%s' "${MODEL}:${SPEAKER}:${TEXT}" | md5sum | awk '{print $1}')
PIPER_WAV="${CACHE_DIR}/${HASH}.wav"
ASTERISK_BASE="${CACHE_DIR}/${HASH}"   # Asterisk plays without extension

# Generate only if not cached.
if [ ! -f "${ASTERISK_BASE}.sln" ] && [ ! -f "${ASTERISK_BASE}.wav" ]; then
    SPEAKER_ARG=""
    [ -n "$SPEAKER" ] && SPEAKER_ARG="--speaker $SPEAKER"

    printf '%s' "$TEXT" | "$PIPER" \
        --model "${MODELS_DIR}/${MODEL}.onnx" \
        $SPEAKER_ARG \
        --output_file "$PIPER_WAV" 2>>/var/log/asterisk/piper-tts.log

    # Convert 22050 Hz Piper output → 8 kHz signed-linear mono for Asterisk.
    if [ -f "$PIPER_WAV" ] && command -v sox >/dev/null 2>&1; then
        sox "$PIPER_WAV" -r 8000 -c 1 -b 16 -e signed-integer "${ASTERISK_BASE}.sln" 2>>/var/log/asterisk/piper-tts.log \
            && rm -f "$PIPER_WAV"
    fi
fi

# AGI handshake: read environment variables (key:value lines), terminated by an empty line.
while IFS= read -r line; do
    [ -z "$line" ] || [ "$line" = $'\r' ] && break
done

# Tell Asterisk to play the cached audio (without extension; it auto-picks .sln/.wav).
printf 'STREAM FILE %s ""\n' "$ASTERISK_BASE"
# Drain Asterisk's response so we exit cleanly.
read -t 30 _response || true

exit 0
