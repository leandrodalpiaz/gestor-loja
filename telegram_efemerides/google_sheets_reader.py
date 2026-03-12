import json
import os
import random

import pandas as pd
from google.oauth2 import service_account
from googleapiclient.discovery import build


class GoogleSheetsReader:
    def __init__(self, config, base_dir=None):
        self.config = config
        self.base_dir = base_dir or os.path.dirname(os.path.abspath(__file__))
        self.planilha_id = config["google_sheets"]["planilha_id"]
        self.sheets_config = config["google_sheets"]["sheets"]
        self.history_file = os.path.join(self.base_dir, "message_history_dev.json")
        self.service = self._authenticate_google_sheets()
        self.df_principal = self._load_principal_sheet()

    def _authenticate_google_sheets(self):
        credentials_path = os.path.join(self.base_dir, "credentials.json")
        creds = service_account.Credentials.from_service_account_file(
            credentials_path,
            scopes=["https://www.googleapis.com/auth/spreadsheets.readonly"],
        )
        return build("sheets", "v4", credentials=creds)

    def _col_to_num(self, col_str):
        num = 0
        for c in col_str.upper():
            if "A" <= c <= "Z":
                num = num * 26 + (ord(c) - ord("A")) + 1
        return num

    def _read_sheet_data(self, sheet_name):
        sheet_info = self.sheets_config.get(sheet_name)
        if not sheet_info:
            return pd.DataFrame()

        all_sheets = (
            self.service.spreadsheets().get(spreadsheetId=self.planilha_id).execute().get("sheets", [])
        )
        real_sheet_name = None
        for sheet in all_sheets:
            if sheet.get("properties", {}).get("sheetId") == int(sheet_info["gid"]):
                real_sheet_name = sheet.get("properties", {}).get("title")
                break

        if not real_sheet_name:
            real_sheet_name = sheet_name

        range_name = f"'{real_sheet_name}'!A:ZZ"
        values = (
            self.service.spreadsheets()
            .values()
            .get(spreadsheetId=self.planilha_id, range=range_name)
            .execute()
            .get("values", [])
        )

        if not values:
            return pd.DataFrame()

        if sheet_info.get("has_header", False):
            return pd.DataFrame(values[1:], columns=values[0])

        df = pd.DataFrame(values)
        start_row = sheet_info.get("start_row", 0)
        if start_row > 0:
            df = df.iloc[start_row:]

        if "columns" in sheet_info:
            column_mapping = {}
            for col_name, col_letter in sheet_info["columns"].items():
                col_index = self._col_to_num(col_letter) - 1
                if col_index < len(df.columns):
                    column_mapping[df.columns[col_index]] = col_name

            df = df.rename(columns=column_mapping)
            selected_columns = [col for col in column_mapping.values() if col in df.columns]
            if selected_columns:
                df = df[selected_columns]

        return df

    def _load_principal_sheet(self):
        df = self._read_sheet_data("principal")
        if not df.empty and "data" in df.columns:
            df["data"] = pd.to_datetime(df["data"], errors="coerce", dayfirst=True)

        if "cod_evento" in df.columns:
            df["cod_evento"] = pd.to_numeric(df["cod_evento"], errors="coerce").fillna(0).astype(int)

        return df

    def get_events_for_anniversary_today(self):
        if self.df_principal.empty or "data" not in self.df_principal.columns:
            return pd.DataFrame()

        today = pd.Timestamp.now().date()
        return self.df_principal[
            (self.df_principal["data"].dt.month == today.month)
            & (self.df_principal["data"].dt.day == today.day)
        ]

    def calculate_years_since_event(self, event_date):
        if pd.isna(event_date):
            return 0

        today = pd.Timestamp.now().date()
        base_date = event_date.date() if hasattr(event_date, "date") else event_date
        years = today.year - base_date.year

        if (today.month, today.day) < (base_date.month, base_date.day):
            years -= 1

        return years

    def get_event_name_by_code(self, cod_evento):
        event_map = {
            1: "Aniversário",
            2: "Iniciação",
            3: "Elevação",
            4: "Exaltação",
            5: "Instalação",
            6: "Oriente Eterno",
            8: "Posse Grão Mestre",
            9: "Concessão de Membro Honorário",
            10: "Filiação",
            11: "História",
        }
        return event_map.get(cod_evento, "Desconhecido")

    def get_custom_message_from_row(self, row):
        candidate_columns = [
            "Mensagem",
            "mensagem",
            "Template",
            "template",
            "Texto",
            "texto",
        ]

        for col in candidate_columns:
            value = row.get(col)
            if value is not None and str(value).strip():
                return str(value).strip()
        return ""

    def _get_persistent_random_message(self, key, all_messages):
        if not all_messages:
            return ""

        history = {}
        if os.path.exists(self.history_file):
            try:
                with open(self.history_file, "r", encoding="utf-8") as f:
                    history = json.load(f)
            except json.JSONDecodeError:
                history = {}

        used_messages = history.get(key, [])
        available_messages = [msg for msg in all_messages if msg not in used_messages]

        if not available_messages:
            available_messages = all_messages
            used_messages = []

        selected_message = random.choice(available_messages)
        used_messages.append(selected_message)
        history[key] = used_messages

        with open(self.history_file, "w", encoding="utf-8") as f:
            json.dump(history, f, ensure_ascii=False, indent=2)

        return selected_message

    def buscar_mensagem_aniversario(self, tratamento):
        df = self._read_sheet_data("msg_aniversarios")
        if df.empty:
            return ""

        col_map = {"Irmão": 0, "Sobrinha": 1, "Sobrinho": 2, "Cunhada": 3, "": 0}
        col_index = col_map.get(tratamento, 0)
        if col_index >= len(df.columns):
            return ""

        mensagens = df.iloc[:, col_index].dropna().astype(str).tolist()
        return self._get_persistent_random_message(f"msg_aniversarios_{tratamento}", mensagens)

    def _buscar_mensagem_generica(self, sheet_name):
        df = self._read_sheet_data(sheet_name)
        if df.empty:
            return ""

        mensagens = df.iloc[:, 0].dropna().astype(str).tolist()
        return self._get_persistent_random_message(sheet_name, mensagens)

    def buscar_mensagem_curiosidade(self):
        return self._buscar_mensagem_generica("mensagens_extras")

    def buscar_mensagem_por_tipo(self, tipo_evento, tratamento=None):
        if tipo_evento == "Aniversário" and tratamento:
            return self.buscar_mensagem_aniversario(tratamento)

        map_sheet = {
            "Iniciação": "msg_iniciacao",
            "Elevação": "msg_elevacao",
            "Exaltação": "msg_exaltacao",
            "Instalação": "msg_instalacao",
        }
        sheet_name = map_sheet.get(tipo_evento)
        return self._buscar_mensagem_generica(sheet_name) if sheet_name else ""