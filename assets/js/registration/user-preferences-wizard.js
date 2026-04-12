/**
 * Wizard multi-étapes préférences utilisateur (inscription).
 */
function readConfig() {
    const el = document.getElementById('user-preferences-config');
    if (!el || !el.textContent.trim()) {
        return null;
    }
    try {
        return JSON.parse(el.textContent);
    } catch {
        return null;
    }
}

function boot() {
    const configEl = document.getElementById('user-preferences-config');
    if (!configEl || configEl.dataset.wizardInit === '1') {
        return;
    }

    const config = readConfig();
    if (!config?.saveUrl || !config?.skipUrl) {
        return;
    }

    const state = {
        currentStep: 1,
        totalSteps: 4,
        cuisineTypes: [],
        budgetMin: null,
        budgetMax: null,
        preferredCity: '',
        searchRadius: null,
        capacityMin: null,
    };

    const progressBar = document.getElementById('progress-bar');
    const stepNum = document.getElementById('current-step-num');
    const stepPercent = document.getElementById('step-percent');
    const btnPrev = document.getElementById('btn-prev');
    const btnNext = document.getElementById('btn-next');
    const btnSkip = document.getElementById('btn-skip');
    const btnSubmit = document.getElementById('btn-submit');

    if (!progressBar || !btnPrev || !btnNext || !btnSkip || !btnSubmit) {
        return;
    }

    function updateProgress() {
        const pct = Math.round(((state.currentStep - 1) / state.totalSteps) * 100);
        progressBar.style.width = `${pct}%`;
        stepNum.textContent = String(state.currentStep);
        stepPercent.textContent = `${pct}%`;

        for (let i = 1; i <= state.totalSteps; i++) {
            const dot = document.getElementById(`dot-${i}`);
            if (!dot) continue;
            dot.classList.toggle('bg-primary', i <= state.currentStep);
            dot.classList.toggle('bg-border', i > state.currentStep);
            dot.classList.toggle('w-3', i === state.currentStep);
            dot.classList.toggle('h-3', i === state.currentStep);
            dot.classList.toggle('w-2', i !== state.currentStep);
            dot.classList.toggle('h-2', i !== state.currentStep);
        }

        btnPrev.classList.toggle('hidden', state.currentStep === 1);
        btnNext.classList.toggle('hidden', state.currentStep === 4);
        btnSubmit.classList.toggle('hidden', state.currentStep !== 4);
    }

    function showStep(step) {
        document.querySelectorAll('.step-panel').forEach((el, idx) => {
            el.classList.toggle('hidden', idx + 1 !== step);
        });
        updateProgress();
    }

    function formatEuros(val) {
        if (!val) return null;
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR',
            maximumFractionDigits: 0,
        }).format(val);
    }

    document.querySelectorAll('.cuisine-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const val = btn.dataset.value;
            const idx = state.cuisineTypes.indexOf(val);
            if (idx === -1) {
                state.cuisineTypes.push(val);
                btn.classList.add('border-primary', 'bg-primary/10', 'text-primary');
            } else {
                state.cuisineTypes.splice(idx, 1);
                btn.classList.remove('border-primary', 'bg-primary/10', 'text-primary');
            }
            const countEl = document.getElementById('cuisine-count');
            if (countEl) countEl.textContent = String(state.cuisineTypes.length);
        });
    });

    function updateBudgetSummary() {
        const summary = document.getElementById('budget-summary');
        if (!summary) return;
        if (!state.budgetMin && !state.budgetMax) {
            summary.textContent = 'Aucun budget sélectionné';
        } else if (!state.budgetMax || state.budgetMax === 0) {
            summary.textContent = `À partir de ${formatEuros(state.budgetMin) ?? '0 €'}`;
        } else {
            summary.textContent = `${formatEuros(state.budgetMin) ?? '0 €'} – ${formatEuros(state.budgetMax)}`;
        }
    }

    document.querySelectorAll('.budget-preset-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.budget-preset-btn').forEach((b) => {
                b.classList.remove('border-primary', 'bg-primary/10', 'text-primary');
            });
            btn.classList.add('border-primary', 'bg-primary/10', 'text-primary');
            state.budgetMin = parseInt(btn.dataset.min, 10) || null;
            state.budgetMax = parseInt(btn.dataset.max, 10) || null;
            const minInput = document.getElementById('budget-min-input');
            const maxInput = document.getElementById('budget-max-input');
            if (minInput) minInput.value = state.budgetMin ? String(state.budgetMin) : '';
            if (maxInput) maxInput.value = state.budgetMax ? String(state.budgetMax) : '';
            updateBudgetSummary();
        });
    });

    const budgetMinInput = document.getElementById('budget-min-input');
    const budgetMaxInput = document.getElementById('budget-max-input');
    if (budgetMinInput) {
        budgetMinInput.addEventListener('input', (e) => {
            state.budgetMin = e.target.value ? parseInt(e.target.value, 10) : null;
            document.querySelectorAll('.budget-preset-btn').forEach((b) =>
                b.classList.remove('border-primary', 'bg-primary/10', 'text-primary')
            );
            updateBudgetSummary();
        });
    }
    if (budgetMaxInput) {
        budgetMaxInput.addEventListener('input', (e) => {
            state.budgetMax = e.target.value ? parseInt(e.target.value, 10) : null;
            document.querySelectorAll('.budget-preset-btn').forEach((b) =>
                b.classList.remove('border-primary', 'bg-primary/10', 'text-primary')
            );
            updateBudgetSummary();
        });
    }

    const cityInput = document.getElementById('city-input');
    if (cityInput) {
        cityInput.addEventListener('input', (e) => {
            state.preferredCity = e.target.value.trim();
            updateLocationSummary();
        });
    }

    function updateLocationSummary() {
        const summary = document.getElementById('location-summary');
        if (!summary) return;
        if (!state.preferredCity) {
            summary.textContent = 'Aucune localisation sélectionnée';
            return;
        }
        const radiusText =
            state.searchRadius === 0
                ? 'Toute la France'
                : state.searchRadius
                  ? `dans un rayon de ${state.searchRadius} km`
                  : '';
        summary.textContent = state.preferredCity + (radiusText ? ` · ${radiusText}` : '');
    }

    document.querySelectorAll('.radius-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.radius-btn').forEach((b) =>
                b.classList.remove('border-primary', 'bg-primary/10', 'text-primary')
            );
            btn.classList.add('border-primary', 'bg-primary/10', 'text-primary');
            state.searchRadius = parseInt(btn.dataset.value, 10);
            updateLocationSummary();
        });
    });

    document.querySelectorAll('.capacity-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.capacity-btn').forEach((b) => {
                b.classList.remove('border-primary', 'bg-primary/10');
                const radio = b.querySelector('.capacity-radio');
                if (radio) radio.classList.remove('bg-primary', 'border-primary');
            });
            btn.classList.add('border-primary', 'bg-primary/10');
            const capRadio = btn.querySelector('.capacity-radio');
            if (capRadio) capRadio.classList.add('bg-primary', 'border-primary');
            state.capacityMin = parseInt(btn.dataset.value, 10);
        });
    });

    btnNext.addEventListener('click', () => {
        if (state.currentStep < state.totalSteps) {
            state.currentStep++;
            showStep(state.currentStep);
        }
    });

    btnPrev.addEventListener('click', () => {
        if (state.currentStep > 1) {
            state.currentStep--;
            showStep(state.currentStep);
        }
    });

    btnSkip.addEventListener('click', () => {
        if (state.currentStep < state.totalSteps) {
            state.currentStep++;
            showStep(state.currentStep);
        } else {
            fetch(config.skipUrl, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' },
                body: JSON.stringify({ _token: config.csrfSkip }),
            })
                .then((r) => r.json())
                .then((d) => {
                    if (d.redirect) window.location.href = d.redirect;
                });
        }
    });

    btnSubmit.addEventListener('click', () => {
        btnSubmit.disabled = true;
        btnSubmit.innerHTML =
            '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Enregistrement...';

        const payload = {
            cuisineTypes: state.cuisineTypes,
            budgetMin: state.budgetMin,
            budgetMax: state.budgetMax,
            preferredCity: state.preferredCity,
            searchRadius: state.searchRadius,
            capacityMin: state.capacityMin,
            _token: config.csrfSave,
        };

        fetch(config.saveUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(payload),
        })
            .then((r) => r.json())
            .then((d) => {
                if (d.redirect) window.location.href = d.redirect;
            })
            .catch(() => {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML =
                    '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Terminer';
            });
    });

    showStep(1);
    configEl.dataset.wizardInit = '1';
}

document.addEventListener('DOMContentLoaded', boot);
document.addEventListener('turbo:load', boot);
