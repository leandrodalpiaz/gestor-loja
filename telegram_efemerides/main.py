import json
import os
import argparse

from google_sheets_reader import GoogleSheetsReader
from event_manager import EventManager
from telegram_sender import TelegramSender


BASE_DIR = os.path.dirname(os.path.abspath(__file__))

def load_config():
    config_path = os.path.join(BASE_DIR, 'config.json')
    with open(config_path, 'r', encoding='utf-8') as f:
        return json.load(f)


def parse_int(value, default=0):
    if value is None:
        return default

    text = str(value).strip().replace(',', '.')
    if not text:
        return default

    try:
        return int(float(text))
    except ValueError:
        return default


def run(dry_run=False):
    print("Iniciando o Bot de Efemérides para Telegram...")
    config = load_config()

    reader = GoogleSheetsReader(config, base_dir=BASE_DIR)
    eventos_hoje = reader.get_events_for_anniversary_today()
    event_manager = EventManager()

    for _, evento_row in eventos_hoje.iterrows():
        nome = evento_row.get('Nome', 'Nome não informado')
        data_evento = evento_row.get('data')
        idade = reader.calculate_years_since_event(data_evento)

        cod_vinculo = parse_int(evento_row.get('cod_vinculo', 0), default=0)

        tratamento_map = {
            1: 'Irmão',
            2: 'Cunhada',
            3: 'Sobrinha',
            4: 'Sobrinho',
            5: 'Sobrinha',
            6: 'Sobrinho',
        }

        cod_evento = parse_int(evento_row.get('cod_evento', 0), default=0)
        tipo = evento_row.get('Tipo') or reader.get_event_name_by_code(cod_evento)

        event_data = {
            'Nome': nome,
            'Tipo': tipo,
            'data': data_evento,
            'idade': idade,
            'Tratamento': tratamento_map.get(cod_vinculo, 'tratamento não informado'),
            'Vinculo': evento_row.get('vinculo', 'vínculo não informado'),
            'Parentesco': evento_row.get('parentesco', 'parentesco não informado'),
            'Cod_vinculo': cod_vinculo,
            'Local': evento_row.get('Local', 'Loja Renascença nº 270'),
            'MensagemCustom': reader.get_custom_message_from_row(evento_row),
        }
        event_manager.add_event(event_data)

    mensagens = event_manager.generate_daily_messages(reader)
    mensagens_validas = [m for m in mensagens if m and m.strip()]
    mensagem_final = '\n\n'.join(mensagens_validas) if mensagens_validas else 'Nenhum evento para hoje.'

    print('Mensagem montada:\n', mensagem_final)

    preview_path = os.path.join(BASE_DIR, 'ultima_mensagem_preview.txt')
    with open(preview_path, 'w', encoding='utf-8') as f:
        f.write(mensagem_final)

    if dry_run:
        print('Modo dry-run ativo: mensagem não enviada ao Telegram.')
        print(f'Preview salvo em: {preview_path}')
    else:
        sender = TelegramSender(config)
        sender.enviar_mensagem(mensagem_final)

    print('Processo finalizado com sucesso.')

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description='Bot de Efemérides para Telegram')
    parser.add_argument('--dry-run', action='store_true', help='Gera mensagem sem enviar ao Telegram')
    args = parser.parse_args()
    run(dry_run=args.dry_run)