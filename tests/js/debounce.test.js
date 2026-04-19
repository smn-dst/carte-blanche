import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { debounce } from '../../assets/js/utils/debounce.js';

describe('debounce', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('n\'exécute qu\'après le délai', () => {
        const fn = vi.fn();
        const debounced = debounce(fn, 400);

        debounced();
        expect(fn).not.toHaveBeenCalled();

        vi.advanceTimersByTime(399);
        expect(fn).not.toHaveBeenCalled();

        vi.advanceTimersByTime(1);
        expect(fn).toHaveBeenCalledTimes(1);
    });

    it('réinitialise le timer à chaque appel', () => {
        const fn = vi.fn();
        const debounced = debounce(fn, 100);

        debounced();
        vi.advanceTimersByTime(50);
        debounced();
        vi.advanceTimersByTime(50);
        expect(fn).not.toHaveBeenCalled();

        vi.advanceTimersByTime(50);
        expect(fn).toHaveBeenCalledTimes(1);
    });
});
