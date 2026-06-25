import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, RouterLinkActive } from '@angular/router';

export interface BottomNavTab {
  id: string;
  label: string;
  path: string;
  icon: string; // SVG path `d` attribute
  exact?: boolean;
}

@Component({
  selector: 'app-bottom-nav',
  standalone: true,
  imports: [CommonModule, RouterLink, RouterLinkActive],
  template: `
    <nav
      class="fixed bottom-0 left-0 right-0 z-[55] flex items-stretch border-t border-white/5 bg-[#070c16]/95 backdrop-blur-xl md:hidden"
      style="padding-bottom: env(safe-area-inset-bottom, 8px);"
    >
      @for (tab of tabs; track tab.id) {
        @if (tab.id === 'more') {
          <!-- "Mais" button: opens sidebar instead of navigating -->
          <button
            type="button"
            (click)="onMoreClick()"
            class="bottom-nav-tab flex flex-1 flex-col items-center justify-center gap-1 py-2 text-[10px] font-medium text-slate-400 transition-all duration-200 active:scale-90"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" [attr.d]="tab.icon" />
            </svg>
            <span class="font-inter tracking-wide">{{ tab.label }}</span>
          </button>
        } @else {
          <!-- Regular tab: uses RouterLink -->
          <a
            [routerLink]="tab.path"
            routerLinkActive="bottom-nav-active"
            [routerLinkActiveOptions]="{ exact: tab.exact ?? false }"
            class="bottom-nav-tab flex flex-1 flex-col items-center justify-center gap-1 py-2 text-[10px] font-medium text-slate-400 transition-all duration-200 active:scale-90"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" [attr.d]="tab.icon" />
            </svg>
            <span class="font-inter tracking-wide">{{ tab.label }}</span>
          </a>
        }
      }
    </nav>
  `,
  styles: [`
    :host {
      display: block;
    }

    .bottom-nav-tab {
      -webkit-tap-highlight-color: transparent;
      user-select: none;
      position: relative;
    }

    .bottom-nav-tab::before {
      content: '';
      position: absolute;
      top: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 24px;
      height: 2px;
      border-radius: 0 0 2px 2px;
      background: transparent;
      transition: background 200ms ease;
    }

    .bottom-nav-active {
      color: #C9A227 !important;
    }

    .bottom-nav-active::before {
      background: #C9A227;
    }

    .bottom-nav-tab:active {
      background: rgba(255, 255, 255, 0.03);
    }
  `]
})
export class BottomNav {
  @Input() tabs: BottomNavTab[] = [];
  @Output() moreClick = new EventEmitter<void>();

  onMoreClick(): void {
    this.moreClick.emit();
  }
}
