#!/usr/bin/env python3
"""OpenAI Realtime AGI for Asterisk — EAGI mode (audio on fd 3)
Features: DB logging, transcript, guardrails, context/RAG
"""
import sys, os, json, asyncio, base64, hashlib, subprocess, time, websockets, struct
from pathlib import Path
from datetime import datetime

SOUND_DIR = Path('/var/lib/asterisk/sounds/tts')
CACHE_DIR = Path('/var/spool/asterisk/tts_cache')
CONTEXT_DIR = Path('/var/www/html/storage/app/ai-context')

def get_db_connection():
    try:
        import mysql.connector
        env = {}
        for line in Path('/var/www/html/.env').read_text().splitlines():
            if '=' in line and not line.startswith('#'):
                k, v = line.split('=', 1)
                env[k.strip()] = v.strip().strip('"')
        return mysql.connector.connect(
            host=env.get('DB_HOST', '127.0.0.1'),
            user=env.get('DB_USERNAME', 'root'),
            password=env.get('DB_PASSWORD', ''),
            database=env.get('DB_DATABASE', 'sip_manager')
        )
    except Exception as e:
        sys.stderr.write(f'DB connect error: {e}\n')
        return None

def load_settings():
    settings = {}
    conn = get_db_connection()
    if conn:
        try:
            cursor = conn.cursor()
            cursor.execute("SELECT `key`, `value` FROM sip_settings WHERE `group` = 'ai'")
            for row in cursor.fetchall():
                settings[row[0]] = row[1]
            cursor.close()
            conn.close()
        except Exception as e:
            sys.stderr.write(f'Settings load error: {e}\n')
    return settings

def log_conversation(data):
    conn = get_db_connection()
    if not conn:
        return
    try:
        cursor = conn.cursor()
        cursor.execute(
            """INSERT INTO ai_conversations
            (call_id, caller_number, called_number, model, voice, prompt,
             duration_sec, turns, cost_estimated, transcript, hangup_reason, created_at, updated_at)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
            (data['call_id'], data['caller'], data['called'], data['model'], data['voice'],
             data['prompt'], data['duration'], data['turns'], data['cost'],
             json.dumps(data['transcript'], ensure_ascii=False), data['hangup_reason'],
             datetime.now(), datetime.now())
        )
        conn.commit()
        cursor.close()
        conn.close()
    except Exception as e:
        sys.stderr.write(f'Log error: {e}\n')

def load_context_files():
    """Load RAG context from text files in storage/app/ai-context/"""
    context = []
    if CONTEXT_DIR.exists():
        for f in sorted(CONTEXT_DIR.glob('*.txt')):
            context.append(f'--- {f.stem} ---\n{f.read_text(encoding="utf-8", errors="ignore").strip()}')
        for f in sorted(CONTEXT_DIR.glob('*.md')):
            context.append(f'--- {f.stem} ---\n{f.read_text(encoding="utf-8", errors="ignore").strip()}')
    return '\n\n'.join(context)

def build_system_prompt(user_prompt, context_docs=''):
    """Build final prompt with guardrails and context."""
    guardrail = (
        "REGLES STRICTES:\n"
        "- Tu ne reponds QU'aux sujets lies a l'entreprise et a ses services.\n"
        "- Si l'appelant pose des questions hors sujet (politique, religion, sujets personnels, "
        "blagues, contenu inapproprie), reponds poliment: "
        "'Je suis desole, je ne peux repondre qu'aux questions concernant nos services. "
        "Puis-je vous aider autrement ?'\n"
        "- Ne revele jamais que tu es une IA. Presente-toi comme un assistant du service.\n"
        "- Sois professionnel, concis et en francais.\n"
        "- Si tu ne connais pas la reponse, propose de transferer vers un operateur humain.\n"
        "- IMPORTANT: Quand l'appelant dit qu'il n'a plus de questions, qu'il veut raccrocher, "
        "dit 'au revoir', 'merci c'est tout', 'bonne journee', ou toute formule de fin, "
        "reponds avec une phrase d'au revoir courte et termine OBLIGATOIREMENT ton message "
        "par le mot exact [FIN_CONVERSATION] sur la derniere ligne. "
        "Ce mot-cle est indispensable pour que le systeme raccroche.\n"
    )

    parts = [guardrail, user_prompt]

    if context_docs:
        parts.append(
            "\n\nDOCUMENTATION DE REFERENCE (utilise ces informations pour repondre):\n"
            + context_docs
        )

    return '\n\n'.join(parts)

def get_api_key():
    for line in Path('/var/www/html/.env').read_text().splitlines():
        if line.startswith('OPENAI_API_KEY='):
            return line.split('=', 1)[1].strip().strip('"')
    return ''

def read_agi_env():
    env = {}
    while True:
        line = sys.stdin.readline().strip()
        if not line:
            break
        if ':' in line:
            k, v = line.split(':', 1)
            env[k.strip()] = v.strip()
    return env

def agi_cmd(cmd):
    sys.stdout.write(cmd + '\n')
    sys.stdout.flush()
    return sys.stdin.readline().strip()

def upsample_8k_to_24k(data_8k):
    samples = struct.unpack(f'<{len(data_8k)//2}h', data_8k)
    out = []
    for i in range(len(samples) - 1):
        s0, s1 = samples[i], samples[i + 1]
        out.extend([s0, s0 + (s1 - s0) // 3, s0 + 2 * (s1 - s0) // 3])
    if samples:
        out.extend([samples[-1]] * 3)
    return struct.pack(f'<{len(out)}h', *out)

async def run_conversation(system_prompt, voice, audio_fd, api_key, settings, agi_env):
    model = settings.get('openai_model', 'gpt-4o-realtime-preview-2024-12-17')
    max_turns = int(settings.get('openai_max_turns', '30'))
    vad_threshold = float(settings.get('openai_vad_threshold', '0.5'))
    silence_ms = int(settings.get('openai_silence_ms', '1000'))

    is_mini = 'mini' in model
    price_per_min = 0.025 if is_mini else 0.15

    url = f'wss://api.openai.com/v1/realtime?model={model}'
    headers = {'Authorization': f'Bearer {api_key}', 'OpenAI-Beta': 'realtime=v1'}
    SOUND_DIR.mkdir(parents=True, exist_ok=True)
    CACHE_DIR.mkdir(parents=True, exist_ok=True)

    transcript = []
    start_time = time.time()
    hangup_reason = 'normal'

    async with websockets.connect(url, additional_headers=headers, open_timeout=15) as ws:
        await asyncio.wait_for(ws.recv(), timeout=10)

        await ws.send(json.dumps({
            'type': 'session.update',
            'session': {
                'modalities': ['text', 'audio'],
                'instructions': system_prompt,
                'voice': voice,
                'input_audio_format': 'pcm16',
                'output_audio_format': 'pcm16',
                'input_audio_transcription': {'model': 'whisper-1'},
                'turn_detection': {
                    'type': 'server_vad',
                    'threshold': vad_threshold,
                    'prefix_padding_ms': 300,
                    'silence_duration_ms': silence_ms,
                },
            }
        }))

        msg = await asyncio.wait_for(ws.recv(), timeout=10)
        data = json.loads(msg)
        if data.get('type') == 'error':
            sys.stderr.write(f'Session error: {json.dumps(data)}\n')
            hangup_reason = 'session_error'
            return

        # Trigger greeting
        await ws.send(json.dumps({
            'type': 'conversation.item.create',
            'item': {
                'type': 'message', 'role': 'user',
                'content': [{'type': 'input_text',
                    'text': "L'appelant vient de se connecter. Accueillez-le et demandez comment vous pouvez aider."}]
            }
        }))
        await ws.send(json.dumps({'type': 'response.create'}))

        response_audio = bytearray()
        running = True
        turn_count = 0
        greeting_played = False

        async def send_audio():
            nonlocal running, greeting_played
            loop = asyncio.get_event_loop()
            while not greeting_played and running:
                await asyncio.sleep(0.1)
            while running and turn_count < max_turns:
                try:
                    chunk = await loop.run_in_executor(None, lambda: os.read(audio_fd, 640))
                    if not chunk:
                        running = False
                        hangup_reason = 'caller_hangup'
                        break
                    await ws.send(json.dumps({
                        'type': 'input_audio_buffer.append',
                        'audio': base64.b64encode(upsample_8k_to_24k(chunk)).decode(),
                    }))
                except Exception:
                    running = False
                    break

        async def recv_responses():
            nonlocal response_audio, turn_count, running, greeting_played, hangup_reason
            while running and turn_count < max_turns:
                try:
                    msg = await asyncio.wait_for(ws.recv(), timeout=60)
                    data = json.loads(msg)
                    t = data['type']

                    if t == 'response.audio.delta':
                        response_audio.extend(base64.b64decode(data['delta']))

                    elif t == 'response.audio.done':
                        if response_audio:
                            turn_count += 1
                            h = hashlib.md5(response_audio).hexdigest()
                            sln = SOUND_DIR / f'ai_{h}.sln'
                            if not sln.exists():
                                raw = CACHE_DIR / f'{h}.raw'
                                raw.write_bytes(response_audio)
                                subprocess.run([
                                    'sox', '-t', 'raw', '-r', '24000',
                                    '-e', 'signed-integer', '-b', '16', '-c', '1',
                                    str(raw), '-r', '8000', str(sln)
                                ], capture_output=True)
                                raw.unlink(missing_ok=True)
                            agi_cmd(f'STREAM FILE "tts/ai_{h}" ""')
                            response_audio = bytearray()
                            greeting_played = True

                    elif t == 'response.audio_transcript.done':
                        text = data.get('transcript', '')
                        if text:
                            clean = text.replace('[FIN_CONVERSATION]', '').strip()
                            transcript.append({'role': 'assistant', 'text': clean, 'time': round(time.time() - start_time, 1)})
                            if '[FIN_CONVERSATION]' in text:
                                hangup_reason = 'ai_goodbye'
                                running = False
                                break

                    elif t == 'conversation.item.input_audio_transcription.completed':
                        text = data.get('transcript', '')
                        if text:
                            transcript.append({'role': 'user', 'text': text, 'time': round(time.time() - start_time, 1)})

                    elif t == 'error':
                        sys.stderr.write(f'OpenAI error: {json.dumps(data)}\n')
                        hangup_reason = 'api_error'
                        running = False
                        break

                except asyncio.TimeoutError:
                    hangup_reason = 'timeout'
                    running = False
                    break
                except Exception as e:
                    sys.stderr.write(f'recv error: {e}\n')
                    running = False
                    break

            if turn_count >= max_turns:
                hangup_reason = 'max_turns'

        await asyncio.gather(send_audio(), recv_responses())

    # Log conversation
    duration = int(time.time() - start_time)
    cost = (duration / 60) * price_per_min
    log_conversation({
        'call_id': agi_env.get('agi_uniqueid', ''),
        'caller': agi_env.get('agi_callerid', ''),
        'called': agi_env.get('agi_extension', ''),
        'model': model,
        'voice': voice,
        'prompt': system_prompt[:500],
        'duration': duration,
        'turns': turn_count,
        'cost': round(cost, 4),
        'transcript': transcript,
        'hangup_reason': hangup_reason,
    })

def main():
    env = read_agi_env()
    user_prompt = env.get('agi_arg_1', 'Tu es un assistant telephonique. Reponds en francais.')
    voice = env.get('agi_arg_2', 'coral')
    api_key = get_api_key()
    settings = load_settings()

    if not voice or voice == 'coral':
        voice = settings.get('openai_voice', 'coral')

    if not api_key:
        agi_cmd('VERBOSE "OPENAI_API_KEY missing" 1')
        return

    # Load context documents (RAG)
    context_docs = load_context_files()

    # Build prompt with guardrails + context
    system_prompt = build_system_prompt(user_prompt, context_docs)

    agi_cmd('VERBOSE "OpenAI Realtime: starting" 2')
    try:
        asyncio.run(run_conversation(system_prompt, voice, 3, api_key, settings, env))
    except Exception as e:
        agi_cmd(f'VERBOSE "OpenAI error: {e}" 1')
        sys.stderr.write(f'Main error: {e}\n')
    agi_cmd('VERBOSE "OpenAI Realtime: ended" 2')

if __name__ == '__main__':
    main()
