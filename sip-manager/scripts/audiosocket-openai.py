#!/usr/bin/env python3
"""
AudioSocket server bridging Asterisk <-> OpenAI Realtime API.
Full-duplex: caller speaks → OpenAI hears immediately, OpenAI responds → caller hears immediately.
No STREAM FILE blocking — true real-time bidirectional conversation.

Usage in dialplan:
  same => n,AudioSocket(uuid,127.0.0.1:9092)

Run as systemd service listening on port 9092.
"""

import asyncio
import json
import base64
import hashlib
import struct
import sys
import time
import os
import websockets
from pathlib import Path
from datetime import datetime

# AudioSocket frame types
AUDIOSOCKET_KIND_UUID = 0x01
AUDIOSOCKET_KIND_AUDIO = 0x10
AUDIOSOCKET_KIND_SILENCE = 0x02
AUDIOSOCKET_KIND_HANGUP = 0x00
AUDIOSOCKET_KIND_ERROR = 0xff

CONTEXT_DIR = Path('/var/www/html/storage/app/private/ai-context')

def get_config():
    """Load config from .env and sip_settings DB."""
    config = {
        'api_key': '',
        'model': 'gpt-4o-mini-realtime-preview-2024-12-17',
        'voice': 'coral',
        'vad_threshold': 0.5,
        'silence_ms': 1000,
        'max_turns': 30,
        'prompt': 'Tu es un assistant telephonique. Reponds en francais.',
    }

    # Read .env
    env_file = Path('/var/www/html/.env')
    if env_file.exists():
        env = {}
        for line in env_file.read_text().splitlines():
            if '=' in line and not line.startswith('#'):
                k, v = line.split('=', 1)
                env[k.strip()] = v.strip().strip('"')
        config['api_key'] = env.get('OPENAI_API_KEY', '')
        config['db_host'] = env.get('DB_HOST', '127.0.0.1')
        config['db_user'] = env.get('DB_USERNAME', 'root')
        config['db_pass'] = env.get('DB_PASSWORD', '')
        config['db_name'] = env.get('DB_DATABASE', 'sip_manager')

    # Read DB settings
    try:
        import mysql.connector
        conn = mysql.connector.connect(
            host=config['db_host'], user=config['db_user'],
            password=config['db_pass'], database=config['db_name']
        )
        cursor = conn.cursor()
        cursor.execute("SELECT `key`, `value` FROM sip_settings WHERE `group` = 'ai'")
        for k, v in cursor.fetchall():
            if k == 'openai_model': config['model'] = v
            elif k == 'openai_voice': config['voice'] = v
            elif k == 'openai_vad_threshold': config['vad_threshold'] = float(v)
            elif k == 'openai_silence_ms': config['silence_ms'] = int(v)
            elif k == 'openai_max_turns': config['max_turns'] = int(v)
        cursor.close()
        conn.close()
    except Exception as e:
        print(f'[WARN] DB settings: {e}', file=sys.stderr)

    return config

def load_context(rag_folder=''):
    """Load RAG context files from general + specific folder."""
    parts = []
    # Always load general (root) docs
    if CONTEXT_DIR.exists():
        for f in sorted(CONTEXT_DIR.glob('*.txt')) + sorted(CONTEXT_DIR.glob('*.md')):
            parts.append(f'--- {f.stem} ---\n{f.read_text(encoding="utf-8", errors="ignore").strip()}')
    # Load folder-specific docs
    if rag_folder:
        folder_path = CONTEXT_DIR / rag_folder
        if folder_path.exists():
            for f in sorted(folder_path.glob('*.txt')) + sorted(folder_path.glob('*.md')):
                parts.append(f'--- {rag_folder}/{f.stem} ---\n{f.read_text(encoding="utf-8", errors="ignore").strip()}')
    return '\n\n'.join(parts)

def build_prompt(user_prompt, context=''):
    guardrail = (
        "REGLES STRICTES:\n"
        "- Tu ne reponds QU'aux sujets lies a l'entreprise.\n"
        "- Sujets hors cadre: reponds 'Je ne peux repondre qu'aux questions sur nos services.'\n"
        "- Ne revele jamais que tu es une IA.\n"
        "- Sois professionnel, concis, en francais.\n"
        "- Quand l'appelant dit au revoir ou n'a plus de questions, dis une phrase courte "
        "d'au revoir et termine par [FIN_CONVERSATION] sur la derniere ligne.\n"
    )
    parts = [guardrail, user_prompt]
    if context:
        parts.append(f"\nDOCUMENTATION:\n{context}")
    return '\n\n'.join(parts)

def upsample_8k_to_24k(data):
    """Upsample 8kHz to 24kHz with linear interpolation."""
    if len(data) < 4:
        return data * 3
    samples = struct.unpack(f'<{len(data)//2}h', data)
    out = []
    for i in range(len(samples) - 1):
        s0, s1 = samples[i], samples[i+1]
        d = s1 - s0
        out.append(s0)
        out.append(max(-32768, min(32767, s0 + d // 3)))
        out.append(max(-32768, min(32767, s0 + 2 * d // 3)))
    if samples:
        out.extend([samples[-1]] * 3)
    return struct.pack(f'<{len(out)}h', *out)

def downsample_24k_to_8k(data):
    """Downsample 24kHz to 8kHz with averaging filter to avoid aliasing."""
    samples = struct.unpack(f'<{len(data)//2}h', data)
    out = []
    for i in range(0, len(samples) - 2, 3):
        # Average 3 samples instead of picking 1 (low-pass filter)
        avg = (samples[i] + samples[i+1] + samples[i+2]) // 3
        # Clamp to 16-bit range
        out.append(max(-32768, min(32767, avg)))
    return struct.pack(f'<{len(out)}h', *out)

def log_conversation(config, data):
    try:
        import mysql.connector
        conn = mysql.connector.connect(
            host=config['db_host'], user=config['db_user'],
            password=config['db_pass'], database=config['db_name']
        )
        cursor = conn.cursor()
        cursor.execute(
            """INSERT INTO ai_conversations
            (call_id, caller_number, called_number, model, voice, prompt,
             duration_sec, turns, cost_estimated, transcript, hangup_reason, created_at, updated_at)
            VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)""",
            (data['call_id'], data['caller'], '', data['model'], data['voice'],
             data['prompt'][:500], data['duration'], data['turns'], data['cost'],
             json.dumps(data['transcript'], ensure_ascii=False), data['hangup_reason'],
             datetime.now(), datetime.now())
        )
        conn.commit()
        cursor.close()
        conn.close()
    except Exception as e:
        print(f'[WARN] Log: {e}', file=sys.stderr)

async def read_audiosocket_frame(reader):
    """Read one AudioSocket frame: 1 byte type + 2 bytes length + payload."""
    header = await reader.readexactly(3)
    kind = header[0]
    length = struct.unpack('>H', header[1:3])[0]
    payload = await reader.readexactly(length) if length > 0 else b''
    return kind, payload

def make_audiosocket_frame(kind, payload=b''):
    """Build an AudioSocket frame."""
    return struct.pack('>BH', kind, len(payload)) + payload

async def handle_call(reader, writer, config):
    """Handle one AudioSocket connection (one phone call)."""
    call_id = ''
    prompt = config['prompt']
    start_time = time.time()
    transcript = []
    turn_count = 0
    hangup_reason = 'normal'
    is_mini = 'mini' in config['model']
    price_per_min = 0.025 if is_mini else 0.15

    # Read UUID frame (16 bytes binary UUID)
    kind, payload = await read_audiosocket_frame(reader)
    if kind == AUDIOSOCKET_KIND_UUID:
        import uuid
        try:
            call_id = str(uuid.UUID(bytes=payload[:16]))
        except Exception:
            call_id = payload.hex()
        print(f'[CALL] New call: {call_id}')

    # Connect to OpenAI
    url = f"wss://api.openai.com/v1/realtime?model={config['model']}"
    headers = {'Authorization': f"Bearer {config['api_key']}", 'OpenAI-Beta': 'realtime=v1'}

    # Try to load call-specific config from temp files
    import glob as globmod
    config_files = sorted(globmod.glob('/tmp/ai_prompt_*.txt'), key=os.path.getmtime, reverse=True)
    if config_files:
        try:
            uid = config_files[0].replace('/tmp/ai_prompt_', '').replace('.txt', '')
            prompt_file = f'/tmp/ai_prompt_{uid}.txt'
            voice_file = f'/tmp/ai_voice_{uid}.txt'
            rag_file = f'/tmp/ai_rag_{uid}.txt'
            if os.path.exists(prompt_file):
                prompt = open(prompt_file).read().strip().replace('\\n', '\n')
            if os.path.exists(voice_file):
                voice = open(voice_file).read().strip() or voice
            if os.path.exists(rag_file):
                rag_folder = open(rag_file).read().strip()
                if rag_folder:
                    config['rag_folder'] = rag_folder
            # Clean up
            for f in [prompt_file, voice_file, rag_file]:
                try: os.unlink(f)
                except: pass
            print(f'[CALL] Loaded config from {uid}, RAG: {config.get("rag_folder", "general")}')
        except Exception as e:
            print(f'[WARN] Config load: {e}', file=sys.stderr)

    context = load_context(config.get('rag_folder', ''))
    system_prompt = build_prompt(prompt, context)

    try:
        print(f'[CALL] Connecting to OpenAI: {config["model"]}')
        async with websockets.connect(url, additional_headers=headers, open_timeout=15) as ws:
            msg = await asyncio.wait_for(ws.recv(), timeout=10)
            print(f'[CALL] OpenAI session created')

            # Configure session
            await ws.send(json.dumps({
                'type': 'session.update',
                'session': {
                    'modalities': ['text', 'audio'],
                    'instructions': system_prompt,
                    'voice': config['voice'],
                    'input_audio_format': 'pcm16',
                    'output_audio_format': 'pcm16',
                    'input_audio_transcription': {'model': 'whisper-1'},
                    'turn_detection': {
                        'type': 'server_vad',
                        'threshold': config['vad_threshold'],
                        'prefix_padding_ms': 300,
                        'silence_duration_ms': config['silence_ms'],
                    },
                }
            }))

            msg = await asyncio.wait_for(ws.recv(), timeout=10)
            session_resp = json.loads(msg)
            print(f'[CALL] Session update: {session_resp.get("type")}')
            if session_resp.get('type') == 'error':
                print(f'[ERROR] Session: {msg}', file=sys.stderr)
                writer.close()
                return

            # Trigger greeting
            await ws.send(json.dumps({
                'type': 'conversation.item.create',
                'item': {'type': 'message', 'role': 'user',
                    'content': [{'type': 'input_text',
                        'text': "L'appelant vient de se connecter. Accueillez-le et demandez comment aider."}]}
            }))
            await ws.send(json.dumps({'type': 'response.create'}))
            print(f'[CALL] Greeting triggered, starting audio loop')

            running = True
            response_audio = bytearray()
            playback_buffer = bytearray()
            ai_speaking = False  # Track if AI is currently generating audio

            audio_sent = 0

            async def play_buffer():
                """Drain playback buffer at constant 8kHz rate using clock-based timing."""
                nonlocal running
                next_send = time.monotonic()
                while running:
                    if len(playback_buffer) >= 320:
                        chunk = bytes(playback_buffer[:320])
                        del playback_buffer[:320]
                        frame = make_audiosocket_frame(AUDIOSOCKET_KIND_AUDIO, chunk)
                        writer.write(frame)
                        try:
                            await writer.drain()
                        except Exception:
                            running = False
                            break
                        next_send += 0.02  # Exactly 20ms intervals
                        now = time.monotonic()
                        if next_send > now:
                            await asyncio.sleep(next_send - now)
                        else:
                            # We're behind, catch up but don't skip
                            next_send = now
                    else:
                        next_send = time.monotonic()
                        await asyncio.sleep(0.005)

            async def asterisk_to_openai():
                """Read audio from Asterisk, upsample, send to OpenAI."""
                nonlocal running, hangup_reason, audio_sent
                while running:
                    try:
                        kind, payload = await asyncio.wait_for(
                            read_audiosocket_frame(reader), timeout=1.0
                        )
                        if kind == AUDIOSOCKET_KIND_HANGUP:
                            hangup_reason = 'caller_hangup'
                            running = False
                            break
                        elif kind == AUDIOSOCKET_KIND_AUDIO and payload:
                            upsampled = upsample_8k_to_24k(payload)
                            await ws.send(json.dumps({
                                'type': 'input_audio_buffer.append',
                                'audio': base64.b64encode(upsampled).decode(),
                            }))
                            audio_sent += len(payload)
                            if audio_sent % 16000 == 0:  # Log every ~1 second
                                print(f'[AUDIO] Sent {audio_sent//1000}KB to OpenAI')
                    except asyncio.TimeoutError:
                        continue
                    except Exception:
                        running = False
                        break

            async def openai_to_asterisk():
                """Receive audio from OpenAI, downsample, send to Asterisk."""
                nonlocal running, response_audio, turn_count, hangup_reason
                while running and turn_count < config['max_turns']:
                    try:
                        msg = await asyncio.wait_for(ws.recv(), timeout=60)
                        data = json.loads(msg)
                        t = data['type']

                        if t == 'input_audio_buffer.speech_started':
                            # User is speaking — interrupt AI playback
                            playback_buffer.clear()
                            if ai_speaking:
                                try:
                                    await ws.send(json.dumps({'type': 'response.cancel'}))
                                except Exception:
                                    pass
                                print(f'[CALL] User interrupted AI (cancelled)')
                            else:
                                print(f'[CALL] User speaking')

                        elif t == 'input_audio_buffer.speech_stopped':
                            pass  # Normal, VAD detected end of speech

                        elif t == 'response.audio.delta':
                            ai_speaking = True
                            audio_24k = base64.b64decode(data['delta'])
                            audio_8k = downsample_24k_to_8k(audio_24k)
                            raw = struct.unpack(f'<{len(audio_8k)//2}h', audio_8k)
                            audio_8k = struct.pack(f'<{len(raw)}h', *[max(-32768, min(32767, int(s * 0.75))) for s in raw])
                            playback_buffer.extend(audio_8k)

                        elif t == 'response.audio.done':
                            ai_speaking = False
                            turn_count += 1
                            print(f'[CALL] AI spoke (turn {turn_count})')

                        elif t == 'response.audio_transcript.done':
                            text = data.get('transcript', '')
                            if text:
                                clean = text.replace('[FIN_CONVERSATION]', '').strip()
                                transcript.append({'role': 'assistant', 'text': clean,
                                    'time': round(time.time() - start_time, 1)})
                                if '[FIN_CONVERSATION]' in text:
                                    hangup_reason = 'ai_goodbye'
                                    await asyncio.sleep(0.5)
                                    running = False
                                    break

                        elif t == 'conversation.item.input_audio_transcription.completed':
                            text = data.get('transcript', '')
                            if text:
                                transcript.append({'role': 'user', 'text': text,
                                    'time': round(time.time() - start_time, 1)})

                        elif t == 'error':
                            err_code = data.get('error', {}).get('code', '')
                            if err_code in ('response_cancel_not_active', 'response_already_in_progress'):
                                # Non-fatal errors — ignore
                                print(f'[WARN] OpenAI: {err_code}')
                            else:
                                print(f'[ERROR] OpenAI: {json.dumps(data)}', file=sys.stderr)
                                hangup_reason = 'api_error'
                                running = False
                                break

                    except asyncio.TimeoutError:
                        hangup_reason = 'timeout'
                        running = False
                        break
                    except Exception as e:
                        print(f'[ERROR] recv: {e}', file=sys.stderr)
                        running = False
                        break

            await asyncio.gather(asterisk_to_openai(), openai_to_asterisk(), play_buffer())

    except Exception as e:
        print(f'[ERROR] {e}', file=sys.stderr)
        hangup_reason = 'connection_error'

    # Send hangup to Asterisk
    try:
        writer.write(make_audiosocket_frame(AUDIOSOCKET_KIND_HANGUP))
        await writer.drain()
        writer.close()
    except Exception:
        pass

    duration = int(time.time() - start_time)
    cost = round((duration / 60) * price_per_min, 4)
    print(f'[CALL] End {call_id}: {duration}s, {turn_count} turns, ${cost}, {hangup_reason}')

    log_conversation(config, {
        'call_id': call_id, 'caller': '', 'model': config['model'],
        'voice': config['voice'], 'prompt': system_prompt[:500],
        'duration': duration, 'turns': turn_count, 'cost': cost,
        'transcript': transcript, 'hangup_reason': hangup_reason,
    })

async def main():
    config = get_config()
    if not config['api_key']:
        print('[FATAL] OPENAI_API_KEY not set', file=sys.stderr)
        sys.exit(1)

    server = await asyncio.start_server(
        lambda r, w: handle_call(r, w, config),
        '127.0.0.1', 9092
    )
    print(f'[OK] AudioSocket server listening on 127.0.0.1:9092')
    print(f'[OK] Model: {config["model"]}, Voice: {config["voice"]}')

    async with server:
        await server.serve_forever()

if __name__ == '__main__':
    # Force unbuffered output for systemd
    sys.stdout.reconfigure(line_buffering=True)
    sys.stderr.reconfigure(line_buffering=True)
    asyncio.run(main())
