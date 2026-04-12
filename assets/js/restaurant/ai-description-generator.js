/**
 * Bouton « Générer avec IA » sur le formulaire restaurant.
 * @param {string} generateUrl URL POST JSON
 */
let aiDescriptionTurboBound = false;

export function initAiDescriptionGenerator(generateUrl) {
    if (!generateUrl) {
        return;
    }

    const bind = () => {
        const btn = document.getElementById('btn-generate-description');
        const textarea = document.querySelector('textarea[name*="description"]');
        const iconGen = document.getElementById('icon-generate');
        const iconLoad = document.getElementById('icon-loading');
        const label = document.getElementById('btn-generate-label');

        if (!btn || !textarea || !iconGen || !iconLoad || !label) {
            return;
        }

        if (btn.dataset.aiGeneratorBound === '1') {
            return;
        }

        const setStatus = (message, type = 'info') => {
            const status = document.getElementById('ai-status');
            if (!status) return;
            status.textContent = message;
            status.classList.remove(
                'hidden',
                'text-muted-foreground',
                'border-border',
                'text-green-400',
                'border-green-500/30',
                'bg-green-500/10',
                'text-red-400',
                'border-red-500/30',
                'bg-red-500/10'
            );
            if (type === 'success') {
                status.classList.add('text-green-400', 'border-green-500/30', 'bg-green-500/10');
            } else if (type === 'error') {
                status.classList.add('text-red-400', 'border-red-500/30', 'bg-red-500/10');
            } else {
                status.classList.add('text-muted-foreground', 'border-border');
            }
        };

        const getFieldValue = (name) => {
            const el = document.querySelector(`[name*="[${name}]"]`);
            return el ? el.value.trim() : '';
        };

        const getCheckedCategories = () =>
            Array.from(document.querySelectorAll('input[name*="[categories]"]:checked')).map(
                (el) => el.closest('label')?.querySelector('span')?.textContent?.trim() ?? ''
            );

        btn.addEventListener('click', async () => {
            btn.disabled = true;
            iconGen.classList.add('hidden');
            iconLoad.classList.remove('hidden');
            label.textContent = 'Génération...';
            setStatus('Génération en cours...', 'info');

            const payload = {
                name: getFieldValue('name'),
                address: getFieldValue('address'),
                capacity: getFieldValue('capacity'),
                askingPrice: getFieldValue('askingPrice'),
                annualRevenue: getFieldValue('annualRevenue'),
                rent: getFieldValue('rent'),
                leaseRemaining: getFieldValue('leaseRemaining'),
                auctionLocation: getFieldValue('auctionLocation'),
                categories: getCheckedCategories(),
            };

            try {
                const res = await fetch(generateUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();

                if (data.description) {
                    textarea.value = data.description;
                    setStatus('✓ Description générée — vous pouvez la modifier', 'success');
                } else {
                    setStatus(`✗ ${data.error ?? 'Erreur inconnue'}`, 'error');
                }
            } catch (e) {
                setStatus(`✗ Erreur réseau : ${e.message}`, 'error');
            } finally {
                btn.disabled = false;
                iconGen.classList.remove('hidden');
                iconLoad.classList.add('hidden');
                label.textContent = 'Générer avec IA';
            }
        });

        btn.dataset.aiGeneratorBound = '1';
    };

    bind();
    if (!aiDescriptionTurboBound) {
        document.addEventListener('turbo:load', bind);
        aiDescriptionTurboBound = true;
    }
}
