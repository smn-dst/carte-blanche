/** @template TFn @param {TFn} fn @param {number} delay @returns {TFn} */
export function debounce(fn, delay) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}
