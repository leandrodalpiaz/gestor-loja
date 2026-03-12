from datetime import datetime

from historical_events import get_historical_event
from message_formatter import MessageFormatter


class EventManager:
    def __init__(self):
        self.events = []

    def add_event(self, event_data):
        self.events.append(event_data)

    def generate_daily_messages(self, reader):
        historical_message = get_historical_event(datetime.now().date())

        if not self.events:
            return [self._get_no_events_message(reader, historical_message)]

        all_messages = []
        if historical_message:
            all_messages.append(historical_message)

        events_by_type = self._group_events_by_type()

        for history_key in ["História", "Histórico", "Historia"]:
            if history_key in events_by_type:
                all_messages.extend(self._process_custom_history(events_by_type[history_key]))

        special_events = ["Posse Grão Mestre", "Concessão de Membro Honorário", "Filiação"]
        for event_type in special_events:
            if event_type in events_by_type:
                all_messages.extend(self._process_special_events(events_by_type[event_type], event_type))

        if "Aniversário" in events_by_type:
            all_messages.extend(self._process_anniversaries(events_by_type["Aniversário"], reader))

        ceremony_types = ["Iniciação", "Elevação", "Exaltação", "Instalação"]
        for ceremony_type in ceremony_types:
            if ceremony_type in events_by_type:
                all_messages.extend(
                    self._process_ceremony_group(events_by_type[ceremony_type], ceremony_type, reader)
                )

        if "Oriente Eterno" in events_by_type:
            all_messages.extend(self._process_eternal_orient(events_by_type["Oriente Eterno"]))

        return all_messages

    def _process_custom_history(self, events):
        messages = []
        for event in events:
            texto = event.get("MensagemCustom", "")
            if texto:
                messages.append(texto)
        return messages

    def _group_events_by_type(self):
        events_by_type = {}
        for event in self.events:
            event_type = event.get("Tipo", "Outro")
            events_by_type.setdefault(event_type, []).append(event)
        return events_by_type

    def _process_anniversaries(self, events, reader):
        messages = []
        events_by_treatment = {}

        for event in events:
            tratamento = event.get("Tratamento", "Irmão")
            events_by_treatment.setdefault(tratamento, []).append(event)

        for tratamento, treatment_events in events_by_treatment.items():
            if len(treatment_events) == 1:
                event = treatment_events[0]
                complementar = reader.buscar_mensagem_por_tipo("Aniversário", tratamento)
                messages.append(MessageFormatter.format_anniversary(event, complementar))
            else:
                messages.append(self._format_anniversary_group(treatment_events, tratamento, reader))

        return messages

    def _format_anniversary_group(self, events, tratamento, reader):
        if tratamento == "Irmão":
            return self._format_irmaos_group(events, reader)
        return self._format_non_irmaos_group(events, tratamento, reader)

    def _format_irmaos_group(self, events, reader):
        parts = ["Com fraterna alegria, hoje celebramos:"]
        for i, event in enumerate(events):
            nome = event.get("Nome", "Nome não informado")
            idade = event.get("idade", "idade não informada")
            parts.append(f"Os {idade} ano(s) de vida do nosso Irmão {nome} parabéns pelo seu aniversário.")
            if i < len(events) - 1:
                parts.append("Ainda celebramos:")

        complementar = reader.buscar_mensagem_por_tipo("Aniversário", "Irmão")
        if complementar:
            parts.append(complementar)
        return "\n".join(parts)

    def _format_non_irmaos_group(self, events, tratamento, reader):
        messages = []
        for event in events:
            nome = event.get("Nome", "Nome não informado")
            idade = event.get("idade", "idade não informada")
            vinculo = event.get("Vinculo", "vínculo não informado")
            parentesco = event.get("Parentesco", "parentesco não informado")

            tratamento_lower = tratamento.lower()
            artigo = "nossa" if tratamento_lower in ["cunhada", "sobrinha", "filha", "enteada"] else "nosso"

            if tratamento_lower in ["cunhada", "esposa"]:
                base = (
                    f"Hoje celebramos, com fraterna alegria, o aniversário de {artigo} {tratamento} {nome}, "
                    f"{vinculo} do nosso Irmão {parentesco}."
                )
            else:
                base = (
                    f"Hoje celebramos, com fraterna alegria, os {idade} ano(s) de vida de {artigo} {tratamento} {nome}, "
                    f"{vinculo} do nosso Irmão {parentesco}."
                )

            complementar = reader.buscar_mensagem_por_tipo("Aniversário", tratamento)
            if complementar:
                base += f" {complementar}"
            messages.append(base)

        return "\n\n".join(messages)

    def _process_ceremony_group(self, events, ceremony_type, reader):
        if len(events) == 1:
            event = events[0]
            complementar = reader.buscar_mensagem_por_tipo(ceremony_type)
            return [MessageFormatter.format_ceremony(event, ceremony_type, complementar)]

        return [self._format_ceremony_group_unified(events, ceremony_type, reader)]

    def _format_ceremony_group_unified(self, events, ceremony_type, reader):
        parts = ["Neste dia, registramos com honra:"]
        for i, event in enumerate(events):
            nome = event.get("Nome", "Nome não informado")
            data_evento = event.get("data", "")
            anos = event.get("idade", "idade não informada")
            local = event.get("Local", "local não informado")
            data_formatada = data_evento.strftime("%d/%m/%Y") if isinstance(data_evento, datetime) else str(data_evento)

            texto = (
                f"{anos} ano(s) de um passo fundamental em vossa jornada: em {data_formatada}, ocorria a vossa "
                f"{ceremony_type}, querido Irmão {nome}, nas colunas da {local}."
            )
            parts.append(texto if i == 0 else f"E também: {texto}")

        complementar = reader.buscar_mensagem_por_tipo(ceremony_type)
        if complementar:
            parts.append(complementar)
        return "\n".join(parts)

    def _process_special_events(self, events, event_type):
        messages = []
        for event in events:
            if event_type == "Posse Grão Mestre":
                messages.append(self._format_posse_gm(event))
            elif event_type == "Concessão de Membro Honorário":
                messages.append(self._format_membro_honorario(event))
            elif event_type == "Filiação":
                messages.append(self._format_filiacao(event))
        return messages

    def _format_posse_gm(self, event):
        nome = event.get("Nome", "Nome não informado")
        data_evento = event.get("data", "")
        anos = event.get("idade", "idade não informada")
        local = event.get("Local", "local não informado")
        data_formatada = data_evento.strftime("%d/%m/%Y") if isinstance(data_evento, datetime) else str(data_evento)
        return (
            f"Recordamos hoje com profundo respeito a data magna em que o Malhete da Grande Loja foi confiado às vossas mãos. "
            f"Há {anos} anos, em {data_formatada}, a Maçonaria celebrava a vossa Posse como Grão Mestre, querido Irmão {nome}, "
            f"um momento que fortaleceu as colunas do {local} e de toda a nossa Jurisdição."
        )

    def _format_membro_honorario(self, event):
        nome = event.get("Nome", "Nome não informado")
        data_evento = event.get("data", "")
        anos = event.get("idade", "idade não informada")
        local = event.get("Local", "Loja não informada")
        data_formatada = data_evento.strftime("%d/%m/%Y") if isinstance(data_evento, datetime) else str(data_evento)
        return (
            f"Com imensa alegria e o coração grato, a {local} celebra hoje o aniversário de um dia de grande honra para nossa Oficina. "
            f"Há exatamente {anos} anos, em {data_formatada}, tivemos o privilégio de realizar a Concessão do Título de Membro Honorário "
            f"ao nosso estimado Irmão {nome}."
        )

    def _format_filiacao(self, event):
        nome = event.get("Nome", "Nome não informado")
        data_evento = event.get("data", "")
        anos = event.get("idade", "idade não informada")
        local = event.get("Local", "local não informado")
        data_formatada = data_evento.strftime("%d/%m/%Y") if isinstance(data_evento, datetime) else str(data_evento)
        return (
            f"Neste dia, registramos com honra, {anos} ano(s) de um passo fundamental em vossa jornada: em {data_formatada}, "
            f"ocorria a vossa Filiação, querido Irmão {nome}, nas colunas da {local}."
        )

    def _process_eternal_orient(self, events):
        return [MessageFormatter.format_eternal_orient(event) for event in events]

    def _get_no_events_message(self, reader, historical_message):
        if historical_message:
            return historical_message

        curiosidade_message = reader.buscar_mensagem_curiosidade()
        if curiosidade_message:
            return (
                "*Um Toque da Trolha:*\n\n"
                f"{curiosidade_message}\n\n"
                "Que este toque provoque a busca que suaviza nossas arestas."
            )
        return "Nenhum evento especial para hoje."
