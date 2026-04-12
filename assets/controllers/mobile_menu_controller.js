import { Controller } from '@hotwired/stimulus';

/**
 * Tiroir navigation mobile : ouverture, fermeture, Escape, clic hors zone.
 */
export default class extends Controller {
    static targets = ['panel', 'backdrop', 'openButton', 'closeButton'];

    connect () {
        this._onKeydown = this._onKeydown.bind(this);
        this._onResize = this._onResize.bind(this);
        window.addEventListener('resize', this._onResize);
    }

    disconnect () {
        document.removeEventListener('keydown', this._onKeydown);
        window.removeEventListener('resize', this._onResize);
        document.body.classList.remove('overflow-hidden', 'touch-none');
    }

    _onResize () {
        if (!window.matchMedia('(min-width: 1024px)').matches || !this.hasPanelTarget) {
            return;
        }
        if (!this.panelTarget.classList.contains('-translate-x-full')) {
            this.close();
        }
    }

    open () {
        if (!this.hasPanelTarget || !this.hasBackdropTarget) {
            return;
        }
        this.panelTarget.classList.remove('-translate-x-full');
        this.panelTarget.setAttribute('aria-hidden', 'false');
        this.backdropTarget.classList.remove('opacity-0', 'pointer-events-none');
        this.backdropTarget.setAttribute('aria-hidden', 'false');
        if (this.hasOpenButtonTarget) {
            this.openButtonTarget.setAttribute('aria-expanded', 'true');
        }
        document.body.classList.add('overflow-hidden', 'touch-none');
        document.addEventListener('keydown', this._onKeydown);
        requestAnimationFrame(() => {
            if (this.hasCloseButtonTarget) {
                this.closeButtonTarget.focus({ preventScroll: true });
            }
        });
    }

    close () {
        if (!this.hasPanelTarget || !this.hasBackdropTarget) {
            return;
        }
        this.panelTarget.classList.add('-translate-x-full');
        this.panelTarget.setAttribute('aria-hidden', 'true');
        this.backdropTarget.classList.add('opacity-0', 'pointer-events-none');
        this.backdropTarget.setAttribute('aria-hidden', 'true');
        if (this.hasOpenButtonTarget) {
            this.openButtonTarget.setAttribute('aria-expanded', 'false');
            this.openButtonTarget.focus({ preventScroll: true });
        }
        document.body.classList.remove('overflow-hidden', 'touch-none');
        document.removeEventListener('keydown', this._onKeydown);
    }

    /**
     * Ferme le tiroir après navigation interne (liens du menu).
     */
    navigateAway (event) {
        const anchor = event.target.closest('a[href]');
        if (!anchor) {
            return;
        }
        const href = anchor.getAttribute('href') || '';
        if (href.startsWith('#') || href.startsWith('javascript:')) {
            return;
        }
        this.close();
    }

    _onKeydown (event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            this.close();
        }
    }

}
