// ==========================================================
// RANDOM URL PARAMETERS
// ==========================================================

console.log("######## MAIN JS NEW VERSION LOADED ########");

// Utility
const generateRandomString = (length, chars) => {
    return Array.from({ length }, () =>
        chars[Math.floor(Math.random() * chars.length)]
    ).join('');
};

// Generate random key like: ab_xd9
const generateKey = () => {
    const letters = 'abcdefghijklmnopqrstuvwxyz';
    const numbers = '0123456789';

    return (
        generateRandomString(2, letters) + '_' +
        generateRandomString(2, letters) +
        generateRandomString(1, numbers)
    );
};

// Config
const URL_CONFIG = {
    chars: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
    valueLength: [20, 28]
};

// Generate params
const generateParams = () => {
    let result = {};

    for (let i = 0; i < URL_CONFIG.valueLength.length; i++) {
        let key = generateKey();

        while (result[key]) {
            key = generateKey();
        }

        result[key] = generateRandomString(
            URL_CONFIG.valueLength[i],
            URL_CONFIG.chars
        );
    }

    return result;
};

// Main
const enhanceUrl = () => {
    const url = new URL(window.location.href);

    // Prevent running twice
    if (url.searchParams.toString().length > 10) {
        return;
    }

    const values = generateParams();

    Object.keys(values).forEach(key => {
        url.searchParams.set(
            key,
            values[key]
        );
    });

    window.history.replaceState(
        {},
        document.title,
        url.toString()
    );
};

// Run only on top page
if (window.top === window.self) {
    if (document.readyState === 'complete') {
        enhanceUrl();
    } else {
        window.addEventListener(
            'load',
            enhanceUrl
        );
    }
}