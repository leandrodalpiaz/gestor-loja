import { Component, inject, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { SupabaseService } from '../../services/supabase.service';

interface CargoLink {
  label: string;
  path: string;
  description: string;
  icon: string;
  role: string;
}

@Component({
  selector: 'app-cargos-gestao',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './cargos-gestao.html',
  styleUrl: './cargos-gestao.css'
})
export class CargosGestao {
  protected supabaseService = inject(SupabaseService);

  protected cards = [
    {
      label: 'Venerável Mestre',
      path: '/dashboard/veneravel',
      description: 'Acompanhamento e gestão de sessões, votações e nominata crítica.',
      icon: '👑',
      role: 'veneravel'
    },
    {
      label: 'Hospitaleiro',
      path: '/dashboard/hospitaleiro',
      description: 'Tronco de Beneficência, ocorrências de assistência fraternal e visitas.',
      icon: '🏥',
      role: 'hospitaleiro'
    },
    {
      label: '1º Vigilante',
      path: '/dashboard/primeiro-vigilante',
      description: 'Acompanhamento da instrução e trilha de progresso dos Aprendizes.',
      icon: '📐',
      role: 'primeiro_vigilante'
    },
    {
      label: '2º Vigilante',
      path: '/dashboard/segundo-vigilante',
      description: 'Acompanhamento da instrução e trilha de progresso dos Companheiros.',
      icon: '🛠️',
      role: 'segundo_vigilante'
    },
    {
      label: 'Orador',
      path: '/dashboard/orador',
      description: 'Guardião da lei, resumo ritual, pauta, lembretes e saudações a visitantes.',
      icon: '⚖️',
      role: 'orador'
    },
    {
      label: 'Mestre de Banquetes',
      path: '/dashboard/mestre-banquetes',
      description: 'Planejamento operacional, orçamento, fornecedores e logística do Ágape.',
      icon: '🍷',
      role: 'mestre_banquetes'
    }
  ];

  protected cardsDisponiveis = computed(() => {
    const profile = this.supabaseService.profile();
    if (!profile) return [];

    const cargos = profile.cargos || [];
    const principal = profile.cargo_principal || '';
    const isAdmin = cargos.includes('admin') || principal === 'admin';

    if (isAdmin) {
      return this.cards;
    }

    return this.cards.filter(c => 
      principal === c.role || cargos.includes(c.role)
    );
  });
}
