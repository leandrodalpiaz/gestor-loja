from datetime import datetime


def get_historical_event(date_obj=None):
    reference_date = date_obj or datetime.now().date()
    hoje = reference_date.strftime("%m-%d")

    current_year = datetime.now().year
    eventos = {
        "01-08": (
            "*08 de Janeiro - A Fundação da Grande Loja Maçônica do Estado do Rio Grande do Sul*\n\n"
            f"*{current_year - 1928} anos de uma história escrita em pedra e luz (1928)*\n\n"
            "Hoje celebramos o nascimento da nossa Grande Loja, um marco que redefiniu os rumos da Ordem no Rio Grande do Sul. "
            "Em 8 de janeiro de 1928, a cidade de Bagé tornou-se o berço de uma nova era de soberania para os graus simbólicos.\n\n"
            "Fonte: Dados baseados nos registros históricos da Grande Loja do RS e no Ato de Fundação de 1928."
        ),
        "01-27": (
            "*27 de janeiro - Nascimento de Mário Behring*\n\n"
            f"*{current_year - 1876} anos do nascimento do Arquiteto da Maçonaria Regular Brasileira (1876)*\n\n"
            "Nascido em Minas Gerais em 1876, Mário Behring ingressou na Maçonaria aos 22 anos na Loja União Cosmopolita, "
            "onde rapidamente se destacou como defensor da liberdade de pensamento e crítico dos radicalismos religiosos.\n\n"
            "Em 1927, diante da concentração irregular de poder no GOB, Behring liderou a histórica fundação do Sistema de Grandes Lojas Brasileiras, "
            "restaurando a regularidade e o respeito internacional da Maçonaria no país.\n\n"
            "Fonte: Site da GLRS - Documentos Históricos."
        ),
        "08-20": "📜 *Nesta data:* Dia do Maçom.",
        "09-07": "📜 *Nesta data:* Independência do Brasil.",
    }

    return eventos.get(hoje)