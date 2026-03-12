from datetime import datetime

class MessageFormatter:
    @staticmethod
    def format_anniversary(event, complementar_msg=""):
        """Formata mensagem de aniversário para eventos ÚNICOS"""
        nome = event.get('Nome', '') or 'Nome não informado'
        idade = event.get('idade', '') or 'idade não informada'
        tratamento = event.get('Tratamento', '') or 'tratamento não informado'
        vinculo = event.get('Vinculo', '') or 'vínculo não informado'
        parentesco = event.get('Parentesco', '') or 'parentesco não informado'
        cod_vinculo = event.get('Cod_vinculo', 0)

        if cod_vinculo == 1:  # Irmão
            message = f"Com fraterna alegria, hoje celebramos os {idade} ano(s) de vida do nosso Irmão {nome} parabéns pelo seu aniversário."
        else:
            tratamento_lower = tratamento.lower()

            # Lista de tratamentos que levam o artigo feminino
            tratamentos_femininos = ["cunhada", "sobrinha", "filha", "enteada", "esposa"]
            artigo = "nossa" if tratamento_lower in tratamentos_femininos else "nosso"

            # Lógica para ocultar a idade APENAS para Cunhadas e Esposas
            if tratamento_lower in ["cunhada", "esposa"]:
                message = f"Hoje celebramos, com fraterna alegria, o aniversário de {artigo} {tratamento} {nome}, {vinculo} do nosso Irmão {parentesco}."
            else:
                # Mantém a idade para filhos, sobrinhos, etc.
                message = f"Hoje celebramos, com fraterna alegria, os {idade} ano(s) de vida de {artigo} {tratamento} {nome}, {vinculo} do nosso Irmão {parentesco}."

        if complementar_msg:
            message += f" {complementar_msg}"

        return message

    @staticmethod
    def format_ceremony(event, ceremony_type, complementar_msg=""):
        """Formata mensagem para eventos cerimoniais ÚNICOS"""
        nome = event.get('Nome', '') or 'Nome não informado'
        data_evento = event.get('data', '')
        anos = event.get('idade', '') or 'idade não informada'
        local = event.get('Local', '') or 'local não informado'

        if isinstance(data_evento, datetime):
            data_formatada = data_evento.strftime('%d/%m/%Y')
        else:
            data_formatada = str(data_evento)

        message = f'"Neste dia, registramos com honra, {anos} ano(s) de um passo fundamental em vossa jornada: em {data_formatada}, ocorria a vossa {ceremony_type}, querido Irmão {nome}, nas colunas da {local}."'

        if complementar_msg:
            message += f" {complementar_msg}"

        return message

    @staticmethod
    def format_eternal_orient(event):
        """Formata mensagem para Oriente Eterno"""
        nome = event.get('Nome', '') or 'Nome não informado'
        data_evento = event.get('data', '')

        if isinstance(data_evento, datetime):
            data_formatada = data_evento.strftime('%d/%m/%Y')
        else:
            data_formatada = str(data_evento)

        return f"Com profundo pesar, lembramos de nosso Irmão {nome} que partiu para o Oriente Eterno em {data_formatada}."
