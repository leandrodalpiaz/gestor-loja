import json
import os
from urllib import request
from urllib.error import HTTPError, URLError


class TelegramSender:
    def __init__(self, config):
        self.config = config.get('telegram_settings', {})
        if not self.config:
            raise ValueError('telegram_settings não encontrado no config.json')

        token_cfg = self.config.get('bot_token', '').strip()
        self.token = os.getenv('TELEGRAM_BOT_TOKEN', '') or ('' if token_cfg.startswith('COLOQUE_') else token_cfg)
        self.use_test = self.config.get('use_test_chat', True)

        test_cfg = str(self.config.get('test_chat_id', '')).strip()
        prod_cfg = str(self.config.get('production_chat_id', '')).strip()

        test_chat = os.getenv('TELEGRAM_CHAT_ID_CHANCELER', '') or ('' if test_cfg.startswith('COLOQUE_') else test_cfg)
        prod_chat = os.getenv('TELEGRAM_CHAT_ID_GROUP', '') or ('' if prod_cfg.startswith('COLOQUE_') else prod_cfg)

        self.chat_id = test_chat if self.use_test else prod_chat
        self.parse_mode = self.config.get('parse_mode', 'Markdown')

    def _split_message(self, mensagem, chunk_size=3500):
        if len(mensagem) <= chunk_size:
            return [mensagem]

        chunks = []
        current = []
        current_len = 0

        for paragraph in mensagem.split('\n\n'):
            block = paragraph + '\n\n'
            if current_len + len(block) > chunk_size and current:
                chunks.append(''.join(current).strip())
                current = [block]
                current_len = len(block)
            else:
                current.append(block)
                current_len += len(block)

        if current:
            chunks.append(''.join(current).strip())

        return chunks

    def enviar_mensagem(self, mensagem):
        if not self.token or not self.chat_id:
            print('Erro: bot_token ou chat_id não configurados no config.json.')
            return

        print(f"Enviando mensagem para o Telegram (Chat ID: {self.chat_id})...")
        url = f"https://api.telegram.org/bot{self.token}/sendMessage"

        for idx, chunk in enumerate(self._split_message(mensagem), start=1):
            payload = {
                'chat_id': self.chat_id,
                'text': chunk,
                'parse_mode': self.parse_mode,
            }

            try:
                req = request.Request(
                    url,
                    data=json.dumps(payload).encode('utf-8'),
                    headers={'Content-Type': 'application/json'},
                    method='POST',
                )
                with request.urlopen(req, timeout=30) as response:
                    if response.status != 200:
                        raise RuntimeError(f'HTTP status inesperado: {response.status}')
                print(f'Parte {idx} enviada com sucesso para o Telegram!')
            except HTTPError as e:
                print(f'Erro ao enviar a parte {idx} para o Telegram: {e}')
                try:
                    print(e.read().decode('utf-8', errors='replace'))
                except Exception:
                    pass
                raise
            except URLError as e:
                print(f'Erro ao enviar a parte {idx} para o Telegram: {e}')
                raise